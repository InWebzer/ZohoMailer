<?php
/**
 * ZohoMailer for Perfex CRM
 * Copyright (C) 2025  InWebzer Solutions
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: ZohoMailer
Description: Perfex CRM Module to send mail using Zoho API with fallback to system mailer.
Version: 1.1.0
Author: InWebzer Solutions
Author URI: https://inwebzer.com
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Requires PHP: 8.0
Requires Perfex: 3.*
*/
if (!defined('ZOHOMAILER_MODULE_NAME')) {
define('ZOHOMAILER_MODULE_NAME', 'zohomailer');
}

if (!defined('ZOHOMAILER_MODULE_VERSION')) {
    define('ZOHOMAILER_MODULE_VERSION', '1.1.0');
}


/**
 * Add CSS only on ZohoMailer admin pages.
 *
 * Perfex / CodeIgniter hook: app_admin_head
 * We keep echoing markup to match the existing pattern used by other modules.
 */
hooks()->add_action('app_admin_head', function () {
    try {
        // uri_string() may not be available in some contexts, guard defensively
        if (function_exists('uri_string') && strpos(uri_string(), 'admin/zohomailer') === 0) {
            echo '<link rel="stylesheet" type="text/css" href="'
                . module_dir_url('zohomailer', 'assets/css/zoho_mailer.css')
                . '">';
        }
    } catch (Throwable $e) {
        // Log any unexpected errors but do not break page rendering
        log_message('error', '[ZohoMailer] app_admin_head hook error: ' . $e->getMessage());
    }
});

/**
 * Add JS only on ZohoMailer admin pages.
 */
hooks()->add_action('app_admin_footer', function () {
    try {
        if (function_exists('uri_string') && strpos(uri_string(), 'admin/zohomailer') === 0) {
            echo '<script src="'
                . module_dir_url('zohomailer', 'assets/js/zoho_mailer.js')
                . '"></script>';
        }
    } catch (Throwable $e) {
        log_message('error', '[ZohoMailer] app_admin_footer hook error: ' . $e->getMessage());
    }
});

/**
 * Activation hook: set a flag and run installer.
 * register_activation_hook is provided by Perfex modules loader.
 */
register_activation_hook(ZOHOMAILER_MODULE_NAME, 'zohomailer_activate_module');
function zohomailer_activate_module()
{
    try {
        add_option('zohomailer_redirect_after_activation', 1);

        // Installer script should be responsible for adding DB tables/options
        $install_file = __DIR__ . '/install.php';
        if (file_exists($install_file)) {
            require_once $install_file;
        } else {
            log_message('error', '[ZohoMailer] install.php not found during activation.');
        }
    } catch (Throwable $e) {
        log_message('error', '[ZohoMailer] Activation error: ' . $e->getMessage());
    }
}

/**
 * Redirect to settings after activation (one-time).
 */
hooks()->add_action('admin_init', function () {
    try {
        // Only redirect if the option exists and equals 1 (int or string)
        $redirectFlag = get_option('zohomailer_redirect_after_activation');
        if ($redirectFlag && (string) $redirectFlag === '1') {
            // Cleanup then redirect to settings page
            delete_option('zohomailer_redirect_after_activation');

            // Use admin_url() if available; guard with function_exists
            if (function_exists('admin_url')) {
                redirect(admin_url('zohomailer/settings'));
            } else {
                log_message('error', '[ZohoMailer] admin_url() unavailable during post-activation redirect.');
            }
        }
    } catch (Throwable $e) {
        log_message('error', '[ZohoMailer] Post-activation redirect error: ' . $e->getMessage());
    }
});

/**
 * Deactivation hook: remove sensitive options on deactivation.
 * Keep this conservative — removing tokens on deactivate to avoid stale credentials.
 */
register_deactivation_hook(ZOHOMAILER_MODULE_NAME, 'zohomailer_deactivate_module');
function zohomailer_deactivate_module()
{
    try {
        // Remove stored credentials and flags. This is intentional on deactivate.
        delete_option('zoho_enabled'); // 0 = disabled, 1 = enabled
        delete_option('zoho_fallback'); // 0 = no fallback, 1 = fallback to system mailer
    } catch (Throwable $e) {
        log_message('error', '[ZohoMailer] Deactivation error: ' . $e->getMessage());
    }
}

/**
 * Uninstall hook: remove minimal options (called on uninstall).
 * Note: uninstall should be irreversible — keep it conservative or expand as needed.
 */
register_uninstall_hook(ZOHOMAILER_MODULE_NAME, 'zohomailer_uninstall_module');
function zohomailer_uninstall_module()
{
        // Delete all options created by this module
        $options = [
            'zoho_client_id',
            'zoho_client_secret',
            'zoho_from_address',
            'zoho_access_token',
            'zoho_refresh_token',
            'zoho_account_id',
            'zoho_token_expires',
            'zoho_from_name',
            'zoho_enabled',
            'zoho_fallback',
            'zoho_domain',
        ];

    try {
        foreach ($options as $opt) {
            delete_option($opt);
        }
    } catch (Throwable $e) {
        log_message('error', '[ZohoMailer] Uninstall error: ' . $e->getMessage());
    }
}

/**
 * Intercept outgoing email templates when Zoho is enabled.
 *
 * Filter hook: before_email_template_send
 *
 * This function swaps CI's email instance with Mail_interceptor which will
 * forward messages to Zoho API or fallback to native mailer depending on options.
 *
 * @param array|object $data Template data passed by the filter (preserved)
 * @return mixed Passed-through $data (the filter contract)
 */
hooks()->add_filter('before_email_template_send', 'mail_init');
function mail_init($data)
{
    try {
        // Interpret enabled option strictly: only '1' or int 1 enables Zoho.
        $zohoEnabled = get_option('zoho_enabled');
        if ($zohoEnabled && ((string) $zohoEnabled === '1' || (int) $zohoEnabled === 1)) {
            $CI = &get_instance();

            // Save native email instance for fallback (if present)
            $nativeEmail = null;
            if (isset($CI->email)) {
                $nativeEmail = $CI->email;
            }

            // Load the Mail_interceptor library safely
            if (!isset($CI->load)) {
                log_message('error', '[ZohoMailer] CI loader not available when initializing mail interceptor.');
                return $data;
            }

            // Wrap loading with try/catch to avoid fatal errors if library not found
            try {
                $CI->load->library('zohomailer/Mail_interceptor');
            } catch (Throwable $e) {
                log_message('error', '[ZohoMailer] Failed to load Mail_interceptor library: ' . $e->getMessage());
                return $data;
            }

            // Only replace CI->email if the class exists
            if (class_exists('Mail_interceptor')) {
                try {
                    // Mail_interceptor should accept the native email object in constructor
                    $CI->email = new Mail_interceptor($nativeEmail);
                } catch (Throwable $e) {
                    log_message('error', '[ZohoMailer] Error creating Mail_interceptor: ' . $e->getMessage());
                    // restore native email just in case (defensive)
                    if ($nativeEmail !== null) {
                        $CI->email = $nativeEmail;
                    }
                }
            } else {
                log_message('error', '[ZohoMailer] Mail_interceptor class not found after loading library.');
            }
        }
    } catch (Throwable $e) {
        log_message('error', '[ZohoMailer] mail_init filter error: ' . $e->getMessage());
    }

    // Always return the original filter data (do not mutate contract)
    return $data;
}

/**
 * Register Zoho Mailer settings menu in Setup for admin users.
 */
hooks()->add_action('admin_init', 'zohomailer_settings_tab');
function zohomailer_settings_tab()
{
    try {
        if (is_admin()) {
            $CI = &get_instance();

            // Ensure app_menu is available (Perfex provides this)
            if (isset($CI->app_menu) && method_exists($CI->app_menu, 'add_setup_menu_item')) {
                $CI->app_menu->add_setup_menu_item('zohomailer_settings', [
                    'href'     => admin_url('zohomailer/settings'),
                    'name'     => 'ZohoMailer Settings',
                    'position' => 66,
                ]);
            } else {
                log_message('error', '[ZohoMailer] app_menu or add_setup_menu_item unavailable.');
            }
        }
    } catch (Throwable $e) {
        log_message('error', '[ZohoMailer] zohomailer_settings_tab error: ' . $e->getMessage());
    }
}

/**
 * Load module helper (zohomailer_log) if possible.
 * We attempt to load it only when CI instance and loader are present so this
 * file won't cause fatal errors during some early bootstrap phases.
 */
try {
    if (function_exists('get_instance')) {
        $CI = &get_instance();
        if (isset($CI->load)) {
            // Use @ to avoid warnings if helper missing, but log if not found
            try {
                $CI->load->helper('zohomailer/zohomailer_log');
            } catch (Throwable $e) {
                log_message('error', '[ZohoMailer] Failed to load helper zohomailer/zohomailer_log: ' . $e->getMessage());
            }
        } else {
            log_message('error', '[ZohoMailer] CI loader not available for helper load.');
        }
    } else {
        log_message('error', '[ZohoMailer] get_instance() not available - cannot load zohomailer helper.');
    }
} catch (Throwable $e) {
    log_message('error', '[ZohoMailer] Unexpected error while loading helper: ' . $e->getMessage());
}

/**
 * Add "Settings" link for this module only (if the per-module hook is available).
 *
 * Some Perfex builds provide a dedicated filter for each module row in the Modules page.
 * The filter name is dynamically generated: module_{system_name}_action_links
 *
 * Example: For ZOHOMAILER_MODULE_NAME = "zohomailer", the filter will be:
 *          module_zohomailer_action_links
 *
 * @hook module_{system_name}_action_links
 *
 * @param array $actions Existing action links for this module (Deactivate, etc.)
 *
 * @return array Modified list of action links including our Settings link
 */
hooks()->add_filter('module_' . ZOHOMAILER_MODULE_NAME . '_action_links', function ($actions) {
    $actions[] = '<a href="' . admin_url('zohomailer/settings') . '">' . _l('settings') . '</a>';
    return $actions;
}, 10, 1);

