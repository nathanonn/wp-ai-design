<div class="wrap">
    <h1><?php echo esc_html__('AI Design Block Logs', 'ai-design-block'); ?></h1>

    <form method="get">
        <input type="hidden" name="page" value="ai-design-block-logs">
        <div class="tablenav top">
            <div class="alignleft actions">
                <select name="provider">
                    <option value=""><?php echo esc_html__('All Providers', 'ai-design-block'); ?></option>
                    <option value="openai" <?php selected($provider, 'openai'); ?>><?php echo esc_html__('OpenAI', 'ai-design-block'); ?></option>
                    <option value="anthropic" <?php selected($provider, 'anthropic'); ?>><?php echo esc_html__('Anthropic', 'ai-design-block'); ?></option>
                    <option value="gemini" <?php selected($provider, 'gemini'); ?>><?php echo esc_html__('Gemini', 'ai-design-block'); ?></option>
                </select>
                <select name="call_type">
                    <option value=""><?php echo esc_html__('All Call Types', 'ai-design-block'); ?></option>
                    <option value="generate_pattern" <?php selected($call_type, 'generate_pattern'); ?>><?php echo esc_html__('Generate Pattern', 'ai-design-block'); ?></option>
                    <option value="analyze_image" <?php selected($call_type, 'analyze_image'); ?>><?php echo esc_html__('Analyze Image', 'ai-design-block'); ?></option>
                </select>
                <input type="date" name="date_from" value="<?php echo esc_attr($date_from); ?>" placeholder="<?php echo esc_attr__('From', 'ai-design-block'); ?>">
                <input type="date" name="date_to" value="<?php echo esc_attr($date_to); ?>" placeholder="<?php echo esc_attr__('To', 'ai-design-block'); ?>">
                <input type="submit" class="button" value="<?php echo esc_attr__('Filter', 'ai-design-block'); ?>">
            </div>
        </div>
    </form>

    <form method="post">
        <?php wp_nonce_field('ai_design_block_bulk_action', 'ai_design_block_bulk_action_nonce'); ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <td id="cb" class="manage-column column-cb check-column">
                        <input id="cb-select-all-1" type="checkbox">
                    </td>
                    <th scope="col" class="manage-column column-datetime"><?php echo esc_html__('Date/Time', 'ai-design-block'); ?></th>
                    <th scope="col" class="manage-column column-provider"><?php echo esc_html__('Provider', 'ai-design-block'); ?></th>
                    <th scope="col" class="manage-column column-model"><?php echo esc_html__('Model', 'ai-design-block'); ?></th>
                    <th scope="col" class="manage-column column-call-type"><?php echo esc_html__('Call Type', 'ai-design-block'); ?></th>
                    <th scope="col" class="manage-column column-tokens"><?php echo esc_html__('Tokens', 'ai-design-block'); ?></th>
                    <th scope="col" class="manage-column column-user"><?php echo esc_html__('User', 'ai-design-block'); ?></th>
                    <th scope="col" class="manage-column column-actions"><?php echo esc_html__('Actions', 'ai-design-block'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log) : 
                    $user = get_userdata($log->user_id);
                    $username = $user ? $user->user_login : __('Unknown', 'ai-design-block');
                ?>
                    <tr>
                        <th scope="row" class="check-column">
                            <input type="checkbox" name="log_ids[]" value="<?php echo esc_attr($log->id); ?>">
                        </th>
                        <td><?php echo esc_html($log->datetime); ?></td>
                        <td><?php echo esc_html($log->provider); ?></td>
                        <td><?php echo esc_html($log->model); ?></td>
                        <td><?php echo esc_html($log->call_type); ?></td>
                        <td><?php echo esc_html($log->total_tokens); ?></td>
                        <td><?php echo esc_html($username); ?></td>
                        <td>
                            <button type="button" class="button view-details" data-log-id="<?php echo esc_attr($log->id); ?>"><?php echo esc_html__('View Details', 'ai-design-block'); ?></button>
                            <button type="button" class="button delete-log" data-log-id="<?php echo esc_attr($log->id); ?>"><?php echo esc_html__('Delete', 'ai-design-block'); ?></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="tablenav bottom">
            <div class="alignleft actions bulkactions">
                <select name="action">
                    <option value="-1"><?php echo esc_html__('Bulk Actions', 'ai-design-block'); ?></option>
                    <option value="delete"><?php echo esc_html__('Delete', 'ai-design-block'); ?></option>
                </select>
                <input type="submit" class="button action" value="<?php echo esc_attr__('Apply', 'ai-design-block'); ?>">
            </div>
            <div class="tablenav-pages">
                <?php
                echo paginate_links(array(
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => __('&laquo;'),
                    'next_text' => __('&raquo;'),
                    'total' => $total_pages,
                    'current' => $current_page,
                ));
                ?>
            </div>
        </div>
    </form>

    <div id="log-details-modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div id="log-details-content"></div>
        </div>
    </div>
</div>