<?php
/**
 * MonkeyPay Custom Logger
 *
 * Writes structured log files into the plugin's own logs/ directory.
 * Replaces usage of error_log() for easier debugging and tracing.
 *
 * @package MonkeyPay
 * @since   3.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MonkeyPay_Logger {

    /** @var string Base directory for log files */
    private static $log_dir;

    /** @var int Max log file size in bytes (5MB) */
    const MAX_SIZE = 5 * 1024 * 1024;

    /** @var int Max number of rotated files to keep */
    const MAX_FILES = 5;

    /**
     * Get the log directory path (creates if needed).
     *
     * @return string
     */
    private static function get_log_dir() {
        if ( ! self::$log_dir ) {
            self::$log_dir = MONKEYPAY_PLUGIN_DIR . 'logs/';
        }

        if ( ! file_exists( self::$log_dir ) ) {
            wp_mkdir_p( self::$log_dir );
            // Protect directory from web access
            file_put_contents( self::$log_dir . '.htaccess', "Deny from all\n" );
            file_put_contents( self::$log_dir . 'index.php', "<?php // Silence is golden.\n" );
        }

        return self::$log_dir;
    }

    /**
     * Write a log entry to a specific channel.
     *
     * @param string $channel  Log channel ('webhook', 'lark', 'transaction', 'error')
     * @param string $level    Log level ('INFO', 'WARN', 'ERROR', 'DEBUG')
     * @param string $message  Log message
     * @param array  $context  Additional data context
     */
    public static function log( $channel, $level, $message, $context = [] ) {
        $dir  = self::get_log_dir();
        $file = $dir . $channel . '.log';

        // Rotate if file too large
        self::maybe_rotate( $file );

        $timestamp = current_time( 'Y-m-d H:i:s' );
        $context_str = ! empty( $context ) ? ' | ' . wp_json_encode( $context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) : '';

        $line = "[{$timestamp}] [{$level}] {$message}{$context_str}" . PHP_EOL;
        file_put_contents( $file, $line, FILE_APPEND | LOCK_EX );
    }

    /**
     * Convenience: Log webhook events.
     */
    public static function webhook( $message, $context = [] ) {
        self::log( 'webhook', 'INFO', $message, $context );
    }

    /**
     * Convenience: Log Lark/connection dispatch events.
     */
    public static function lark( $message, $context = [] ) {
        self::log( 'lark', 'INFO', $message, $context );
    }

    /**
     * Convenience: Log transactions.
     */
    public static function transaction( $message, $context = [] ) {
        self::log( 'transaction', 'INFO', $message, $context );
    }

    /**
     * Convenience: Log errors.
     */
    public static function error( $message, $context = [] ) {
        self::log( 'error', 'ERROR', $message, $context );
    }

    /**
     * Convenience: Log API calls (outgoing requests to MonkeyPay server).
     */
    public static function api( $message, $context = [] ) {
        self::log( 'api', 'INFO', $message, $context );
    }

    /**
     * Rotate log file if it exceeds MAX_SIZE.
     *
     * @param string $file Full path to log file
     */
    private static function maybe_rotate( $file ) {
        if ( ! file_exists( $file ) || filesize( $file ) < self::MAX_SIZE ) {
            return;
        }

        // Rotate existing files: .log.4 -> .log.5, .log.3 -> .log.4, etc.
        for ( $i = self::MAX_FILES - 1; $i >= 1; $i-- ) {
            $old = $file . '.' . $i;
            $new = $file . '.' . ( $i + 1 );
            if ( file_exists( $old ) ) {
                rename( $old, $new );
            }
        }

        // Current file becomes .log.1
        rename( $file, $file . '.1' );

        // Delete oldest file if over limit
        $oldest = $file . '.' . ( self::MAX_FILES + 1 );
        if ( file_exists( $oldest ) ) {
            unlink( $oldest );
        }
    }

    /**
     * Read recent log entries from a channel.
     *
     * @param string $channel  Log channel
     * @param int    $lines    Number of recent lines to return
     * @return array
     */
    public static function get_recent( $channel, $lines = 50 ) {
        $file = self::get_log_dir() . $channel . '.log';
        if ( ! file_exists( $file ) ) {
            return [];
        }

        $all_lines = file( $file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
        return array_slice( $all_lines, -$lines );
    }
}
