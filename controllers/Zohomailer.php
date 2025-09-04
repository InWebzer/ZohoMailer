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
 * Admin Controller for Zoho Mailer Integration
 * 
 * Handles:
 * - Settings save
 * - OAuth authorization
 * - Test emails
 * - Enable/disable toggles
 */
class Zohomailer extends AdminController
{

    public function __construct()
    {
        parent::__construct();
        $this->ci =& get_instance();
        $this->ci->load->library('zohomailer/Zoho_mailer');
        $this->ci->load->model('zohomailer/Zohomailer_model');
        $this->ci->load->helper('zohomailer/zoho_domain');

       // $this->Zohomailer_model = $this->ci->Zohomailer_model;
       // $this->Zoho_mailer       = $this->ci->zoho_mailer;
    }

    /**
     * Zoho Mailer Settings page
     */
    public function settings()
    {
        if ($this->input->post('reset_credentials')) {
                // Wipe all saved options
            update_option('zoho_client_id', '');
            update_option('zoho_client_secret', '');
            update_option('zoho_from_address', '');
            update_option('zoho_from_name', '');
            update_option('zoho_access_token', '');
            update_option('zoho_refresh_token', '');
            update_option('zoho_token_expires', '');
            update_option('zoho_account_id', '');
            update_option('zoho_domain', 'com');
            update_option('zoho_enabled', 0);
            update_option('zoho_fallback', 0);

            set_alert('success', 'Zoho credentials have been reset. Please reconfigure.');
            redirect(admin_url('zohomailer/settings'));
            // Save settings form submission
        } elseif ($this->input->post('save_settings')) {
            update_option('zoho_client_id', $this->input->post('zoho_client_id'));
            update_option('zoho_client_secret', $this->input->post('zoho_client_secret'));
            update_option('zoho_from_address', $this->input->post('zoho_from_address'));
            update_option('zoho_from_name', $this->input->post('zoho_from_name'));
            update_option('zoho_domain', $this->input->post('zoho_domain'));

            set_alert('success', 'Zoho credentials saved.');
            redirect(admin_url('zohomailer/settings'));
        }

        // Send test email form submission
        if ($this->input->post('send_test_email')) {
            $to      = $this->input->post('test_email_to');
            $subject = 'Zoho Mail Test';
            $message = '<strong>This is a test email</strong> sent via Zoho Mail API.';

            $email = [
                'to'      => $to,
                'subject' => $subject,
                'message' => $message
            ];

            $success = $this->ci->zoho_mailer->send_via_zoho($email);

            if ($success) {
                set_alert('success', 'Test email sent successfully!');
            } else {
                set_alert('danger', 'Failed to send test email. Check logs.');
            }

            redirect(admin_url('zohomailer/settings'));
        }

        // Prepare view data
        $data = [
            'title'             => 'Zoho Mailer Settings',
            'client_id'         => get_option('zoho_client_id'),
            'client_secret'     => get_option('zoho_client_secret'),
            'from_address'      => get_option('zoho_from_address'),
            'from_name'         => get_option('zoho_from_name'),
            'supportedDomains'  => getZohoSupportedDomains(),
            'connected'         => get_option('zoho_access_token') 
                                   && get_option('zoho_refresh_token') 
                                   && get_option('zoho_account_id'),
            'credentials_saved' => get_option('zoho_client_id') 
                                   && get_option('zoho_client_secret') 
                                   && get_option('zoho_from_address') 
                                   && get_option('zoho_from_name')
        ];

        $this->ci->load->view('zoho_mailer_settings', $data);
    }

    /**
     * Redirects admin to Zoho OAuth page
     */
    public function authorize()
    {
        $client_id    = get_option('zoho_client_id');
        $redirect_uri = admin_url('zohomailer/oauth_callback');
        $scopes       = 'ZohoMail.accounts.READ,ZohoMail.messages.ALL';
        $zohoUrls     = getZohoBaseUrls();

        $auth_url = $zohoUrls['accounts'] . "/oauth/v2/auth?" . http_build_query([
            'scope'         => $scopes,
            'client_id'     => $client_id,
            'response_type' => 'code',
            'access_type'   => 'offline',
            'redirect_uri'  => $redirect_uri,
            'prompt'        => 'consent',
        ]);

        redirect($auth_url);
    }

    /**
     * Zoho OAuth callback handler
     */
    public function oauth_callback()
    {
        $code = $this->input->get('code');
        if (!$code) {
            set_alert('danger', 'Authorization failed: No code parameter found.');
            redirect(admin_url('zohomailer/settings'));
        }

        $client_id     = get_option('zoho_client_id');
        $client_secret = get_option('zoho_client_secret');
        $redirect_uri  = admin_url('zohomailer/oauth_callback');

        $response = $this->ci->Zohomailer_model->get_tokens_from_zoho($client_id, $client_secret, $redirect_uri, $code);

        if (!isset($response['access_token'])) {
            set_alert('danger', 'Failed to exchange authorization code: ' . json_encode($response));
            redirect(admin_url('zohomailer/settings'));
        }

        // Save tokens
        update_option('zoho_access_token', $response['access_token']);
        update_option('zoho_refresh_token', $response['refresh_token']);
        update_option('zoho_token_expires', time() + $response['expires_in'] - 60);

        // Fetch Zoho account ID
        $account_id = $this->ci->Zohomailer_model->get_account_id($response['access_token']);
        if ($account_id) {
            update_option('zoho_account_id', $account_id);
            set_alert('success', 'Zoho OAuth connected successfully.');
        } else {
            set_alert('danger', 'Access token retrieved but failed to fetch Zoho account ID.');
        }

        redirect(admin_url('zohomailer/settings'));
    }

    /**
     * Toggle enable/disable and fallback via AJAX
     */
    public function update_option_ajax()
    {
        if (!$this->input->is_ajax_request()) {
            show_error('No direct access allowed');
        }

        $option = $this->input->post('option');
        $value  = $this->input->post('value');

        if ($option && in_array($option, ['zoho_enabled', 'zoho_fallback'])) {
            update_option($option, $value);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid option']);
        }
    }
}
