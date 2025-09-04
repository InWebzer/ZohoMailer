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

if (!function_exists('getZohoSupportedDomains')) {
    /**
     * Get list of supported Zoho domains.
     *
     * Key = domain TLD (string), Value = region/label (string).
     * Extend this list if Zoho opens new data centers.
     *
     * @return array<string, string>
     */
    function getZohoSupportedDomains()
    {
        return [
            'com'    => 'United States',
            'in'     => 'India',
            'com.au' => 'Australia',
            'uk'     => 'Europe/UK',
            'jp'     => 'Japan',
            'ca'     => 'Canada',
            'sa'     => 'Saudi Arabia',
        ];
    }
}

if (!function_exists('getZohoBaseUrls')) {
    /**
     * Get Zoho base URLs (Accounts + Mail) depending on selected domain.
     *
     * Reads from Perfex option 'zoho_domain' (default = 'com').
     * Falls back to 'com' (US) if domain not supported.
     *
     * @return array{
     *   accounts: string,
     *   mail: string,
     *   domain: string,
     *   region: string
     * }
     */
    function getZohoBaseUrls()
    {
        $domain = 'com'; // default
        try {
            if (function_exists('get_instance')) {
                $CI =& get_instance();
                if (function_exists('get_option')) {
                    $savedDomain = get_option('zoho_domain');
                    if (!empty($savedDomain)) {
                        $domain = strtolower(trim($savedDomain));
                    }
                }
            }
        } catch (Throwable $e) {
            // Log but continue with default .com
            log_message('error', '[ZohoMailer] Failed to read zoho_domain option: ' . $e->getMessage());
        }

        $supported = getZohoSupportedDomains();

        // If unsupported, log & fall back to .com
        if (!array_key_exists($domain, $supported)) {
            log_message('error', "[ZohoMailer] Zoho domain '{$domain}' not supported, falling back to .com");
            $domain = 'com';
        }

        return [
            'accounts' => "https://accounts.zoho.$domain",
            'mail'     => "https://mail.zoho.$domain",
            'domain'   => $domain,
            'region'   => $supported[$domain] ?? 'Unknown',
        ];
    }
}
