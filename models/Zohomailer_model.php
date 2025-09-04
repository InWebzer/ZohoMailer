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

class Zohomailer_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('zohomailer/zoho_domain');
    }

    /**
     * Refresh Zoho access token using refresh token
     */
    public function refresh_access_token($client_id, $client_secret, $refresh_token)
    {
        $zohoUrls = getZohoBaseUrls();
        $url = $zohoUrls['accounts'] . '/oauth/v2/token';

        $params = [
            'refresh_token' => $refresh_token,
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
            'grant_type'    => 'refresh_token',
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($params),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error     = curl_error($ch);
        curl_close($ch);

        if ($error) {
            zohomailer_log("cURL error (refresh_access_token): " . $error);
            return false;
        }

        if ($http_code === 200) {
            return json_decode($response, true);
        }

        zohomailer_log("Zoho Token Refresh Failed [HTTP $http_code]: " . $response);
        return false;
    }

    /**
     * Exchange authorization code for access & refresh tokens
     */
    public function get_tokens_from_zoho($client_id, $client_secret, $redirect_uri, $code)
    {
        $zohoUrls = getZohoBaseUrls();
        $url = $zohoUrls['accounts'] . '/oauth/v2/token';

        $post = [
            'grant_type'    => 'authorization_code',
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
            'redirect_uri'  => $redirect_uri,
            'code'          => $code
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS     => http_build_query($post),
            CURLOPT_POST           => true,
        ]);

        $response = curl_exec($ch);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            zohomailer_log("cURL error (get_tokens_from_zoho): " . $error);
            return false;
        }

        $json = json_decode($response, true);
        if (isset($json['error'])) {
            zohomailer_log("Zoho OAuth Token Exchange Failed: " . $response);
        }

        return $json;
    }

    /**
     * Get Zoho Mail account ID using access token
     */
    public function get_account_id($access_token)
    {
        $zohoUrls = getZohoBaseUrls();
        $url = $zohoUrls['mail'] . '/api/accounts';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER     => [
                'Authorization: Zoho-oauthtoken ' . $access_token,
                'Accept: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
        ]);

        $response = curl_exec($ch);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            zohomailer_log("cURL error (get_account_id): " . $error);
            return false;
        }

        $json = json_decode($response, true);

        if (isset($json['data'][0]['accountId'])) {
            return $json['data'][0]['accountId'];
        }

        zohomailer_log("Zoho OAuth: Failed to fetch accountId: " . $response);
        return false;
    }
}
