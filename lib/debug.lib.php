<?php
/* Copyright (C) 2025 EVARISK <technique@evarisk.com>
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
 * \file    lib/debug.lib.php
 * \ingroup saturne
 * \brief   Utility functions for debugging and logging in the Saturne module
 */

/**
 * Logs a message with details about the call stack
 *
 * This function adds the call context using `debug_backtrace()` and
 * logs the message using Dolibarr's `dol_syslog()`
 *
 * @param string $msg   The message to be logged
 * @param int    $level The log severity level (defaults to LOG_ERR)
 *
 * @return void
 */
function saturne_log(string $msg, int $level = LOG_ERR): void
{
    $out = dol_strtoupper(__METHOD__) . ' ' . $msg . PHP_EOL;

    if ($level == LOG_ERR) {
        $backtrace = debug_backtrace();

        foreach ($backtrace as $trace) {
            $file     = $trace['file'];
            $line     = $trace['line'];
            $function = $trace['function'];
            $out     .= "\t$file:$line $function" . PHP_EOL;
        }
    }

    dol_syslog($out, $level);
}
