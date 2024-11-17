<?php

abstract class AI_Design_Block_API_Core {
    protected $api_key;
    protected $api_url;
    protected $model;
    protected $last_api_response;

    public function __construct($model = null) {
        $this->model = $model ?: $this->get_default_model();
        $this->api_key = $this->get_api_key();
        $this->set_api_url();
    }

    abstract protected function get_api_key();
    abstract protected function set_api_url();
    abstract protected function get_default_model();
    abstract protected function get_request_args($prompt, $image_url = '', $image_analysis_model = '');
    abstract protected function process_api_response($data);
    abstract protected function get_provider_name();
    abstract protected function calculate_token_usage($data);

    public function generate_pattern($content, $image_url = '', $image_analysis_model = '') {
        $custom_prompts = get_option('ai_design_block_custom_prompts', array());
        $custom_prompt = isset($custom_prompts[static::class]) ? $custom_prompts[static::class] : '';
        $prompt = $this->build_prompt($content, $custom_prompt);

        $image_description = '';
        if (!empty($image_url) && !empty($image_analysis_model)) {
            $image_analysis_api = $this->get_image_analysis_api($image_analysis_model);
            $image_description = $image_analysis_api->analyze_image($image_url, $image_analysis_model);
            if (is_wp_error($image_description)) {
                return $image_description;
            }
        }

        $request_args = $this->get_request_args($prompt, $image_url, $image_description);

        $response = wp_remote_post($this->api_url, $request_args);

        if (is_wp_error($response)) {
            return new WP_Error('api_error', $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $this->last_api_response = json_decode($body, true);

        if (isset($this->last_api_response['error'])) {
            return new WP_Error('api_error', $this->last_api_response['error']['message']);
        }

        $result = $this->process_api_response($this->last_api_response);

        $this->log_api_call('generate_pattern', $content, $image_url, $result);

        return $this->parse_json_response($result);
    }

    protected function get_image_analysis_api($image_analysis_model) {
        list($provider, $model) = explode('_|_', $image_analysis_model);
        switch ($provider) {
            case 'openai':
                return new AI_Design_Block_API_OpenAI($model);
            case 'anthropic':
                return new AI_Design_Block_API_Anthropic($model);
            case 'gemini':
                return new AI_Design_Block_API_Gemini($model);
            default:
                return $this;
        }
    }

    protected function analyze_image($image_url, $image_analysis_model = '') {
        $image_data = $this->fetch_image_data($image_url);
        if ($image_data === false) {
            return new WP_Error('image_fetch_failed', 'Failed to fetch image data from URL');
        }

        $system_prompt = file_get_contents(AI_DESIGN_BLOCK_PATH . 'includes/prompt/system_analyze_image.md');

        $request_args = $this->get_image_analysis_request_args($system_prompt, $image_data, $image_analysis_model);

        $response = wp_remote_post($this->api_url, $request_args);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $this->last_api_response = json_decode($body, true);

        if (isset($this->last_api_response['error'])) {
            return new WP_Error('api_error', $this->last_api_response['error']['message']);
        }

        $result = $this->extract_image_analysis($this->last_api_response);

        $this->log_api_call('analyze_image', "Analyze this image: $image_url", $image_url, $result);

        return $result;
    }

    protected function log_api_call($call_type, $input, $image_url, $output) {
        $token_usage = $this->calculate_token_usage($this->last_api_response);

        AI_Design_Block_Logger::log_api_call([
            'call_type'     => $call_type,
            'provider'      => $this->get_provider_name(),
            'model'         => $this->model,
            'input'         => $input,
            'image_url'     => $image_url,
            'output'        => $output,
            'input_tokens'  => $token_usage['input_tokens'],
            'output_tokens' => $token_usage['output_tokens'],
            'total_tokens'  => $token_usage['total_tokens'],
        ]);
    }

    protected function build_prompt($content, $custom_prompt) {
        // $base_prompt = "Generate a WordPress block pattern for the following content. The response should be a JSON array of Gutenberg blocks.";
        // $prompt = $custom_prompt ? $custom_prompt : $base_prompt;
        // $prompt .= "\n\nContent: $content";
        $prompt = "<user_query>$content</user_query>\n<assistant_response>";
        return $prompt;
    }

    protected function extract_json_string($response_text = '') {
        // split it by ```
        $response_text = explode('```json', $response_text, 2)[1];
        // remove the trailing ``` after the JSON
        $response_text = explode('```', $response_text, 2)[0];
        // remove leading and trailing whitespace
        return trim($response_text);
    }

    protected function parse_json_response($response_text) {
        $json_string = $this->extract_json_string($response_text);
        $blocks = json_decode($json_string, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $json_string;
        }

        return $blocks;
    }

    protected function fetch_image_data($url) {
        $response = wp_safe_remote_get($url, array('timeout' => 30));
        if (is_wp_error($response)) {
            error_log('Error fetching image: ' . $response->get_error_message());
            return false;
        }
        $http_code = wp_remote_retrieve_response_code($response);
        if ($http_code !== 200) {
            error_log("HTTP error when fetching image. Code: {$http_code}");
            return false;
        }
        // get the extension of the url
        $extension = pathinfo($url, PATHINFO_EXTENSION);

        $image_data = wp_remote_retrieve_body($response);
        return [
            'url' => $url,
            'data' => $image_data,
            'base64_data' => base64_encode($image_data),
            'mime_type' => 'image/' . $extension
        ];
    }

    protected function get_image_analysis_request_args($system_prompt, $image_data, $image_analysis_model = '') {
        // This method should be implemented by each provider-specific class
        throw new Exception('get_image_analysis_request_args must be implemented in the provider-specific class');
    }

    protected function get_system_prompt($provider, $type = 'generate_patterns') {
        $system_prompt = file_get_contents(AI_DESIGN_BLOCK_PATH . 'includes/prompt/system_' . $type . '.md');
        
        $custom_prompts = get_option('ai_design_block_custom_prompts', array());
        $custom_prompt_enabled = get_option('ai_design_block_custom_prompt_enabled', array());
        
        if (isset($custom_prompt_enabled[$provider]) && $custom_prompt_enabled[$provider] && !empty($custom_prompts[$provider])) {
            $system_prompt = $custom_prompts[$provider];
        }

        return $system_prompt;
    }
}
