<?php
/**
 * Plugin Name: WP AI Design
 * Plugin URI: https://github.com/nathanonn/wp-ai-design
 * Description: A WordPress block plugin that uses AI to generate designs from text using LLMs.
 * Version: 0.1.0
 * Author: Nathan Onn
 * Author URI: https://www.nathanonn.com
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

define('AI_DESIGN_BLOCK_VERSION', '0.1.0');
define('AI_DESIGN_BLOCK_PATH', plugin_dir_path(__FILE__));
define('AI_DESIGN_BLOCK_URL', plugin_dir_url(__FILE__));

// Require API files
require_once AI_DESIGN_BLOCK_PATH . 'includes/api/class-ai-design-block-api-core.php';
require_once AI_DESIGN_BLOCK_PATH . 'includes/api/class-ai-design-block-api-openai.php';
require_once AI_DESIGN_BLOCK_PATH . 'includes/api/class-ai-design-block-api-anthropic.php';
require_once AI_DESIGN_BLOCK_PATH . 'includes/api/class-ai-design-block-api-gemini.php';
require_once AI_DESIGN_BLOCK_PATH . 'includes/api/class-ai-design-block-api.php';

// Require other necessary files
require_once AI_DESIGN_BLOCK_PATH . 'includes/class-ai-design-block-settings.php';
require_once AI_DESIGN_BLOCK_PATH . 'includes/class-ai-design-block-rest-api.php';
require_once AI_DESIGN_BLOCK_PATH . 'includes/class-ai-design-block-logger.php';
require_once AI_DESIGN_BLOCK_PATH . 'includes/class-ai-design-block-logs-page.php';

class AI_Design_Block {
    private $settings;
    private $api;
    private $rest_api;
    private $logs_page;

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));

        $this->settings = new AI_Design_Block_Settings();
        $this->api = new AI_Design_Block_API();
        $this->rest_api = new AI_Design_Block_REST_API($this->api);
        $this->logs_page = new AI_Design_Block_Logs_Page();
    }

    public function init() {
        register_block_type(AI_DESIGN_BLOCK_PATH . 'build');
    }

    public function enqueue_assets() {
        wp_enqueue_script(
            'ai-design-block-editor',
            AI_DESIGN_BLOCK_URL . 'build/index.js',
            array('wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor'),
            AI_DESIGN_BLOCK_VERSION
        );

        wp_enqueue_style(
            'ai-design-block-editor',
            AI_DESIGN_BLOCK_URL . 'build/index.css',
            array('wp-edit-blocks'),
            AI_DESIGN_BLOCK_VERSION
        );
    }
}

function ai_design_block_init() {
    new AI_Design_Block();
}
add_action('plugins_loaded', 'ai_design_block_init');

register_activation_hook(__FILE__, 'ai_design_block_activate');
register_deactivation_hook(__FILE__, 'ai_design_block_deactivate');

function ai_design_block_activate() {
    try {
        global $wpdb;
        $table_name = $wpdb->prefix . AI_Design_Block_Logger::get_table_name();
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            AI_Design_Block_Logger::create_table();
        }
    } catch (Exception $e) {
        error_log('AI Design Block activation error: ' . $e->getMessage());
        wp_die('Error activating AI Design Block plugin. Please check the error log for details.');
    }
}

function ai_design_block_deactivate() {
    // Deactivation tasks (if needed)
    // Remove this
    // AI_Design_Block_Logger::drop_table();
}