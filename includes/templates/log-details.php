<?php
$user = get_userdata($log->user_id);
$username = $user ? $user->user_login : __('Unknown', 'ai-design-block');
?>
<h2><?php echo esc_html__('Log Details', 'ai-design-block'); ?></h2>
<p><strong><?php echo esc_html__('Date/Time:', 'ai-design-block'); ?></strong> <?php echo esc_html($log->datetime); ?></p>
<p><strong><?php echo esc_html__('Provider:', 'ai-design-block'); ?></strong> <?php echo esc_html($log->provider); ?></p>
<p><strong><?php echo esc_html__('Model:', 'ai-design-block'); ?></strong> <?php echo esc_html($log->model); ?></p>
<p><strong><?php echo esc_html__('Call Type:', 'ai-design-block'); ?></strong> <?php echo esc_html($log->call_type); ?></p>
<p><strong><?php echo esc_html__('User:', 'ai-design-block'); ?></strong> <?php echo esc_html($username); ?></p>
<p><strong><?php echo esc_html__('Input:', 'ai-design-block'); ?></strong> <?php echo esc_html($log->input); ?></p>
<p><strong><?php echo esc_html__('Output:', 'ai-design-block'); ?></strong> <?php echo esc_html($log->output); ?></p>
<p><strong><?php echo esc_html__('Input Tokens:', 'ai-design-block'); ?></strong> <?php echo esc_html($log->input_tokens); ?></p>
<p><strong><?php echo esc_html__('Output Tokens:', 'ai-design-block'); ?></strong> <?php echo esc_html($log->output_tokens); ?></p>
<p><strong><?php echo esc_html__('Total Tokens:', 'ai-design-block'); ?></strong> <?php echo esc_html($log->total_tokens); ?></p>