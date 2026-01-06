<?php
/**
 * Name Matcher - Porovnávanie mien bez diacritiky
 * 
 * Pomáha identifikovať pracovníkov aj keď sa nezhoduje diakritiká
 * a hlási rozdiely v diacritike.
 */
class NameMatcher {

    /**
     * Normalizuj meno - odstráni diakritikou
     * 
     * @param string $name Meno so alebo bez diacritiky
     * @return string Meno bez diacritiky
     */
    public static function normalize( $name ) {
        if ( empty( $name ) ) {
            return '';
        }

        // Mapa diacritických znakov na normalizované
        $diacritical = array(
            'á' => 'a', 'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a', 'ã' => 'a', 'å' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'ö' => 'o', 'õ' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ý' => 'y', 'ỳ' => 'y', 'ŷ' => 'y', 'ÿ' => 'y',
            'ċ' => 'c', 'č' => 'c', 'ć' => 'c',
            'ď' => 'd', 'đ' => 'd',
            'ģ' => 'g', 'ǧ' => 'g', 'ğ' => 'g',
            'ħ' => 'h',
            'ķ' => 'k',
            'ļ' => 'l', 'ľ' => 'l', 'ł' => 'l',
            'ņ' => 'n', 'ň' => 'n', 'ñ' => 'n',
            'ŕ' => 'r', 'ř' => 'r',
            'ś' => 's', 'š' => 's', 'ş' => 's',
            'ţ' => 't', 'ť' => 't',
            'ź' => 'z', 'ž' => 'z', 'ż' => 'z',
            'Á' => 'A', 'À' => 'A', 'Ä' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Å' => 'A',
            'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'Í' => 'I', 'Ì' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'Ó' => 'O', 'Ò' => 'O', 'Ô' => 'O', 'Ö' => 'O', 'Õ' => 'O',
            'Ú' => 'U', 'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U',
            'Ý' => 'Y', 'Ỳ' => 'Y', 'Ŷ' => 'Y',
            'Ć' => 'C', 'Č' => 'C', 'Ċ' => 'C',
            'Ď' => 'D', 'Đ' => 'D',
            'Ģ' => 'G', 'Ǧ' => 'G', 'Ğ' => 'G',
            'Ħ' => 'H',
            'Ķ' => 'K',
            'Ļ' => 'L', 'Ľ' => 'L', 'Ł' => 'L',
            'Ņ' => 'N', 'Ň' => 'N', 'Ñ' => 'N',
            'Ŕ' => 'R', 'Ř' => 'R',
            'Ś' => 'S', 'Š' => 'S', 'Ş' => 'S',
            'Ţ' => 'T', 'Ť' => 'T',
            'Ź' => 'Z', 'Ž' => 'Z', 'Ż' => 'Z',
        );

        return strtr( $name, $diacritical );
    }

    /**
     * Porovnaj dve mena bez ohľadu na diakritikou
     * 
     * @param string $name1 Prvé meno
     * @param string $name2 Druhé meno
     * @return bool True ak sa mena zhodujú bez diacritiky
     */
    public static function matches( $name1, $name2 ) {
        return strtolower( self::normalize( $name1 ) ) === strtolower( self::normalize( $name2 ) );
    }

    /**
     * Nájdi pracovníka v poli bez ohľadu na diakritikou
     * 
     * @param string $name Hľadané meno
     * @param array $employees Pole pracovníkov
     * @param string $name_field Názov poľa s menom (default: 'meno_priezvisko')
     * @return array|null Pracovník alebo null
     */
    public static function find_by_name( $name, $employees, $name_field = 'meno_priezvisko' ) {
        if ( empty( $name ) || empty( $employees ) ) {
            return null;
        }

        foreach ( $employees as $employee ) {
            if ( isset( $employee[ $name_field ] ) && self::matches( $name, $employee[ $name_field ] ) ) {
                return $employee;
            }
        }

        return null;
    }

    /**
     * Detektuj rozdiely v diacritike
     * 
     * @param string $name1 Prvé meno
     * @param string $name2 Druhé meno
     * @return array Informácie o rozdiely alebo prázdne pole
     */
    public static function detect_diacritics_difference( $name1, $name2 ) {
        $result = array();

        // Skontroluj, či sa zhodujú bez diacritiky
        if ( ! self::matches( $name1, $name2 ) ) {
            return $result;
        }

        // Ak sa zhodujú bez diacritiky, ale rôzni sa s diakritikou
        if ( $name1 !== $name2 ) {
            $result = array(
                'name1' => $name1,
                'name2' => $name2,
                'normalized1' => self::normalize( $name1 ),
                'normalized2' => self::normalize( $name2 ),
                'match_without_diacritics' => true,
                'has_diacritics_difference' => true,
            );
        }

        return $result;
    }

    /**
     * Zisti, ktorá verzia mena má správnu diakritikou
     * (ktorá sa používa viac v ostatných zoznamoch)
     * 
     * @param array $name_variations Pole variantov mien
     * @param array $all_employees Všetci zamestnanci na referenčné porovnanie
     * @param string $name_field Názov poľa s menom
     * @return string Najpravdepodobnejší správny variant mena
     */
    public static function get_correct_spelling( $name_variations, $all_employees = array(), $name_field = 'meno_priezvisko' ) {
        if ( empty( $name_variations ) ) {
            return '';
        }

        if ( count( $name_variations ) === 1 ) {
            return reset( $name_variations );
        }

        // Ak máme referenciu všetkých zamestnancov
        if ( ! empty( $all_employees ) ) {
            foreach ( $all_employees as $employee ) {
                if ( isset( $employee[ $name_field ] ) ) {
                    foreach ( $name_variations as $variant ) {
                        if ( self::matches( $variant, $employee[ $name_field ] ) ) {
                            return $employee[ $name_field ];
                        }
                    }
                }
            }
        }

        // Vrátim prvý variant ako default
        return reset( $name_variations );
    }
}
