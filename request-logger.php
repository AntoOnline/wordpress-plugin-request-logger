<?php
/*
Plugin Name: Request Logger
Plugin URI: https://anto.online
Description: Logs headers from requests to the server
Version: 1.0
Author: Anto Online
Author URI: https://anto.online
*/

// Register the settings page
function request_logger_register_settings()
{
    register_setting('request_logger_settings', 'request-logger-enable');
}
add_action('admin_init', 'request_logger_register_settings');

// Add the settings page to the Tools menu
function request_logger_add_settings_page()
{
    add_submenu_page(
        'tools.php',
        'Request Logger Settings',
        'Request Logger',
        'manage_options',
        'request-logger-settings',
        'request_logger_settings_page'
    );
}
add_action('admin_menu', 'request_logger_add_settings_page');

// Generate the settings page HTML
function request_logger_settings_page()
{
?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('request_logger_settings');
            do_settings_sections('request_logger_settings');
            ?>
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row"><?php _e('Enable logging', 'request-logger'); ?></th>
                        <td>
                            <label for="request-logger-enable">
                                <input type="checkbox" name="request-logger-enable" id="request-logger-enable" value="1" <?php checked(get_option('request-logger-enable'), 1); ?> />
                                <?php _e('Log request headers', 'request-logger'); ?>
                            </label>
                        </td>
                    </tr>
                </tbody>
            </table>
            <?php submit_button(); ?>
        </form>

        <hr>

        <h2><?php _e('Request Logger Help', 'request-logger'); ?></h2>
        <p><?php _e('This plugin logs headers from requests to the server. The logs are stored in the following directory:', 'request-logger'); ?></p>
        <p><code><?php echo plugin_dir_path(__FILE__) . 'logs/'; ?></code></p>
        <p><?php _e('To view the contents of a log file, download it and open it in a text editor.', 'request-logger'); ?></p>
        <p><?php _e('Remember to disable the plugin when you are finished reviewing the logs.', 'request-logger'); ?></p>
    </div>
<?php
}



//
// Log the request headers
function log_request_headers()
{
    if (get_option('request-logger-enable', 0)) {
        $headers = getallheaders();
        $ip = $_SERVER['REMOTE_ADDR'];
        $log_message = serialize(array(
            'date' => date('Y-m-d H:i:s'),
            'ip' => $ip,
            'headers' => $headers
        ));

        // Create the logs directory if it doesn't exist
        $logs_dir = plugin_dir_path(__FILE__) . 'logs/';
        if (!file_exists($logs_dir)) {
            mkdir($logs_dir, 0755, true);
        }

        // Write the log message to a file
        $log_file = $logs_dir . 'request-log-' . date('Y-m-d') . '.log';
        file_put_contents($log_file, $log_message . "\n", FILE_APPEND | LOCK_EX);

        // Disable logging after 30 minutes if auto-disable is enabled
        if (get_option('request-logger-auto-disable', 0)) {
            $disable_time = strtotime('+30 minutes');
            update_option('request-logger-enable', 0, false);
            wp_schedule_single_event($disable_time, 'request_logger_disable_logging');
        }
    }
}
add_action('init', 'log_request_headers');
