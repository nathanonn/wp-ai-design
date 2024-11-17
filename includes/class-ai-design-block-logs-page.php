<?php

class AI_Design_Block_Logs_Page {
    private $logs_per_page = 20;

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_ai_design_block_get_log_details', array($this, 'ajax_get_log_details'));
        add_action('wp_ajax_ai_design_block_delete_log', array($this, 'ajax_delete_log'));
    }

    public function add_admin_menu() {
        add_submenu_page(
            'ai-design-block-settings',
            __('Logs', 'ai-design-block'),
            __('Logs', 'ai-design-block'),
            'manage_options',
            'ai-design-block-logs',
            array($this, 'render_logs_page')
        );
    }

    public function enqueue_scripts($hook) {
        if ('ai-design-block_page_ai-design-block-logs' !== $hook) {
            return;
        }

        wp_enqueue_style('ai-design-block-logs', AI_DESIGN_BLOCK_URL . 'assets/css/logs-page.css', array(), AI_DESIGN_BLOCK_VERSION);
        wp_enqueue_script('ai-design-block-logs', AI_DESIGN_BLOCK_URL . 'assets/js/logs-page.js', array('jquery'), AI_DESIGN_BLOCK_VERSION, true);
        wp_localize_script('ai-design-block-logs', 'aiDesignBlockLogs', array(
            'nonce' => wp_create_nonce('ai_design_block_logs_nonce'),
            'ajaxurl' => admin_url('admin-ajax.php'),
        ));
    }

    public function render_logs_page() {
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $provider = isset($_GET['provider']) ? sanitize_text_field($_GET['provider']) : '';
        $call_type = isset($_GET['call_type']) ? sanitize_text_field($_GET['call_type']) : '';
        $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
        $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';

        $logger = new AI_Design_Block_Logger();
        $logs = $logger->get_logs($current_page, $this->logs_per_page, $provider, $call_type, $date_from, $date_to);
        $total_logs = $logger->get_total_logs($provider, $call_type, $date_from, $date_to);

        $total_pages = ceil($total_logs / $this->logs_per_page);

        include AI_DESIGN_BLOCK_PATH . 'includes/templates/logs-page.php';
    }

    public function ajax_get_log_details() {
        check_ajax_referer('ai_design_block_logs_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $log_id = isset($_POST['log_id']) ? intval($_POST['log_id']) : 0;

        if (!$log_id) {
            wp_send_json_error('Invalid log ID');
        }

        $logger = new AI_Design_Block_Logger();
        $log = $logger->get_log($log_id);

        if (!$log) {
            wp_send_json_error('Log not found');
        }

        ob_start();
        include AI_DESIGN_BLOCK_PATH . 'includes/templates/log-details.php';
        $content = ob_get_clean();

        wp_send_json_success($content);
    }

    public function ajax_delete_log() {
        check_ajax_referer('ai_design_block_logs_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $log_id = isset($_POST['log_id']) ? intval($_POST['log_id']) : 0;

        if (!$log_id) {
            wp_send_json_error('Invalid log ID');
        }

        $logger = new AI_Design_Block_Logger();
        $result = $logger->delete_logs(array($log_id));

        if ($result) {
            wp_send_json_success('Log deleted successfully');
        } else {
            wp_send_json_error('Failed to delete log');
        }
    }
}