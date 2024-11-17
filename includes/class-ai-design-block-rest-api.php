<?php

class AI_Design_Block_REST_API {
    private $api;

    public function __construct($api) {
        $this->api = $api;
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes() {
        register_rest_route('ai-design-block/v1', '/generate', array(
            'methods' => 'POST',
            'callback' => array($this, 'generate_pattern'),
            'permission_callback' => function () {
                return current_user_can('edit_posts');
            },
        ));

        register_rest_route('ai-design-block/v1', '/providers-and-models', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_providers_and_models'),
            'permission_callback' => function () {
                return current_user_can('edit_posts');
            },
        ));

        register_rest_route('ai-design-block/v1', '/settings', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_settings'),
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ));

        register_rest_route('ai-design-block/v1', '/block-settings', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_block_settings'),
            'permission_callback' => function () {
                return current_user_can('edit_posts');
            },
        ));

        register_rest_route('ai-design-block/v1', '/settings', array(
            'methods' => 'POST',
            'callback' => array($this, 'update_settings'),
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ));

        register_rest_route('ai-design-block/v1', '/provider-models/(?P<provider>[a-zA-Z0-9-]+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_provider_models'),
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ));

        register_rest_route('ai-design-block/v1', '/logger-status', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_logger_status'),
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ));

        register_rest_route('ai-design-block/v1', '/logger-status', array(
            'methods' => 'POST',
            'callback' => array($this, 'update_logger_status'),
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ));

        register_rest_route('ai-design-block/v1', '/remove-logs', array(
            'methods' => 'POST',
            'callback' => array($this, 'remove_all_logs'),
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ));

        register_rest_route('ai-design-block/v1', '/drop-table', array(
            'methods' => 'POST',
            'callback' => array($this, 'drop_table'),
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ));

        register_rest_route('ai-design-block/v1', '/recreate-table', array(
            'methods' => 'POST',
            'callback' => array($this, 'recreate_table'),
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ));
    }

    public function generate_pattern($request) {
        $content = $request->get_param('content');
        $provider = $request->get_param('provider');
        $model = $request->get_param('model');
        $image_url = $request->get_param('image_url');
        $analyze_image = $request->get_param('analyze_image');

        if (empty($content) && empty($image_url)) {
            return new WP_Error('missing_params', 'Either content or image_url is required.', array('status' => 400));
        }

        if ($analyze_image === 'yes') {
            $image_analysis_model = get_option('ai_design_block_default_image_analysis_model', '');
        }

        $api = new AI_Design_Block_API($provider, $model);
        $response = $api->generate_pattern($content, $image_url, $analyze_image === 'yes' ? $image_analysis_model : '');

        if (is_wp_error($response)) {
            return $response;
        }

        return rest_ensure_response($response);
    }

    public function get_providers_and_models() {
        $api_keys = get_option('ai_design_block_api_keys', array());
        $ai_models = get_option('ai_design_block_ai_models', array());
        $default_provider = get_option('ai_design_block_default_provider', '');

        $available_providers = array();

        foreach ($api_keys as $provider => $key) {
            if ($key && isset($ai_models[$provider])) {
                $available_providers[$provider] = array(
                    'model' => $ai_models[$provider],
                    'is_default' => ($provider === $default_provider)
                );
            }
        }

        return rest_ensure_response($available_providers);
    }

    public function get_settings() {
        return rest_ensure_response($this->get_settings_array(true));
    }

    public function get_block_settings() {
        return rest_ensure_response($this->get_settings_array(false));
    }

    public function update_settings($request) {
        $params = $request->get_params();
        $providers = $params['providers'];
        $custom_api_url = $params['customApiUrl'];
        $default_model = $params['defaultModel'];
        $default_image_analysis_model = $params['defaultImageAnalysisModel'];

        $api_keys = array();
        $ai_models = array();
        $custom_prompts = array();
        $custom_prompt_enabled = array();
        $enabled_providers = array();

        foreach ($providers as $provider => $settings) {
            $api_keys[$provider] = sanitize_text_field($settings['apiKey']);
            $ai_models[$provider] = array_map('sanitize_text_field', $settings['aiModels']);
            $custom_prompts[$provider] = sanitize_textarea_field($settings['customPrompt']);
            $custom_prompt_enabled[$provider] = (bool) $settings['customPromptEnabled'];
            $enabled_providers[$provider] = (bool) $settings['enabled'];
        }
        
        update_option('ai_design_block_api_keys', $api_keys);
        update_option('ai_design_block_ai_models', $ai_models);
        update_option('ai_design_block_custom_prompts', $custom_prompts);
        update_option('ai_design_block_custom_prompt_enabled', $custom_prompt_enabled);
        update_option('ai_design_block_default_model', sanitize_text_field($default_model));
        update_option('ai_design_block_custom_api_url', sanitize_url($custom_api_url));
        update_option('ai_design_block_enabled_providers', $enabled_providers);
        update_option('ai_design_block_default_image_analysis_model', sanitize_text_field($default_image_analysis_model));

        return rest_ensure_response(array('message' => 'Settings updated successfully'));
    }

    public function get_provider_models($request) {
        $provider = $request['provider'];
        $api = new AI_Design_Block_API($provider);
        $models = $api->get_available_models();

        if (is_wp_error($models)) {
            return new WP_REST_Response(array('error' => $models->get_error_message()), 400);
        }

        if (!is_array($models)) {
            return new WP_REST_Response(array('error' => 'Invalid response format'), 500);
        }

        return rest_ensure_response($models);
    }

    public function get_logger_status() {
        $logger = new AI_Design_Block_Logger();
        $is_logger_enabled = get_option('ai_design_block_logger_enabled', true);
        $is_table_dropped = !$logger->table_exists();

        return rest_ensure_response(array(
            'enabled' => $is_logger_enabled,
            'tableDropped' => $is_table_dropped
        ));
    }

    public function update_logger_status($request) {
        $is_logger_enabled = $request->get_param('enabled');
        update_option('ai_design_block_logger_enabled', $is_logger_enabled);

        return rest_ensure_response(array('enabled' => $is_logger_enabled));
    }

    public function remove_all_logs() {
        $logger = new AI_Design_Block_Logger();
        $result = $logger->clear_logs();

        if ($result) {
            return rest_ensure_response(array('message' => 'All logs removed successfully'));
        } else {
            return new WP_Error('log_clear_failed', 'Failed to remove logs', array('status' => 500));
        }
    }

    public function drop_table() {
        $logger = new AI_Design_Block_Logger();
        $result = AI_Design_Block_Logger::drop_table();

        if ($result) {
            return rest_ensure_response(array('message' => 'Table dropped successfully'));
        } else {
            return new WP_Error('table_drop_failed', 'Failed to drop table', array('status' => 500));
        }
    }

    public function recreate_table() {
        $logger = new AI_Design_Block_Logger();
        $result = AI_Design_Block_Logger::create_table();

        if ($result) {
            return rest_ensure_response(array('message' => 'Table recreated successfully'));
        } else {
            return new WP_Error('table_create_failed', 'Failed to recreate table', array('status' => 500));
        }
    }

    private function get_settings_array( $show_api_keys = false, $show_custom_prompts = true ) {
        $api_keys = get_option('ai_design_block_api_keys', array());
        $ai_models = get_option('ai_design_block_ai_models', array());
        $custom_prompts = get_option('ai_design_block_custom_prompts', array());
        $custom_prompt_enabled = get_option('ai_design_block_custom_prompt_enabled', array());
        $default_model = get_option('ai_design_block_default_model', '');
        $custom_api_url = get_option('ai_design_block_custom_api_url', '');
        $enabled_providers = get_option('ai_design_block_enabled_providers', array());
        $default_image_analysis_model = get_option('ai_design_block_default_image_analysis_model', '');

        $providers = array();
        foreach (array('openai', 'anthropic', 'gemini') as $provider) {
            $providers[$provider] = [];
            if ($show_api_keys) {
                $providers[$provider]['apiKey'] = isset($api_keys[$provider]) ? $api_keys[$provider] : '';
            }
            if ($show_custom_prompts) {
                $providers[$provider]['customPrompt'] = isset($custom_prompts[$provider]) ? $custom_prompts[$provider] : '';
                $providers[$provider]['customPromptEnabled'] = isset($custom_prompt_enabled[$provider]) ? $custom_prompt_enabled[$provider] : false;
            }
            $providers[$provider]['aiModels'] = isset($ai_models[$provider]) ? (array)$ai_models[$provider] : [];
            $providers[$provider]['enabled'] = isset($enabled_providers[$provider]) ? $enabled_providers[$provider] : false;
        }

        return array(
            'providers' => $providers,
            'customApiUrl' => $custom_api_url,
            'defaultModel' => $default_model,
            'defaultImageAnalysisModel' => $default_image_analysis_model
        );
    }

}