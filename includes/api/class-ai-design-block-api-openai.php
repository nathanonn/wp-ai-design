<?php

class AI_Design_Block_API_OpenAI extends AI_Design_Block_API_Core {
    protected function get_api_key() {
        $api_keys = get_option('ai_design_block_api_keys', array());
        return isset($api_keys['openai']) ? $api_keys['openai'] : '';
    }

    protected function set_api_url() {
        $this->api_url = 'https://api.openai.com/v1/chat/completions';
    }

    protected function get_default_model() {
        return 'gpt-4o-mini';
    }

    protected function get_request_args($prompt, $image_url = '', $image_analysis_model = '', $image_description = '') {
        $system_prompt = $this->get_system_prompt('openai', 'generate_patterns');
        // check if the model is starts with "o1-"
        $is_o1_models = strpos($this->model, 'o1-') === 0 ? true : false;
        
        $messages = [];
        if (!$is_o1_models) {
            $messages[] = ['role' => 'system', 'content' => $system_prompt];
        }

        // if the model is o1-models, then we need to add the system prompt to the user prompt
        $user_prompt = $is_o1_models ? $system_prompt . "\n\n" : "";

        if (!empty($image_url)) {
            $image_data = $this->fetch_image_data($image_url);
            if ($image_data === false) {
                return new WP_Error('image_fetch_failed', 'Failed to fetch image data from URL');
            }
            // set the user prompt to generate a WordPress block pattern based on the attached image (only add the image description if the image description is not empty)
            $user_prompt .= "Generate a WordPress block pattern based on the attached image." . (!empty($image_description) ? "\n\nHere is the image description: $image_description" : '');
            $messages[] = [
                'role' => 'user',
                'content' => [
                    ['type' => 'text', 'text' => $user_prompt],
                    ['type' => 'image_url', 'image_url' => ['url' => "data:{$image_data['mime_type']};base64,{$image_data['base64_data']}"]]
                ]
            ];
        } else {
            $user_prompt .= $prompt;
            $messages[] = [
                'role' => 'user', 
                'content' => $user_prompt
            ];
        }

        return [
            'method' => 'POST',
            'timeout' => 600,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key,
            ],
            'body' => json_encode([
                'model' => $this->model,
                'messages' => $messages
            ]),
        ];
    }

    protected function process_api_response($data) {
        if (!isset($data['choices'][0]['message']['content'])) {
            return new WP_Error('invalid_response', 'Invalid API response format');
        }
        $response_text = $data['choices'][0]['message']['content'];
        return $response_text;
    }

    protected function get_image_analysis_request_args($system_prompt, $image_data, $image_analysis_model = '') {
        $model = $image_analysis_model ? explode('_|_', $image_analysis_model)[1] : $this->model;
        
        return [
            'method' => 'POST',
            'timeout' => 600,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key,
            ],
            'body' => json_encode([
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $system_prompt],
                    [
                        'role' => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => "Analyze this image:"],
                            ['type' => 'image_url', 
                                'image_url' => [
                                    'url' => "data:{$image_data['mime_type']};base64,{$image_data['base64_data']}"
                                ]
                            ]
                        ]
                    ]
                ]
            ]),
        ];
    }

    protected function extract_image_analysis($data) {
        if (!isset($data['choices'][0]['message']['content'])) {
            return new WP_Error('invalid_response', 'Invalid API response format');
        }

        $analysis = $data['choices'][0]['message']['content'];
        preg_match('/<design_description>(.*?)<\/design_description>/s', $analysis, $matches);
        
        return $matches[1] ?? new WP_Error('invalid_analysis', 'Failed to extract design description');
    }

    public function get_available_models() {
        $response = wp_remote_get('https://api.openai.com/v1/models', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
            ],
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!isset($data['data'])) {
            return new WP_Error('invalid_response', 'Invalid response from OpenAI API');
        }

        $models = array_filter($data['data'], function($model) {
            return $model['id'];
        });

        return array_column($models, 'id');
    }

    protected function get_provider_name() {
        return 'openai';
    }

    protected function calculate_token_usage($data) {
        return [
            'input_tokens'  => $data['usage']['prompt_tokens'] ?? 0,
            'output_tokens' => $data['usage']['completion_tokens'] ?? 0,
            'total_tokens'  => $data['usage']['total_tokens'] ?? 0,
        ];
    }
}
