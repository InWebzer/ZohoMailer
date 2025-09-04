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
 * ZohoMailer custom file logger.
 *
 * Writes log messages into the module's logs directory. This is separate from
 * CodeIgniter's built-in `log_message()` to allow module-specific logging.
 *
 * Example:
 *     zohomailer_log('Access token refreshed successfully.');
 *
 * @param string $message  The log message to write.
 * @param string $filename The log filename (default: zohomailer_error.log).
 *
 * @return void
 */
function zohomailer_log($message, $filename = 'zohomailer_error.log')
{
    try {
        // CI instance (not currently used, but kept for future extension)
        if (function_exists('get_instance')) {
            $CI =& get_instance();
        }

        // Ensure base logs directory exists inside the module
        $logPath = APP_MODULES_PATH . 'zohomailer/logs/';
        if (!is_dir($logPath)) {
            if (!mkdir($logPath, 0755, true) && !is_dir($logPath)) {
                // If mkdir fails, fallback to CI's log system
                log_message('error', '[ZohoMailer] Failed to create log directory: ' . $logPath);
                return;
            }
        }

        $filePath = $logPath . basename($filename); // sanitize filename

        // Timestamp with microseconds for better traceability
        $entry = '[' . date('Y-m-d H:i:s.u') . '] ' . $message . PHP_EOL;

        // Append safely with locking
        if (false === @file_put_contents($filePath, $entry, FILE_APPEND | LOCK_EX)) {
            log_message('error', '[ZohoMailer] Failed to write to log file: ' . $filePath);
        }
    } catch (Throwable $e) {
        // If something goes really wrong, fallback to CI logging
        log_message('error', '[ZohoMailer] Logging helper error: ' . $e->getMessage());
    }
}
