<?php

class AI_Design_Block_Settings {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function add_settings_page() {
        add_menu_page(
            __('AI Design Block', 'ai-design-block'),
            __('AI Design Block', 'ai-design-block'),
            'manage_options',
            'ai-design-block-settings',
            array($this, 'render_settings_page'),
            'dashicons-admin-customizer',
            90
        );
    }

    public function register_settings() {
        register_setting('ai_design_block_settings', 'ai_design_block_custom_api_url');
        register_setting('ai_design_block_settings', 'ai_design_block_api_keys', array($this, 'sanitize_api_keys'));
        register_setting('ai_design_block_settings', 'ai_design_block_ai_models', array($this, 'sanitize_ai_models'));
        register_setting('ai_design_block_settings', 'ai_design_block_custom_prompts', array($this, 'sanitize_custom_prompts'));
        register_setting('ai_design_block_settings', 'ai_design_block_default_provider');
    }

    public function sanitize_api_keys($input) {
        $sanitized_input = array();
        foreach ($input as $provider => $key) {
            $sanitized_input[$provider] = sanitize_text_field($key);
        }
        return $sanitized_input;
    }

    public function sanitize_ai_models($input) {
        $sanitized_input = array();
        foreach ($input as $provider => $models) {
            $sanitized_input[$provider] = array_map('sanitize_text_field', $models);
        }
        return $sanitized_input;
    }

    public function sanitize_custom_prompts($input) {
        $sanitized_input = array();
        foreach ($input as $provider => $prompt) {
            $sanitized_input[$provider] = sanitize_textarea_field($prompt);
        }
        return $sanitized_input;
    }

    public static function get_api_provider() {
        return get_option('ai_design_block_api_provider', 'openai');
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
          <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
          <div id="ai-design-block-settings"></div>
        </div>
        <?php
    }
}