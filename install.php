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
 * ZohoMailer Module Installer
 *
 * This script runs on module activation and ensures required options
 * are created in the database. If options already exist, they will not
 * be overridden (to preserve user configuration).
 *
 * Perfex helper function add_option($name, $value) will:
 * - Create the option if not present
 * - Leave it unchanged if already exists
 */

// OAuth / API credentials
add_option('zoho_client_id', '');          // Zoho OAuth Client ID
add_option('zoho_client_secret', '');      // Zoho OAuth Client Secret
add_option('zoho_from_address', '');       // Default "From" email address

// Token storage
add_option('zoho_access_token', '');       // Temporary access token
add_option('zoho_refresh_token', '');      // Refresh token (long-lived)
add_option('zoho_account_id', '');         // Zoho account ID
add_option('zoho_token_expires', '');      // Expiry timestamp for access token

// Mailer settings
add_option('zoho_from_name', '');          // Default "From" name
add_option('zoho_enabled', 0);             // 0 = disabled, 1 = enabled
add_option('zoho_fallback', 0);            // 0 = no fallback, 1 = fallback to system mailer
add_option('zoho_domain', 'com');          // Default domain (.com = US datacenter)
