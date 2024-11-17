<?php

class AI_Design_Block_API {
    private $api;

    public function __construct($provider = null, $model = null) {
        $provider = $provider ?: AI_Design_Block_Settings::get_api_provider();
        $this->api = $this->get_api_instance($provider, $model);
    }

    private function get_api_instance($provider, $model) {
        switch ($provider) {
            case 'openai':
                return new AI_Design_Block_API_OpenAI($model);
            case 'anthropic':
                return new AI_Design_Block_API_Anthropic($model);
            case 'gemini':
                return new AI_Design_Block_API_Gemini($model);
            default:
                return new AI_Design_Block_API_OpenAI($model);
        }
    }

    public function generate_pattern($content, $image_url = '', $image_analysis_model = '') {
        return $this->api->generate_pattern($content, $image_url, $image_analysis_model);
    }

    public function get_available_models() {
        return $this->api->get_available_models();
    }
}