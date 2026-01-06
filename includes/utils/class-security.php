<?php
/**
 * HelpDesk Security Utility
 * 
 * Centralizovaná bezpečnosť, rate limiting, input validation
 */

namespace HelpDesk\Utils;

class Security {
    /**
     * Rate limit config (requesty za minútu)
     */
    const RATE_LIMIT_PER_MINUTE = 30;
    const RATE_LIMIT_PER_HOUR = 500;

    /**
     * Verify AJAX nonce and capability
     * 
     * Supports multiple nonce parameter names for backwards compatibility:
     * _wpnonce, _nonce, _ajax_nonce
     * 
     * @param string $nonce_param Nonce parameter name (default: _wpnonce)
     * @param string $nonce_action Nonce action (default: helpdesk-nonce)
     * @param string $capability User capability (default: manage_helpdesk)
     * @return bool True if all checks pass, false otherwise
     */
    public static function verify_ajax_request( $nonce_param = '_wpnonce', $nonce_action = 'helpdesk-nonce', $capability = 'manage_helpdesk' ) {
        // DEBUG
        error_log( 'DEBUG Security::verify_ajax_request called' );
        error_log( 'DEBUG nonce_param: ' . $nonce_param );
        error_log( 'DEBUG nonce_action: ' . $nonce_action );
        error_log( 'DEBUG $_POST keys: ' . implode( ', ', array_keys( $_POST ) ) );
        error_log( 'DEBUG $_POST[' . $nonce_param . ']: ' . ( isset( $_POST[$nonce_param] ) ? $_POST[$nonce_param] : 'NOT SET' ) );
        
        // Try to verify with primary nonce parameter
        $nonce_verified = check_ajax_referer( $nonce_action, $nonce_param, false );
        error_log( 'DEBUG check_ajax_referer result: ' . var_export( $nonce_verified, true ) );
        
        // If primary check fails, try alternative parameters for backwards compatibility
        if ( ! $nonce_verified ) {
            $alt_params = array( '_nonce', '_ajax_nonce' );
            foreach ( $alt_params as $alt_param ) {
                if ( $alt_param !== $nonce_param && check_ajax_referer( $nonce_action, $alt_param, false ) ) {
                    error_log( 'DEBUG Alternative nonce param ' . $alt_param . ' verified!' );
                    $nonce_verified = true;
                    break;
                }
            }
        }
        
        // Check nonce result
        if ( ! $nonce_verified ) {
            error_log( 'DEBUG Nonce verification FAILED' );
            wp_send_json_error( array(
                'message' => __( 'Security check failed - nonce invalid', HELPDESK_TEXT_DOMAIN )
            ), 403 );
            return false;
        }

        // Check capability
        if ( ! current_user_can( $capability ) ) {
            error_log( 'DEBUG Capability check FAILED - user ' . get_current_user_id() . ' does not have ' . $capability );
            wp_send_json_error( array(
                'message' => __( 'You do not have permission to perform this action', HELPDESK_TEXT_DOMAIN )
            ), 403 );
            return false;
        }

        // Check rate limit
        if ( self::is_rate_limited() ) {
            error_log( 'DEBUG Rate limit exceeded' );
            wp_send_json_error( array(
                'message' => __( 'Too many requests - please try again later', HELPDESK_TEXT_DOMAIN )
            ), 429 );
            return false;
        }

        error_log( 'DEBUG All security checks passed!' );
        return true;
    }

    /**
     * Check if user has exceeded rate limit
     * 
     * @return bool True if rate limited, false otherwise
     */
    public static function is_rate_limited() {
        $user_id = get_current_user_id();
        if ( ! $user_id ) {
            return true; // Not logged in
        }

        $transient_key = 'helpdesk_rate_limit_' . $user_id;
        $request_count = (int) get_transient( $transient_key );

        // Check per-minute limit
        if ( $request_count >= self::RATE_LIMIT_PER_MINUTE ) {
            return true;
        }

        // Increment counter and set transient for 60 seconds
        set_transient( $transient_key, $request_count + 1, 60 );

        return false;
    }

    /**
     * Sanitize a request parameter
     * 
     * @param mixed $value Value to sanitize
     * @param string $type Type of sanitization (text, textarea, email, url, int, float, array)
     * @return mixed Sanitized value
     */
    public static function sanitize_param( $value, $type = 'text' ) {
        switch ( $type ) {
            case 'textarea':
                return sanitize_textarea_field( $value );
            case 'email':
                return sanitize_email( $value );
            case 'url':
                return esc_url_raw( $value );
            case 'int':
                return intval( $value );
            case 'float':
                return floatval( $value );
            case 'array':
                return is_array( $value ) ? array_map( function( $item ) {
                    return sanitize_text_field( $item );
                }, $value ) : array();
            case 'text':
            default:
                return sanitize_text_field( $value );
        }
    }

    /**
     * Escape output for JSON response
     * 
     * @param mixed $data Data to escape
     * @return mixed Escaped data
     */
    public static function escape_response( $data ) {
        if ( is_array( $data ) ) {
            return array_map( array( __CLASS__, 'escape_response' ), $data );
        }

        if ( is_object( $data ) ) {
            $object = clone $data;
            foreach ( get_object_vars( $object ) as $key => $value ) {
                $object->$key = self::escape_response( $value );
            }
            return $object;
        }

        // String escaping
        if ( is_string( $data ) ) {
            // If it looks like HTML, use wp_kses_post, otherwise use htmlspecialchars
            if ( strpos( $data, '<' ) !== false || strpos( $data, '&' ) !== false ) {
                return wp_kses_post( $data );
            } else {
                return htmlspecialchars( $data, ENT_QUOTES, 'UTF-8' );
            }
        }

        return $data;
    }

    /**
     * Get POST parameter with validation
     * 
     * @param string $key Parameter key
     * @param mixed $default Default value if not found
     * @param string $type Sanitization type
     * @param bool $required If true, return null if not found
     * @return mixed|null Parameter value or default
     */
    public static function get_post_param( $key, $default = '', $type = 'text', $required = false ) {
        if ( ! isset( $_POST[ $key ] ) ) {
            if ( $required ) {
                wp_send_json_error( array(
                    'message' => sprintf( __( 'Required parameter missing: %s', HELPDESK_TEXT_DOMAIN ), $key )
                ), 400 );
                return null;
            }
            return $default;
        }

        $value = $_POST[ $key ];
        return self::sanitize_param( $value, $type );
    }

    /**
     * Log activity pre audit trail
     * 
     * @param string $action Action name
     * @param string $object_type Type objektu (employee, project, bug, etc)
     * @param int $object_id ID objektu
     * @param string $details Additional details
     */
    public static function log_activity( $action, $object_type = '', $object_id = 0, $details = '' ) {
        $user_id = get_current_user_id();
        $timestamp = current_time( 'mysql' );
        $ip_address = self::get_client_ip();

        $log_entry = array(
            'timestamp' => $timestamp,
            'user_id' => $user_id,
            'ip_address' => $ip_address,
            'action' => $action,
            'object_type' => $object_type,
            'object_id' => $object_id,
            'details' => $details
        );

        // Log to error_log for now (ideálne by to malo byť v databáze)
        error_log( 'HELPDESK_ACTIVITY: ' . wp_json_encode( $log_entry ) );
    }

    /**
     * Get client IP address
     * 
     * @return string IP address
     */
    public static function get_client_ip() {
        if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        // Validate IP
        $ip = filter_var( $ip, FILTER_VALIDATE_IP );
        return $ip ? $ip : '0.0.0.0';
    }
}
