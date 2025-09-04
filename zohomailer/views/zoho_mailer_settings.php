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
 
/**
 * Zoho Mailer Settings View
 *
 * This view renders the Zoho Mailer configuration UI inside Perfex CRM.
 * It includes:
 *   - Step 0: API URLs for Zoho Developer Console setup
 *   - Step 1: Zoho API credentials form
 *   - Step 2: Zoho connection/authorization status
 *   - Step 3: Test email form
 *   - Step 4: Enable/disable toggles for Zoho Mailer and fallback
 *
 * Notes:
 *   - Uses Bootstrap tooltips for user guidance.
 *   - Assumes controller passes variables:
 *       $supportedDomains, $client_id, $client_secret,
 *       $from_address, $from_name, $connected, $credentials_saved
 *
 * @package     PerfexCRM
 * @subpackage  ZohoMailer Module
 */

defined('BASEPATH') or exit('No direct script access allowed');
init_head(); ?>

<div id="wrapper">
   <div class="content">
      <div class="row">
         <div class="col-md-12">
             
<!-- ZohoMailer Module Header -->
<div class="text-center" style="padding:25px; background:#f7f8fa; border:1px solid #e5e5e5; border-radius:4px; margin-bottom:20px;">
  <img src="<?= module_dir_url('zohomailer', 'assets/images/logo.png'); ?>" 
       alt="ZohoMailer Logo" height="55" style="margin-bottom:10px;">
  <h3 style="margin:0; font-weight:600;">ZohoMailer for Perfex CRM</h3>
  <p class="text-muted" style="margin:5px 0 0;">
      <strong>Version:</strong> <?= defined('ZOHOMAILER_MODULE_VERSION') ? ZOHOMAILER_MODULE_VERSION : '1.2.1' ?>  
      | <strong>Author:</strong> InWebzer Solutions
  </p>
</div>

<!-- ============================
     Step 0: Zoho API URLs
     ============================ -->
<div class="panel_s">
  <div class="panel-body">
      <h4><span class="label label-primary">Step 0</span> 🌐 Zoho API URLs</h4>
      <p class="text-muted">
          Use these URLs in the <a href="https://api-console.zoho.com/" target="_blank">Zoho Developer Console</a>.
      </p>

      <?php
      // Homepage and redirect URLs required by Zoho OAuth
      $homepage_url = admin_url();
      $redirect_url = admin_url('zohomailer/oauth_callback');
      ?>
      <div class="form-group">
          <label>Homepage URL 
              <i class="fa fa-question-circle" data-toggle="tooltip" title="Set this as the homepage in your Zoho app."></i>
          </label>
          <div class="field-action-group">
              <input type="text" class="form-control" value="<?= html_escape($homepage_url) ?>" readonly onclick="this.select()">
              <button type="button" class="btn btn-default" onclick="copyToClipboard('<?= html_escape($homepage_url) ?>', this)">
                  <i class="fa fa-copy"></i> Copy
              </button>
          </div>
      </div>

      <div class="form-group">
          <label>Authorized Redirect URL 
              <i class="fa fa-question-circle" data-toggle="tooltip" title="Paste this in the Authorized Redirect URIs in Zoho Developer Console."></i>
          </label>
          <div class="field-action-group">
              <input type="text" class="form-control" value="<?= html_escape($redirect_url) ?>" readonly onclick="this.select()">
              <button type="button" class="btn btn-default" onclick="copyToClipboard('<?= html_escape($redirect_url) ?>', this)">
                  <i class="fa fa-copy"></i> Copy
              </button>
          </div>
      </div>
  </div>
</div>

<!-- ============================
     Step 1: API Credentials
     ============================ -->
<div class="panel_s">
  <div class="panel-body">
      <h4><span class="label label-primary">Step 1</span> 🔑 Zoho API Credentials</h4>
      <?php if (!$credentials_saved): ?>
      <p class="text-muted">Enter your Zoho app credentials.</p>
      <?php endif; ?>

      <?php echo form_open(admin_url('zohomailer/settings')); ?>
      <input type="hidden" name="save_settings" value="1">
      
      <?php if ($credentials_saved): ?>
    <div class="alert alert-info">
        Credentials are already saved.  
        To reconfigure, click <strong>Reset Credentials</strong>.  
    </div>
<?php endif; ?>

      <div class="form-group">
          <label>Zoho Domain 
              <i class="fa fa-question-circle" data-toggle="tooltip" title="Select the region where your Zoho account is hosted."></i>
          </label>
          <select name="zoho_domain" class="form-control" required <?= $credentials_saved ? 'disabled' : '' ?>>
              <?php foreach ($supportedDomains as $tld => $label): ?>
                  <option value="<?= $tld ?>" <?= get_option('zoho_domain') === $tld ? 'selected' : '' ?>>
                      <?= $label ?> (<?= $tld ?>)
                  </option>
              <?php endforeach; ?>
          </select>
      </div>

      <div class="form-group">
          <label>Client ID 
              <i class="fa fa-question-circle" data-toggle="tooltip" title="Copy from your Zoho Developer Console application."></i>
          </label>
          <input type="text" name="zoho_client_id" class="form-control" 
                 value="<?= html_escape(substr($client_id, 0, 4) . str_repeat('*', max(0, strlen($client_id) - 8)) . substr($client_id, -4)) ?>" 
                 <?= $credentials_saved ? 'readonly' : '' ?>
                 required>
      </div>

      <div class="form-group">
          <label>Client Secret 
              <i class="fa fa-question-circle" data-toggle="tooltip" title="Generated along with Client ID in Zoho Developer Console."></i>
          </label>
          <input type="text" name="zoho_client_secret" class="form-control" 
                 value="<?= html_escape(substr($client_secret, 0, 4) . str_repeat('*', max(0, strlen($client_secret) - 8)) . substr($client_secret, -4)) ?>" 
                 <?= $credentials_saved ? 'readonly' : '' ?>
                 required>
      </div>

      <div class="form-group">
          <label>From Email 
              <i class="fa fa-question-circle" data-toggle="tooltip" title="Email address verified in Zoho Mail."></i>
          </label>
          <input type="email" name="zoho_from_address" class="form-control" 
                 value="<?= html_escape($from_address) ?>" 
                 <?= $credentials_saved ? 'readonly' : '' ?>
                 required>
      </div>

      <div class="form-group">
          <label>From Name 
              <i class="fa fa-question-circle" data-toggle="tooltip" title="This name will appear as the sender."></i>
          </label>
          <input type="text" name="zoho_from_name" class="form-control" 
                 value="<?= html_escape($from_name) ?>" 
                 <?= $credentials_saved ? 'readonly' : '' ?>
                 required>
      </div>

      <?php if ($credentials_saved): ?>
        <input type="hidden" name="reset_credentials" value="1">
        <button type="submit" class="btn btn-danger">🔄 Reset Credentials</button>
      <?php else: ?>
        <button type="submit" class="btn btn-primary">💾 Save Credentials</button>
      <?php endif; ?>
      <?php echo form_close(); ?>
  </div>
</div>

<!-- ============================
     Step 2: Zoho Connection
     ============================ -->
<div class="panel_s">
  <div class="panel-body">
      <h4><span class="label label-primary">Step 2</span> 🔗 Connect to Zoho</h4>
      <p class="text-muted">Authorize Zoho to generate access and refresh tokens.</p>

      <p>Status: 
          <?php if ($connected) { ?>
              <span class="label label-success"><i class="fa fa-check"></i> Connected</span>
          <?php } else { ?>
              <span class="label label-danger"><i class="fa fa-times"></i> Not Connected</span>
          <?php } ?>
      </p>

      <a href="<?= admin_url('zohomailer/authorize') ?>" class="btn btn-success <?= $credentials_saved ? '' : 'disabled' ?>"
         data-toggle="tooltip" title="<?= $connected ? 'Already connected, Click to re-authorize Zoho and get fresh tokens.' : 'Click to authorize Zoho and fetch tokens.' ?>">
          <?= $connected ? 'Re-Authorize Zoho' : 'Authorize Zoho' ?>
      </a>
  </div>
</div>

<!-- ============================
     Step 3: Test Email
     ============================ -->
<div class="panel_s">
  <div class="panel-body">
      <h4><span class="label label-primary">Step 3</span> ✉️ Send Test Email</h4>

      <?php echo form_open(admin_url('zohomailer/settings')); ?>
      <input type="hidden" name="send_test_email" value="1">

      <div class="form-group">
          <label>Recipient 
              <i class="fa fa-question-circle" data-toggle="tooltip" title="Enter an email address to send a test message."></i>
          </label>
          <div class="field-action-group">
              <input type="email" name="test_email_to" class="form-control" placeholder="recipient@example.com" required>
              <button type="submit" class="btn btn-info" <?= $connected ? '' : 'disabled' ?>>Send Test Email</button>
          </div>
      </div>
      <?php echo form_close(); ?>
  </div>
</div>

<!-- ============================
     Step 4: Enable/Disable Toggles
     ============================ -->
<div class="panel_s">
  <div class="panel-body">
      <h4><span class="label label-primary">Step 4</span> ⚙️ Enable/Disable Zoho Mailer</h4>

      <div class="form-group d-flex align-items-center">
          <label class="toggle-switch mb-0 mr-3 <?= $connected ? '' : 'disabled' ?>">
              <input type="checkbox" id="zoho_enabled_toggle" 
                     name="zoho_enabled" value="1" <?= get_option('zoho_enabled') == '1' ? 'checked' : '' ?>
                     <?= $connected ? '' : 'disabled' ?>>
              <span class="slider"></span>
          </label>
          <div>
              <strong>Enable Zoho Mailer</strong>
              <i class="fa fa-question-circle" data-toggle="tooltip" title="Turn on Zoho Mailer to use it as the primary mailer."></i>
          </div>
      </div>

      <div class="form-group d-flex align-items-center">
          <label class="toggle-switch mb-0 mr-3 <?= get_option('zoho_enabled') == '1' ? '' : 'disabled' ?>">
              <input type="checkbox" id="zoho_fallback_toggle"
                     name="zoho_fallback" value="1" <?= get_option('zoho_fallback') == '1' ? 'checked' : '' ?>
                     <?= get_option('zoho_enabled') == '1' ? '' : 'disabled' ?>>
              <span class="slider"></span>
          </label>
          <div>
              <strong>Fallback to System Mailer</strong>
              <i class="fa fa-question-circle" data-toggle="tooltip" title="If Zoho fails, system mailer will be used."></i>
          </div>
      </div>
  </div>
</div>
      
      </div>
   </div>
</div>
<!-- ZohoMailer Module Footer -->
<div class="text-center text-muted small" style="padding:15px; margin-top:30px; border-top:1px solid #eee;">
  &copy; <?= date('Y'); ?> InWebzer Solutions. All Rights Reserved.  
  <br>
  <a href="<a href="https://github.com/your-username/zohomailer/issues/new?template=support.yml"" target="_blank">Support</a> | 
  <a href="https://github.com/InWebzer/ZohoMailer" target="_blank">Documentation</a> | 
  <a href="https://inwebzer.com/" target="_blank">Website</a>
</div>
<script>
    // Pass AJAX URL for option updates
    var zohoMailer = {
        ajaxUrl: <?= json_encode(admin_url('zohomailer/update_option_ajax')) ?>
    };
</script>


<?php init_tail(); ?>


