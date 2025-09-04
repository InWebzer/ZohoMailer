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
 * Zoho Mailer Library
 *
 * Handles sending emails via Zoho Mail API with support for:
 *  - Token refresh
 *  - Inline and file attachments
 *  - Robust error handling & logging
 *
 * @package     Perfex CRM
 * @subpackage  Zoho Mailer Module
 * @author      
 */
class Zoho_mailer
{
    /**
     * @var CI_Controller Reference to CodeIgniter instance
     */
    protected $ci;

    public function __construct()
    {
        $this->ci =& get_instance();
        $this->ci->load->model('zohomailer/Zohomailer_model', 'zohomodel');
        $this->ci->load->helper('zohomailer/zoho_domain');
    }

    /**
     * Send email via Zoho Mail API
     *
     * @param array $email {
     *     Email payload
     *
     *     @type string       $to         Recipient(s), string or array
     *     @type string|array $cc         CC recipients (optional)
     *     @type string|array $bcc        BCC recipients (optional)
     *     @type string       $reply_to   Reply-To address (optional)
     *     @type string       $subject    Subject of the email
     *     @type string       $message    HTML body
     *     @type array        $attachments Array of attachments [
     *         'path' => (string) full path,
     *         'disposition' => (string) 'inline' or 'attachment'
     *     ]
     * }
     *
     * @return bool True on success, false on failure
     */
    public function send_via_zoho(array $email): bool
    {
        $zohoUrls      = getZohoBaseUrls();
        $account_id    = get_option('zoho_account_id');
        $access_token  = get_option('zoho_access_token');
        $refresh_token = get_option('zoho_refresh_token');
        $client_id     = get_option('zoho_client_id');
        $client_secret = get_option('zoho_client_secret');
        $token_expires = (int) get_option('zoho_token_expires');
        $from_address  = get_option('zoho_from_address');
        $from_name     = get_option('zoho_from_name');

        // Validate configuration
        if (!$access_token || !$refresh_token || !$client_id || !$client_secret || !$from_address) {
            zohomailer_log('Zoho Mail: Missing configuration values.');
            return false;
        }

        // Build "From" string
        $from = sprintf('"%s" <%s>', addslashes($from_name), $from_address);

        // Refresh token if expired or about to expire (5 mins buffer)
        if (time() >= ($token_expires - 300)) {
            $new_token = $this->ci->zohomodel->refresh_access_token($client_id, $client_secret, $refresh_token);
            if (!empty($new_token['access_token'])) {
                $access_token = $new_token['access_token'];
                update_option('zoho_access_token', $access_token);
                update_option('zoho_token_expires', time() + (int)$new_token['expires_in']);
            } else {
                zohomailer_log('Zoho token refresh failed: ' . json_encode($new_token));
                return false;
            }
        }

        // --- Handle attachments ---
        $attachmentsMeta = [];
        if (!empty($email['attachments'])) {
            foreach ($email['attachments'] as $att) {
                $file_path = $att['path'] ?? null;
                $isInline  = strtolower($att['disposition'] ?? '') === 'inline';

                if ($file_path && file_exists($file_path)) {
                    $uploadResp = $this->upload_attachment($account_id, $access_token, $file_path, $isInline);
                    if (!empty($uploadResp['success'])) {
                        $attachmentsMeta[] = $uploadResp['data'];
                    } else {
                        zohomailer_log("Attachment upload failed for file {$file_path}: " . json_encode($uploadResp));
                    }
                } else {
                    zohomailer_log("Attachment file not found: {$file_path}");
                }
            }
        }

        // --- Build email payload ---
        $payload = array_filter([
            'fromAddress' => $from,
            'toAddress'   => is_array($email['to']) ? implode(',', $email['to']) : $email['to'],
            'ccAddress'   => !empty($email['cc']) ? (is_array($email['cc']) ? implode(',', $email['cc']) : $email['cc']) : null,
            'bccAddress'  => !empty($email['bcc']) ? (is_array($email['bcc']) ? implode(',', $email['bcc']) : $email['bcc']) : null,
            'replyTo'     => $email['reply_to'] ?? null,
            'subject'     => $email['subject'] ?? '(No subject)',
            'content'     => $email['message'] ?? '',
            'mailFormat'  => 'html',
            'attachments' => !empty($attachmentsMeta) ? $attachmentsMeta : null,
        ]);

        // --- Send email ---
        $url = $zohoUrls['mail'] . "/api/accounts/{$account_id}/messages";
        $headers = [
            "Authorization: Zoho-oauthtoken $access_token",
            "Content-Type: application/json"
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POSTFIELDS     => json_encode($payload)
        ]);

        $response   = curl_exec($ch);
        $http_code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error      = curl_error($ch);
        curl_close($ch);

        if (in_array($http_code, [200, 202], true)) {
            return true;
        }

        zohomailer_log('Zoho Mail send failed: ' . $response . ' | HTTP: ' . $http_code . ' | cURL Error: ' . $error);
        return false;
    }

    /**
     * Upload attachment to Zoho
     *
     * @param string $account_id
     * @param string $access_token
     * @param string $file_path
     * @param bool   $isInline
     *
     * @return array { success: bool, data?: array, http_code?: int, body?: string, error?: string }
     */
    private function upload_attachment(string $account_id, string $access_token, string $file_path, bool $isInline = false): array
    {
        $zohoUrls = getZohoBaseUrls();
        $inlineParam = $isInline ? 'true' : 'false';
        $url = $zohoUrls['mail'] . "/api/accounts/$account_id/messages/attachments?uploadType=multipart&isInline=$inlineParam";

        $cfile = new CURLFile($file_path, mime_content_type($file_path), basename($file_path));

        $headers = [
            "Authorization: Zoho-oauthtoken $access_token"
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POSTFIELDS     => ['attach' => $cfile],
        ]);

        $response   = curl_exec($ch);
        $http_code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error      = curl_error($ch);
        curl_close($ch);

        if (in_array($http_code, [200, 201], true)) {
            $respArr = json_decode($response, true);
            if (!empty($respArr['data'][0])) {
                return ['success' => true, 'data' => $respArr['data'][0]];
            }
        }

        return [
            'success'   => false,
            'http_code' => $http_code,
            'body'      => $response,
            'error'     => $error
        ];
    }
}
