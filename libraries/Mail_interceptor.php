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

if (!class_exists('Mail_interceptor')) {
    /**
     * Mail_interceptor
     *
     * Intercepts calls that would normally go to CI_Email so we can capture the
     * full message (recipients, subject, body, attachments) and forward it to
     * Zoho via the Zoho_mailer library. If Zoho sending fails and fallback is
     * enabled, the intercepted data is forwarded to the native CI_Email sender.
     *
     * Public API matches commonly-used CI_Email methods so it can be used as a drop-in.
     */
    class Mail_interceptor
    {
        /**
         * @var CI_Controller
         */
        protected $ci;

        /**
         * The native CI Email instance used for fallback and compatibility.
         * @var CI_Email|null
         */
        protected $nativeEmail = null;

        /**
         * Captured email payload
         * @var array
         */
        public $captured = [
            'from'        => null,
            'to'          => [],
            'cc'          => [],
            'bcc'         => [],
            'reply_to'    => null,
            'subject'     => null,
            'message'     => null,
            'attachments' => [],
        ];

        /**
         * Mail_interceptor constructor.
         *
         * @param mixed $nativeEmail Optional: existing CI_Email instance (or object compatible with CI_Email methods).
         */
        public function __construct($nativeEmail = null)
        {
            $this->ci =& get_instance();

            // Load module logging helper (safe to call even if missing)
            if (function_exists('zohomailer_log')) {
                // nothing to do - helper will be used directly
            } else {
                // attempt to load helper if available (best-effort)
                try {
                    if (isset($this->ci->load)) {
                        $this->ci->load->helper('zohomailer/zohomailer_log');
                    }
                } catch (Throwable $e) {
                    // swallow: logging helper is optional here; fallback to CI logs is avoided by design
                }
            }

            // Ensure Zoho mailer library is available, but don't fatal if missing
            try {
                if (isset($this->ci->load)) {
                    $this->ci->load->library('zohomailer/Zoho_mailer');
                }
            } catch (Throwable $e) {
                // Use module logger if present, else fallback to CI logger
                if (function_exists('zohomailer_log')) {
                    zohomailer_log('Failed to load Zoho_mailer library: ' . $e->getMessage());
                } else {
                    log_message('error', '[ZohoMailer] Failed to load Zoho_mailer library: ' . $e->getMessage());
                }
            }

            // Use provided nativeEmail or try to use existing $this->ci->email
            if ($nativeEmail) {
                $this->nativeEmail = $nativeEmail;
            } elseif (isset($this->ci->email)) {
                $this->nativeEmail = $this->ci->email;
            } else {
                // Ensure CI Email library is loaded and set nativeEmail to CI's instance
                try {
                    $this->ci->load->library('email');
                    $this->nativeEmail = $this->ci->email ?? null;
                } catch (Throwable $e) {
                    $this->nativeEmail = null;
                    if (function_exists('zohomailer_log')) {
                        zohomailer_log('Failed to load native CI Email library: ' . $e->getMessage());
                    } else {
                        log_message('error', '[ZohoMailer] Failed to load native CI Email library: ' . $e->getMessage());
                    }
                }
            }
        }

        /**
         * Reset captured payload and clear native email if available.
         *
         * @param bool $resetNative True to reset native CI email instance state (if available)
         * @return void
         */
        public function clear($resetNative = false)
        {
            $this->captured = [
                'from'        => null,
                'to'          => [],
                'cc'          => [],
                'bcc'         => [],
                'reply_to'    => null,
                'subject'     => null,
                'message'     => null,
                'attachments' => [],
            ];

            if ($resetNative && $this->nativeEmail && method_exists($this->nativeEmail, 'clear')) {
                try {
                    $this->nativeEmail->clear(true);
                } catch (Throwable $e) {
                    if (function_exists('zohomailer_log')) {
                        zohomailer_log('NativeEmail::clear failed: ' . $e->getMessage());
                    }
                }
            }
        }

        /**
         * Proxy set_newline to native email (if available).
         *
         * @param string $newline
         * @return void
         */
        public function set_newline($newline)
        {
            if ($this->nativeEmail && method_exists($this->nativeEmail, 'set_newline')) {
                $this->nativeEmail->set_newline($newline);
            }
        }

        /**
         * Set From address.
         *
         * @param string $email
         * @param string $name
         * @return $this
         */
        public function from($email, $name = '')
        {
            $this->captured['from'] = $name ? "{$name} <{$email}>" : $email;

            if ($this->nativeEmail && method_exists($this->nativeEmail, 'from')) {
                try {
                    $this->nativeEmail->from($email, $name);
                } catch (Throwable $e) {
                    if (function_exists('zohomailer_log')) {
                        zohomailer_log('NativeEmail::from failed: ' . $e->getMessage());
                    }
                }
            }

            return $this;
        }

        /**
         * Set subject.
         *
         * @param string $subject
         * @return $this
         */
        public function subject($subject)
        {
            $this->captured['subject'] = $subject;
            if ($this->nativeEmail && method_exists($this->nativeEmail, 'subject')) {
                try {
                    $this->nativeEmail->subject($subject);
                } catch (Throwable $e) {
                    if (function_exists('zohomailer_log')) {
                        zohomailer_log('NativeEmail::subject failed: ' . $e->getMessage());
                    }
                }
            }
            return $this;
        }

        /**
         * Set message (HTML/body).
         *
         * @param string $message
         * @return $this
         */
        public function message($message)
        {
            $this->captured['message'] = $message;
            if ($this->nativeEmail && method_exists($this->nativeEmail, 'message')) {
                try {
                    $this->nativeEmail->message($message);
                } catch (Throwable $e) {
                    if (function_exists('zohomailer_log')) {
                        zohomailer_log('NativeEmail::message failed: ' . $e->getMessage());
                    }
                }
            }
            return $this;
        }

        /**
         * Set To recipients.
         *
         * @param string|array $to
         * @return $this
         */
        public function to($to)
        {
            $this->captured['to'] = is_array($to) ? array_values($to) : [$to];
            if ($this->nativeEmail && method_exists($this->nativeEmail, 'to')) {
                try {
                    $this->nativeEmail->to($to);
                } catch (Throwable $e) {
                    if (function_exists('zohomailer_log')) {
                        zohomailer_log('NativeEmail::to failed: ' . $e->getMessage());
                    }
                }
            }
            return $this;
        }

        /**
         * Set CC recipients.
         *
         * @param string|array $cc
         * @return $this
         */
        public function cc($cc)
        {
            $this->captured['cc'] = is_array($cc) ? array_values($cc) : [$cc];
            if ($this->nativeEmail && method_exists($this->nativeEmail, 'cc')) {
                try {
                    $this->nativeEmail->cc($cc);
                } catch (Throwable $e) {
                    if (function_exists('zohomailer_log')) {
                        zohomailer_log('NativeEmail::cc failed: ' . $e->getMessage());
                    }
                }
            }
            return $this;
        }

        /**
         * Set BCC recipients.
         *
         * @param string|array $bcc
         * @return $this
         */
        public function bcc($bcc)
        {
            $this->captured['bcc'] = is_array($bcc) ? array_values($bcc) : [$bcc];
            if ($this->nativeEmail && method_exists($this->nativeEmail, 'bcc')) {
                try {
                    $this->nativeEmail->bcc($bcc);
                } catch (Throwable $e) {
                    if (function_exists('zohomailer_log')) {
                        zohomailer_log('NativeEmail::bcc failed: ' . $e->getMessage());
                    }
                }
            }
            return $this;
        }

        /**
         * Set Reply-To header.
         *
         * @param string $reply
         * @return $this
         */
        public function reply_to($reply)
        {
            $this->captured['reply_to'] = $reply;
            if ($this->nativeEmail && method_exists($this->nativeEmail, 'reply_to')) {
                try {
                    $this->nativeEmail->reply_to($reply);
                } catch (Throwable $e) {
                    if (function_exists('zohomailer_log')) {
                        zohomailer_log('NativeEmail::reply_to failed: ' . $e->getMessage());
                    }
                }
            }
            return $this;
        }

        /**
         * Attach files. Supports:
         *  - string path to file
         *  - raw string content (will be written to temp file)
         *  - array with keys ['attachment' => pathOrContent, 'filename' => ..., 'type' => ...]
         *
         * This method will forward the attachment to native CI_Email (if available)
         * and also capture metadata for Zoho upload. Temporary files created for
         * buffered content are tracked and removed in cleanupTempFiles().
         *
         * @param string|array $file       Path, raw content, or array-style attachment
         * @param string       $disposition
         * @param string|null  $newname
         * @param string       $mime
         * @return $this
         */
        public function attach($file, $disposition = '', $newname = null, $mime = '')
        {
            // Try to forward to native email library (best-effort)
            if ($this->nativeEmail && method_exists($this->nativeEmail, 'attach')) {
                try {
                    // For array-format attachments, CI_Email expects array or path; forward original param
                    $this->nativeEmail->attach($file, $disposition, $newname, $mime);
                } catch (Throwable $e) {
                    if (function_exists('zohomailer_log')) {
                        zohomailer_log('NativeEmail::attach failed: ' . $e->getMessage());
                    }
                }
            }

            // Normalize and capture attachment(s)
            // Case: array style as used by App_mail_template -> ['attachment' => ..., 'filename' => ..., 'type' => ...]
            if (is_array($file) && isset($file['attachment'])) {
                $pathOrContent = $file['attachment'];
                $filename      = $file['filename'] ?? $newname ?? basename($pathOrContent);
                $type          = $file['type'] ?? $mime ?? 'application/octet-stream';
                $this->captureAttachment($pathOrContent, $filename, $type, $disposition);
                return $this;
            }

            // Case: string likely a path to file
            if (is_string($file)) {
                // If file exists on disk, capture path
                if (file_exists($file)) {
                    $this->captured['attachments'][] = [
                        'path'        => $file,
                        'name'        => $newname ?: basename($file),
                        'type'        => $mime ?: $this->guessMimeType($file),
                        'disposition' => empty($disposition) ? 'attachment' : $disposition,
                        'source'      => 'path',
                    ];
                    return $this;
                }

                // If string doesn't refer to an existing path, treat as raw content (buffer)
                $tmpName = sys_get_temp_dir() . '/zohomailer_' . uniqid() . '_' . ($newname ?: 'attachment');
                $written = false;
                try {
                    $written = file_put_contents($tmpName, $file);
                } catch (Throwable $e) {
                    $written = false;
                }

                if ($written === false) {
                    if (function_exists('zohomailer_log')) {
                        zohomailer_log('Failed to write buffered attachment to temp file: ' . $tmpName);
                    }
                    return $this;
                }

                $this->captured['attachments'][] = [
                    'path'        => $tmpName,
                    'name'        => $newname ?: basename($tmpName),
                    'type'        => $mime ?: 'application/octet-stream',
                    'disposition' => empty($disposition) ? 'attachment' : $disposition,
                    'source'      => 'buffer',
                ];

                return $this;
            }

            // Unsupported type
            if (function_exists('zohomailer_log')) {
                zohomailer_log('Unsupported attachment type passed to Mail_interceptor::attach');
            }

            return $this;
        }

        /**
         * Helper to normalize capture of an attachment element.
         *
         * @param string $pathOrContent
         * @param string $filename
         * @param string $type
         * @param string $disposition
         * @return void
         */
        protected function captureAttachment($pathOrContent, $filename, $type = 'application/octet-stream', $disposition = '')
        {
            // If path exists, use it directly
            if (is_string($pathOrContent) && file_exists($pathOrContent)) {
                $this->captured['attachments'][] = [
                    'path'        => $pathOrContent,
                    'name'        => $filename ?: basename($pathOrContent),
                    'type'        => $type ?: $this->guessMimeType($pathOrContent),
                    'disposition' => empty($disposition) ? 'attachment' : $disposition,
                    'source'      => 'path',
                ];
                return;
            }

            // Treat as buffered content and write to temp file
            $tmp = sys_get_temp_dir() . '/zohomailer_' . uniqid() . '_' . ($filename ?: 'attachment');
            $ok  = false;
            try {
                $ok = file_put_contents($tmp, $pathOrContent);
            } catch (Throwable $e) {
                $ok = false;
            }

            if ($ok === false) {
                if (function_exists('zohomailer_log')) {
                    zohomailer_log('Failed to write attachment buffer to temp file: ' . $tmp);
                }
                return;
            }

            $this->captured['attachments'][] = [
                'path'        => $tmp,
                'name'        => $filename ?: basename($tmp),
                'type'        => $type ?: 'application/octet-stream',
                'disposition' => empty($disposition) ? 'attachment' : $disposition,
                'source'      => 'buffer',
            ];
        }

        /**
         * Try to guess mime type for a file path (best-effort).
         *
         * @param string $path
         * @return string
         */
        protected function guessMimeType($path)
        {
            if (function_exists('mime_content_type')) {
                try {
                    $m = mime_content_type($path);
                    if ($m) {
                        return $m;
                    }
                } catch (Throwable $e) {
                    // ignore and fallback
                }
            }

            // Fallback common binary
            return 'application/octet-stream';
        }

        /**
         * Set alternative plain-text message (proxy to CI Email).
         *
         * @param string $alt_message
         * @return void
         */
        public function set_alt_message($alt_message)
        {
            if ($this->nativeEmail && method_exists($this->nativeEmail, 'set_alt_message')) {
                try {
                    $this->nativeEmail->set_alt_message($alt_message);
                } catch (Throwable $e) {
                    if (function_exists('zohomailer_log')) {
                        zohomailer_log('NativeEmail::set_alt_message failed: ' . $e->getMessage());
                    }
                }
            }
        }

        /**
         * Print native debugger (proxy).
         *
         * @return string
         */
        public function print_debugger()
        {
            if ($this->nativeEmail && method_exists($this->nativeEmail, 'print_debugger')) {
                try {
                    return $this->nativeEmail->print_debugger();
                } catch (Throwable $e) {
                    if (function_exists('zohomailer_log')) {
                        zohomailer_log('NativeEmail::print_debugger failed: ' . $e->getMessage());
                    }
                }
            }

            return 'ZohoMailer interceptor - no native debugger available.';
        }

        /**
         * Send the captured email via Zoho. If Zoho fails and fallback is enabled,
         * send using the native CI email implementation.
         *
         * @param bool $autoClear Whether to auto-clear captured/natives after send.
         * @return bool True on successful send (Zoho or native fallback), false otherwise.
         */
        public function send($autoClear = true)
        {
            $fallbackEnabled = (int) get_option('zoho_fallback') === 1;
            $sent = false;

            try {
                // Ensure captured has minimal required data
                if (empty($this->captured['to']) && empty($this->captured['cc']) && empty($this->captured['bcc'])) {
                    if (function_exists('zohomailer_log')) {
                        zohomailer_log('Attempted to send email with no recipients.');
                    }
                    // don't attempt Zoho send if no recipients
                    $sent = false;
                } else {
                    // Attempt Zoho send via Zoho_mailer library (expected to be loaded as $this->ci->zoho_mailer)
                    if (isset($this->ci->zoho_mailer) && method_exists($this->ci->zoho_mailer, 'send_via_zoho')) {
                        $result = null;
                        try {
                            $result = $this->ci->zoho_mailer->send_via_zoho($this->captured);
                        } catch (Throwable $e) {
                            if (function_exists('zohomailer_log')) {
                                zohomailer_log('Zoho_mailer->send_via_zoho threw exception: ' . $e->getMessage());
                            }
                            $result = false;
                        }

                        // Accept truthy success results; Zoho library should return boolean true on success
                        if ($result === true || (is_array($result) && !empty($result['success']))) {
                            $sent = true;
                        } else {
                            // log failure detail if available
                            if (function_exists('zohomailer_log')) {
                                $detail = is_scalar($result) ? (string)$result : json_encode($result);
                                zohomailer_log('Zoho_mailer send failed: ' . $detail);
                            }
                            $sent = false;
                        }
                    } else {
                        if (function_exists('zohomailer_log')) {
                            zohomailer_log('Zoho_mailer library not available - cannot send via Zoho.');
                        }
                    }
                }
            } catch (Throwable $e) {
                if (function_exists('zohomailer_log')) {
                    zohomailer_log('Unexpected error in Mail_interceptor::send: ' . $e->getMessage());
                }
                $sent = false;
            }

            // If Zoho send succeeded, cleanup and return true
            if ($sent) {
                // clear captured + cleanup temp files
                if ($autoClear) {
                    $this->clear(true);
                } else {
                    // still cleanup temp files if any
                    $this->cleanupTempFiles();
                }
                return true;
            }

            // Zoho send failed - attempt fallback to native CI Email if enabled
            if ($fallbackEnabled && $this->nativeEmail) {
                try {
                    // Reset native email state then re-apply captured fields
                    if (method_exists($this->nativeEmail, 'clear')) {
                        $this->nativeEmail->clear(true);
                    }

                    // Apply captured 'from'
                    if (!empty($this->captured['from'])) {
                        // Try to parse "Name <email>" pattern
                        if (strpos($this->captured['from'], '<') !== false && strpos($this->captured['from'], '>') !== false) {
                            // naive parse
                            preg_match('/^(.*)<(.+)>$/', $this->captured['from'], $m);
                            if (isset($m[2])) {
                                $addr = trim($m[2]);
                                $name = isset($m[1]) ? trim($m[1]) : '';
                                $this->nativeEmail->from($addr, trim($name, ' <>'));
                            } else {
                                $this->nativeEmail->from($this->captured['from']);
                            }
                        } else {
                            $this->nativeEmail->from($this->captured['from']);
                        }
                    }

                    if (!empty($this->captured['to'])) {
                        $this->nativeEmail->to($this->captured['to']);
                    }
                    if (!empty($this->captured['cc'])) {
                        $this->nativeEmail->cc($this->captured['cc']);
                    }
                    if (!empty($this->captured['bcc'])) {
                        $this->nativeEmail->bcc($this->captured['bcc']);
                    }
                    if (!empty($this->captured['reply_to'])) {
                        $this->nativeEmail->reply_to($this->captured['reply_to']);
                    }

                    if (!empty($this->captured['subject'])) {
                        $this->nativeEmail->subject($this->captured['subject']);
                    }
                    if (!empty($this->captured['message'])) {
                        $this->nativeEmail->message($this->captured['message']);
                    }

                    // Attachments: pass path or array entries as CI expects
                    foreach ($this->captured['attachments'] as $att) {
                        // CI_Email's attach() accepts either path or an array of options depending on CI version
                        // We prefer to pass path here, or the raw array when available
                        if (isset($att['path']) && file_exists($att['path'])) {
                            try {
                                $this->nativeEmail->attach($att['path']);
                            } catch (Throwable $e) {
                                if (function_exists('zohomailer_log')) {
                                    zohomailer_log('NativeEmail::attach (fallback) failed for ' . $att['path'] . ': ' . $e->getMessage());
                                }
                            }
                        }
                    }

                    $nativeSendOk = false;
                    try {
                        $nativeSendOk = (bool) $this->nativeEmail->send($autoClear);
                    } catch (Throwable $e) {
                        if (function_exists('zohomailer_log')) {
                            zohomailer_log('NativeEmail::send threw exception during fallback: ' . $e->getMessage());
                        }
                        $nativeSendOk = false;
                    }

                    // Cleanup temporary files
                    $this->cleanupTempFiles();

                    if ($nativeSendOk) {
                        return true;
                    }
                } catch (Throwable $e) {
                    if (function_exists('zohomailer_log')) {
                        zohomailer_log('Exception while attempting native fallback send: ' . $e->getMessage());
                    }
                }
            }

            // Final cleanup and return failure
            $this->cleanupTempFiles();
            return false;
        }

        /**
         * Remove temp files created for buffered attachments.
         *
         * @return void
         */
        private function cleanupTempFiles()
        {
            foreach ($this->captured['attachments'] as $att) {
                if (!empty($att['source']) && $att['source'] === 'buffer' && !empty($att['path'])) {
                    try {
                        if (file_exists($att['path'])) {
                            @unlink($att['path']);
                        }
                    } catch (Throwable $e) {
                        if (function_exists('zohomailer_log')) {
                            zohomailer_log('Failed to unlink temp attachment: ' . $att['path'] . ' - ' . $e->getMessage());
                        }
                    }
                }
            }
        }
    }
}
