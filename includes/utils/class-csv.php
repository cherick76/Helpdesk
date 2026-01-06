<?php
/**
 * CSV utility class for import/export functionality
 */

namespace HelpDesk\Utils;

class CSV {
    /**
     * Export data to CSV format
     *
     * @param array $data Array of records to export
     * @param array $headers Column headers
     * @param string $filename Name of the file
     */
    public static function export( $data, $headers, $filename ) {
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=' . $filename );

        $output = fopen( 'php://output', 'w' );

        // Add UTF-8 BOM for Excel compatibility
        fwrite( $output, "\xEF\xBB\xBF" );

        // Write headers
        fputcsv( $output, $headers, ';', '"' );

        // Write data rows
        foreach ( $data as $row ) {
            fputcsv( $output, $row, ';', '"' );
        }

        fclose( $output );
        exit;
    }

    /**
     * Generate CSV content (returns string instead of downloading)
     *
     * @param array $data Array of records to export
     * @param array $headers Column headers
     * @return string CSV content
     */
    public static function generate( $data, $headers ) {
        $output = fopen( 'php://memory', 'r+' );

        // Add UTF-8 BOM for Excel compatibility
        fwrite( $output, "\xEF\xBB\xBF" );

        // Write headers
        fputcsv( $output, $headers, ';', '"' );

        // Write data rows
        foreach ( $data as $row ) {
            fputcsv( $output, $row, ';', '"' );
        }

        rewind( $output );
        $csv_content = stream_get_contents( $output );
        fclose( $output );

        return $csv_content;
    }

    /**
     * Parse CSV file content
     *
     * @param string $content CSV file content
     * @return array Array of parsed records (array of associative arrays)
     */
    public static function parse( $content ) {
        // Remove UTF-8 BOM if present
        if ( substr( $content, 0, 3 ) === "\xEF\xBB\xBF" ) {
            $content = substr( $content, 3 );
        }

        $lines = preg_split( '/\r\n|\r|\n/', $content );
        $lines = array_filter( $lines ); // Remove empty lines

        if ( empty( $lines ) ) {
            return [];
        }

        // Parse header row
        $headers = str_getcsv( array_shift( $lines ), ';', '"' );
        $headers = array_map( 'trim', $headers );

        $data = [];
        foreach ( $lines as $line ) {
            $values = str_getcsv( $line, ';', '"' );
            $values = array_map( 'trim', $values );

            if ( count( $values ) !== count( $headers ) ) {
                continue; // Skip malformed rows
            }

            $row = array_combine( $headers, $values );
            $data[] = $row;
        }

        return $data;
    }

    /**
     * Sanitize CSV value for storage
     *
     * @param mixed $value Value to sanitize
     * @return mixed Sanitized value
     */
    public static function sanitize_value( $value ) {
        if ( is_numeric( $value ) ) {
            return $value;
        }
        return sanitize_text_field( $value );
    }

    /**
     * Format value for CSV export
     *
     * @param mixed $value Value to format
     * @return string Formatted value
     */
    public static function format_value( $value ) {
        if ( is_array( $value ) ) {
            return json_encode( $value );
        }
        if ( is_bool( $value ) ) {
            return $value ? '1' : '0';
        }
        if ( is_null( $value ) ) {
            return '';
        }
        return (string) $value;
    }
}
