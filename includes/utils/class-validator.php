<?php
/**
 * Validator utility class
 */

namespace HelpDesk\Utils;

class Validator {
    /**
     * Validate employee fields
     */
    public static function validate_employee( $data ) {
        $errors = array();

        // Validate meno_priezvisko
        if ( empty( $data['meno_priezvisko'] ) ) {
            $errors['meno_priezvisko'] = __( 'Meno a priezvisko je povinné', HELPDESK_TEXT_DOMAIN );
        }

        // Validate klapka (optional, but if provided must be 4 digits)
        if ( ! empty( $data['klapka'] ) && ! preg_match( '/^[0-9]{4}$/', $data['klapka'] ) ) {
            $errors['klapka'] = __( 'Klapka musí byť 4-miestne číslo', HELPDESK_TEXT_DOMAIN );
        }

        // Validate mobil (optional)
        if ( ! empty( $data['mobil'] ) && ! preg_match( '/^[\d\s\+\-\(\)]+$/', $data['mobil'] ) ) {
            $errors['mobil'] = __( 'Neplatný formát mobilného čísla', HELPDESK_TEXT_DOMAIN );
        }

        return $errors;
    }

    /**
     * Validate project fields
     */
    public static function validate_project( $data ) {
        $errors = array();

        // Validate zakaznicke_cislo - can be any text up to 255 characters
        if ( empty( $data['zakaznicke_cislo'] ) ) {
            $errors['zakaznicke_cislo'] = __( 'Zákaznícke číslo je povinné', HELPDESK_TEXT_DOMAIN );
        } elseif ( strlen( $data['zakaznicke_cislo'] ) > 255 ) {
            $errors['zakaznicke_cislo'] = __( 'Zákaznícke číslo môže obsahovať maximálne 255 znakov', HELPDESK_TEXT_DOMAIN );
        }

        // Validate nazov
        if ( empty( $data['nazov'] ) ) {
            $errors['nazov'] = __( 'Názov projektu je povinný', HELPDESK_TEXT_DOMAIN );
        }

        // Validate projektove_cislo
        if ( empty( $data['projektove_cislo'] ) ) {
            $errors['projektove_cislo'] = __( 'Projektové číslo je povinné', HELPDESK_TEXT_DOMAIN );
        }

        // Validate podnazov - teraz povinné
        if ( empty( $data['podnazov'] ) ) {
            $errors['podnazov'] = __( 'Podnázov je povinný', HELPDESK_TEXT_DOMAIN );
        }

        return $errors;
    }

    /**
     * Validate bug fields
     */
    public static function validate_bug( $data ) {
        $errors = array();

        // Validate nazov
        if ( empty( $data['nazov'] ) ) {
            $errors['nazov'] = __( 'Názov riešenia je povinný', HELPDESK_TEXT_DOMAIN );
        }

        // Validate stav
        $allowed_states = array( 'novy', 'rozpracovany', 'vyrieseny', 'zavrety' );
        if ( ! empty( $data['stav'] ) && ! in_array( $data['stav'], $allowed_states, true ) ) {
            $errors['stav'] = __( 'Neplatný stav riešenia', HELPDESK_TEXT_DOMAIN );
        }

        return $errors;
    }

    /**
     * Sanitize employee data
     */
    public static function sanitize_employee( $data ) {
        return array(
            'meno_priezvisko' => sanitize_text_field( $data['meno_priezvisko'] ?? '' ),
            'klapka' => sanitize_text_field( $data['klapka'] ?? '' ),
            'mobil' => sanitize_text_field( $data['mobil'] ?? '' ),
            'pozicia_id' => isset( $data['pozicia_id'] ) ? absint( $data['pozicia_id'] ) : null,
            'poznamka' => wp_kses_post( $data['poznamka'] ?? '' ),
        );
    }

    /**
     * Sanitize project data
     */
    public static function sanitize_project( $data ) {
        return array(
            'zakaznicke_cislo' => sanitize_text_field( $data['zakaznicke_cislo'] ?? '' ),
            'servisna_sluzba' => sanitize_text_field( $data['servisna_sluzba'] ?? '' ),
            'nazov' => sanitize_text_field( $data['nazov'] ?? '' ),
            'podnazov' => sanitize_text_field( $data['podnazov'] ?? '' ),
            'projektove_cislo' => sanitize_text_field( $data['projektove_cislo'] ?? '' ),
            'sla' => sanitize_text_field( $data['sla'] ?? '' ),
            'servisny_kontrakt' => sanitize_text_field( $data['servisny_kontrakt'] ?? '' ),
            'zakaznik' => sanitize_text_field( $data['zakaznik'] ?? '' ),
            'pm_manazer_id' => absint( $data['pm_manazer_id'] ?? 0 ),
        );
    }

    /**
     * Sanitize bug data
     */
    public static function sanitize_bug( $data ) {
        return array(
            'nazov' => sanitize_text_field( $data['nazov'] ?? '' ),
            'popis' => wp_kses_post( $data['popis'] ?? '' ),
            'kod_chyby' => sanitize_text_field( $data['kod_chyby'] ?? '' ),
            'produkt' => absint( $data['produkt'] ?? 0 ),
            'riesenie' => wp_kses_post( $data['riesenie'] ?? '' ),
            'riesenie_2' => wp_kses_post( $data['riesenie_2'] ?? '' ),
            'podpis_id' => absint( $data['podpis_id'] ?? 0 ) ?: null,
            'tagy' => sanitize_text_field( $data['tagy'] ?? '' ),
            'stav' => sanitize_text_field( $data['stav'] ?? 'novy' ),
        );
    }
}
