<?php

class AI_Design_Block_Logger {
    private static $table_name = 'ai_design_block_api_logs';
    private static $enabled_option_name = 'ai_design_block_logger_enabled';

    public static function get_table_name() {
        return self::$table_name;
    }

    public static function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . self::$table_name;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            call_type TEXT CHECK( call_type IN ('generate_pattern', 'analyze_image') ) NOT NULL,
            datetime DATETIME NOT NULL,
            provider VARCHAR(50) NOT NULL,
            model VARCHAR(100) NOT NULL,
            input TEXT NOT NULL,
            image_url TEXT,
            output TEXT NOT NULL,
            input_tokens INTEGER UNSIGNED NOT NULL,
            output_tokens INTEGER UNSIGNED NOT NULL,
            total_tokens INTEGER UNSIGNED NOT NULL,
            user_id INTEGER UNSIGNED NOT NULL
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        return dbDelta( $sql );
    }

    public static function drop_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . self::$table_name;
        return $wpdb->query("DROP TABLE IF EXISTS $table_name");
    }

    public static function is_enabled() {
        return get_option(self::$enabled_option_name, true); // Default to enabled
    }

    public static function set_enabled($enabled) {
        update_option(self::$enabled_option_name, $enabled);
    }

    public static function log_api_call($data) {
        if (!self::is_enabled()) {
            return; // Don't log if logger is disabled
        }

        global $wpdb;
        $table_name = $wpdb->prefix . self::$table_name;

        $sanitized_data = array(
            'call_type'     => sanitize_text_field( $data['call_type'] ),
            'datetime'      => current_time( 'mysql' ),
            'provider'      => sanitize_text_field( $data['provider'] ),
            'model'         => sanitize_text_field( $data['model'] ),
            'input'         => wp_kses_post( $data['input'] ),
            'image_url'     => esc_url_raw( $data['image_url'] ),
            'output'        => wp_json_encode( $data['output'] ),
            'input_tokens'  => absint( $data['input_tokens'] ),
            'output_tokens' => absint( $data['output_tokens'] ),
            'total_tokens'  => absint( $data['total_tokens'] ),
            'user_id'       => get_current_user_id(),
        );

        $result = $wpdb->insert(
            $table_name,
            $sanitized_data,
            array(
                '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d'
            )
        );

        if ( false === $result ) {
            error_log( 'Failed to log API call: ' . $wpdb->last_error );
        }
    }

    public static function get_logs($page = 1, $per_page = 20, $provider = '', $call_type = '', $start_date = '', $end_date = '') {
        global $wpdb;
        $table_name = $wpdb->prefix . self::$table_name;

        $offset = ($page - 1) * $per_page;

        $where = array();
        if (!empty($provider)) {
            $where[] = $wpdb->prepare("provider = %s", $provider);
        }
        if (!empty($call_type)) {
            $where[] = $wpdb->prepare("call_type = %s", $call_type);
        }
        if (!empty($start_date)) {
            $where[] = $wpdb->prepare("datetime >= %s", $start_date);
        }
        if (!empty($end_date)) {
            $where[] = $wpdb->prepare("datetime <= %s", $end_date);
        }

        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $query = $wpdb->prepare(
            "SELECT * FROM $table_name
            $where_clause
            ORDER BY id DESC
            LIMIT %d OFFSET %d",
            $per_page,
            $offset
        );

        return $wpdb->get_results($query);
    }

    public static function delete_log($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::$table_name;

        return $wpdb->delete($table_name, array('id' => $id), array('%d'));
    }

    public static function delete_logs($ids) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::$table_name;

        $ids = array_map('intval', $ids);
        $id_list = implode(',', $ids);

        return $wpdb->query("DELETE FROM $table_name WHERE id IN ($id_list)");
    }

    public static function get_total_logs($provider = '', $call_type = '', $start_date = '', $end_date = '') {
        global $wpdb;
        $table_name = $wpdb->prefix . self::$table_name;

        $where = array();
        if (!empty($provider)) {
            $where[] = $wpdb->prepare("provider = %s", $provider);
        }
        if (!empty($call_type)) {
            $where[] = $wpdb->prepare("call_type = %s", $call_type);
        }
        if (!empty($start_date)) {
            $where[] = $wpdb->prepare("datetime >= %s", $start_date);
        }
        if (!empty($end_date)) {
            $where[] = $wpdb->prepare("datetime <= %s", $end_date);
        }

        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $query = "SELECT COUNT(*) FROM $table_name $where_clause";
        return $wpdb->get_var($query);
    }

    public static function get_log($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::$table_name;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
    }

    public function table_exists() {
        global $wpdb;
        $table_name = $wpdb->prefix . self::$table_name;
        return $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    }

    public function clear_logs() {
        global $wpdb;
        $table_name = $wpdb->prefix . self::$table_name;
        return $wpdb->query("TRUNCATE TABLE $table_name");
    }
}