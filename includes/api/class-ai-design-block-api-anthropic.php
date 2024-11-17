<?php

class AI_Design_Block_API_Anthropic extends AI_Design_Block_API_Core {
    protected function get_api_key() {
        $api_keys = get_option('ai_design_block_api_keys', array());
        return isset($api_keys['anthropic']) ? $api_keys['anthropic'] : '';
    }

    protected function set_api_url() {
        $this->api_url = 'https://api.anthropic.com/v1/messages';
    }

    protected function get_default_model() {
        return 'claude-3.5-sonnet';
    }

    protected function get_request_args($prompt, $image_url = '', $image_analysis_model = '', $image_description = '') {
        $system_prompt = $this->get_system_prompt('anthropic', 'generate_patterns');

        $messages = [];

        $user_message = ['role' => 'user', 'content' => []];

        if (!empty($image_url)) {
            $image_data = $this->fetch_image_data($image_url);
            if ($image_data === false) {
                return new WP_Error('image_fetch_failed', 'Failed to fetch image data from URL');
            }
            $base64_image = base64_encode($image_data['data']);

            $user_message['content'][] = [
                'type' => 'image',
                'source' => [
                    'type' => 'base64',
                    'media_type' => $image_data['mime_type'],
                    'data' => $base64_image
                ]
            ];
            $user_message['content'][] = ['type' => 'text', 'text' => "Generate a WordPress block pattern based on the attached image." . (!empty($image_description) ? "\n\nHere is the image description: $image_description" : '')];
        } else {
            $user_message['content'][] = ['type' => 'text', 'text' => $prompt];
        }

        $messages[] = $user_message;

        return [
            'method' => 'POST',
            'timeout' => 600,
            'headers' => [
                'Content-Type' => 'application/json',
                'x-api-key' => $this->api_key,
                'anthropic-version' => '2023-06-01',
            ],
            'body' => json_encode([
                'model' => $this->model,
                'system' => $system_prompt,
                'messages' => $messages,
                'max_tokens' => $this->get_max_tokens()
            ]),
        ];
    }

    protected function get_image_analysis_request_args($system_prompt, $image_data, $image_analysis_model = '') {
        $model = $image_analysis_model ? explode('_|_', $image_analysis_model)[1] : $this->model;

        return [
            'method' => 'POST',
            'timeout' => 600,
            'headers' => [
                'Content-Type' => 'application/json',
                'x-api-key' => $this->api_key,
                'anthropic-version' => '2023-06-01',
            ],
            'body' => json_encode([
                'model' => $model,
                'system' => $system_prompt,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => "Analyze this image."],
                            ['type' => 'image', 'source' => [
                                'type' => 'base64',
                                'media_type' => $image_data['mime_type'],
                                'data' => $image_data['base64_data']
                            ]]
                        ]
                    ]
                ],
                'max_tokens' => $this->get_max_tokens()
            ]),
        ];
    }

    protected function get_max_tokens() {
        switch ($this->model) {
            case "claude-3-5-sonnet-20241022":
            case "claude-3-5-sonnet-20240620":
            case "claude-3-5-haiku-20241022":
                return 8192;
            default:
                return 4096;
        }
    }

    protected function process_api_response($data) {
        if (!isset($data['content'][0]['text'])) {
            return new WP_Error('invalid_response', 'Invalid API response format');
        }
        $response_text = $data['content'][0]['text'];
        return $response_text;
    }

    protected function extract_image_analysis($data) {
        if (!isset($data['content'][0]['text'])) {
            return new WP_Error('invalid_response', 'Invalid API response format');
        }

        $analysis = $data['content'][0]['text'];
        preg_match('/<design_description>(.*?)<\/design_description>/s', $analysis, $matches);
        
        return $matches[1] ?? new WP_Error('invalid_analysis', 'Failed to extract design description');
    }

    public function get_available_models() {
        return ['claude-3-5-sonnet-20241022','claude-3-5-sonnet-20240620', "claude-3-5-haiku-20241022" , 'claude-3-opus-20240229', 'claude-3-haiku-20240307'];
    }

    protected function get_provider_name() {
        return 'anthropic';
    }

    protected function calculate_token_usage($data) {
        return [
            'input_tokens'  => $data['usage']['input_tokens'] ?? 0,
            'output_tokens' => $data['usage']['output_tokens'] ?? 0,
            // total tokens is the sum of input and output tokens
            'total_tokens'  => $data['usage']['input_tokens'] + $data['usage']['output_tokens']
        ];
    }
}