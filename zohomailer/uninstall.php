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

/**
 * ZohoMailer Module Uninstaller
 *
 * This script runs automatically when the module is uninstalled
 * from Perfex CRM. Its purpose is to clean up all options created
 * by the ZohoMailer module.
 *
 * Perfex helper function delete_option($name) will:
 * - Remove the option row from the database (if it exists)
 */

$options = [
    // OAuth / API credentials
    'zoho_client_id',       // Zoho OAuth Client ID
    'zoho_client_secret',   // Zoho OAuth Client Secret
    'zoho_from_address',    // Default "From" email address

    // Token storage
    'zoho_access_token',    // Temporary access token
    'zoho_refresh_token',   // Refresh token (long-lived)
    'zoho_account_id',      // Zoho account ID
    'zoho_token_expires',   // Expiry timestamp for access token

    // Mailer settings
    'zoho_from_name',       // Default "From" name
    'zoho_enabled',         // 0 = disabled, 1 = enabled
    'zoho_fallback',        // 0 = no fallback, 1 = fallback to system mailer
    'zoho_domain',          // Default domain (.com = US datacenter)
];

foreach ($options as $opt) {
    if (get_option($opt) !== false) {
        delete_option($opt);
    }
}
