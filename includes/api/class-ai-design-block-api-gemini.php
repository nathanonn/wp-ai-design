<?php

class AI_Design_Block_API_Gemini extends AI_Design_Block_API_Core {
    protected function get_api_key() {
        $api_keys = get_option('ai_design_block_api_keys', array());
        return isset($api_keys['gemini']) ? $api_keys['gemini'] : '';
    }

    protected function set_api_url() {
        $this->api_url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $this->model . ':generateContent';
    }

    protected function get_default_model() {
        return 'gemini-1.5-flash';
    }

    protected function get_request_args($prompt, $image_url = '', $image_analysis_model = '', $image_description = '') {
        $system_prompt = $this->get_system_prompt('gemini', 'generate_patterns');

        $contents = [];
        $user_content = [
            "role" => "user",
            'parts' => []
        ];

        if (!empty($image_url)) {
            $image_data = $this->fetch_image_data($image_url);
            if ($image_data === false) {
                return new WP_Error('image_fetch_failed', 'Failed to fetch image data from URL');
            }
            $base64_image = base64_encode($image_data['data']);
            $user_content['parts'][] = [
                'inline_data' => [
                    'mime_type' => $image_data['mime_type'],
                    'data' => $base64_image
                ]
            ];
            $user_content['parts'][] = ['text' => "Generate a WordPress block pattern based on this image." . (!empty($image_description) ? "\n\nHere is the image description: $image_description" : '')];
        } else {
            $user_content['parts'][] = ['text' => $prompt];
        }

        $contents[] = $user_content;

        return [
            'method' => 'POST',
            'timeout' => 600,
            'headers' => [
                'Content-Type' => 'application/json',
                'x-goog-api-key' => $this->api_key,
            ],
            'body' => json_encode([
                'systemInstruction' => [
                    "role" => "user",
                    "parts" => [
                        [
                        "text" => $system_prompt
                        ]
                    ]
                ],
                'contents' => $contents
            ]),
        ];
    }

    protected function process_api_response($data) {
        if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            return new WP_Error('invalid_response', 'Invalid API response format');
        }
        $response_text = $data['candidates'][0]['content']['parts'][0]['text'];
        return $response_text;
    }

    public function get_available_models() {
        $response = wp_remote_get('https://generativelanguage.googleapis.com/v1beta/models', [
            'headers' => [
                'x-goog-api-key' => $this->api_key,
            ],
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!isset($data['models'])) {
            return new WP_Error('invalid_response', 'Invalid response from Gemini API');
        }

        $models = array_filter($data['models'], function($model) {
            return strpos($model['name'], 'gemini') !== false;
        });

        $model_names = array_map(function($model) {
            return str_replace('models/', '', $model['name']);
        }, $models);

        return array_values($model_names); // This ensures we return a simple array
    }

    protected function get_image_analysis_request_args($system_prompt, $image_data, $image_analysis_model = '') {
        $model = $image_analysis_model ? explode('_|_', $image_analysis_model)[1] : $this->model;

        return [
            'method' => 'POST',
            'timeout' => 600,
            'headers' => [
                'Content-Type' => 'application/json',
                'x-goog-api-key' => $this->api_key,
            ],
            'body' => json_encode([
                'systemInstruction' => [
                    "role" => "user",
                    "parts" => [
                        [
                        "text" => $system_prompt
                        ]
                    ]
                ],
                'contents' => [
                    [
                        'parts' => [
                            ['text' => "Analyze this image:"],
                            [
                                'inline_data' => [
                                    'mime_type' => $image_data['mime_type'],
                                    'data' => $image_data['base64_data']
                                ]
                            ]
                        ],
                        "role" => "user"
                    ]
                ]
            ]),
        ];
    }

    protected function extract_image_analysis($data) {
        if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            return new WP_Error('invalid_response', 'Invalid API response format');
        }

        $analysis = $data['candidates'][0]['content']['parts'][0]['text'];
        preg_match('/<design_description>(.*?)<\/design_description>/s', $analysis, $matches);
        
        return $matches[1] ?? new WP_Error('invalid_analysis', 'Failed to extract design description');
    }

    protected function calculate_token_usage($data) {
        return [
            'input_tokens'  => $data['UsageMetadata']['promptTokenCount'] ?? 0,
            'output_tokens' => $data['UsageMetadata']['candidatesTokenCount'] ?? 0,
            'total_tokens'  => $data['UsageMetadata']['totalTokenCount'] ?? 0,
        ];
    }

    protected function get_provider_name() {
        return 'gemini';
    }
}