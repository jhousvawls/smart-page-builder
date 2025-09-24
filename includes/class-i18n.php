<?php
/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @package Smart_Page_Builder
 * @since   3.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Smart Page Builder Internationalization Class
 *
 * Define the internationalization functionality.
 */
class Smart_Page_Builder_i18n {

    /**
     * Load the plugin text domain for translation.
     *
     * @since    3.0.0
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'smart-page-builder',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }

    /**
     * Get available languages
     *
     * @since    3.0.0
     * @return   array    Array of available language codes.
     */
    public function get_available_languages() {
        $languages = array();
        $language_dir = SPB_PLUGIN_DIR . 'languages/';
        
        if (is_dir($language_dir)) {
            $files = scandir($language_dir);
            foreach ($files as $file) {
                if (preg_match('/smart-page-builder-([a-z_]+)\.mo$/', $file, $matches)) {
                    $languages[] = $matches[1];
                }
            }
        }
        
        return $languages;
    }

    /**
     * Get current language
     *
     * @since    3.0.0
     * @return   string    Current language code.
     */
    public function get_current_language() {
        return get_locale();
    }

    /**
     * Check if language is RTL
     *
     * @since    3.0.0
     * @param    string    $language    Language code to check.
     * @return   bool                   True if RTL, false otherwise.
     */
    public function is_rtl_language($language = null) {
        if ($language === null) {
            $language = $this->get_current_language();
        }
        
        $rtl_languages = array(
            'ar',     // Arabic
            'he_IL',  // Hebrew
            'fa_IR',  // Persian
            'ur',     // Urdu
            'ps',     // Pashto
            'sd_PK',  // Sindhi
            'ug_CN',  // Uighur
            'yi',     // Yiddish
        );
        
        return in_array($language, $rtl_languages);
    }

    /**
     * Get text direction for current language
     *
     * @since    3.0.0
     * @return   string    'rtl' or 'ltr'.
     */
    public function get_text_direction() {
        return $this->is_rtl_language() ? 'rtl' : 'ltr';
    }

    /**
     * Format number according to locale
     *
     * @since    3.0.0
     * @param    float     $number      Number to format.
     * @param    int       $decimals    Number of decimal places.
     * @return   string                 Formatted number.
     */
    public function format_number($number, $decimals = 2) {
        return number_format_i18n($number, $decimals);
    }

    /**
     * Format date according to locale
     *
     * @since    3.0.0
     * @param    string    $date        Date to format.
     * @param    string    $format      Date format.
     * @return   string                 Formatted date.
     */
    public function format_date($date, $format = null) {
        if ($format === null) {
            $format = get_option('date_format');
        }
        
        return date_i18n($format, strtotime($date));
    }

    /**
     * Format time according to locale
     *
     * @since    3.0.0
     * @param    string    $time        Time to format.
     * @param    string    $format      Time format.
     * @return   string                 Formatted time.
     */
    public function format_time($time, $format = null) {
        if ($format === null) {
            $format = get_option('time_format');
        }
        
        return date_i18n($format, strtotime($time));
    }

    /**
     * Format datetime according to locale
     *
     * @since    3.0.0
     * @param    string    $datetime    Datetime to format.
     * @param    string    $format      Datetime format.
     * @return   string                 Formatted datetime.
     */
    public function format_datetime($datetime, $format = null) {
        if ($format === null) {
            $date_format = get_option('date_format');
            $time_format = get_option('time_format');
            $format = $date_format . ' ' . $time_format;
        }
        
        return date_i18n($format, strtotime($datetime));
    }

    /**
     * Get timezone string
     *
     * @since    3.0.0
     * @return   string    Timezone string.
     */
    public function get_timezone() {
        return wp_timezone_string();
    }

    /**
     * Convert UTC time to local time
     *
     * @since    3.0.0
     * @param    string    $utc_time    UTC time string.
     * @return   string                 Local time string.
     */
    public function utc_to_local($utc_time) {
        $timezone = new DateTimeZone($this->get_timezone());
        $datetime = new DateTime($utc_time, new DateTimeZone('UTC'));
        $datetime->setTimezone($timezone);
        
        return $datetime->format('Y-m-d H:i:s');
    }

    /**
     * Convert local time to UTC
     *
     * @since    3.0.0
     * @param    string    $local_time    Local time string.
     * @return   string                   UTC time string.
     */
    public function local_to_utc($local_time) {
        $timezone = new DateTimeZone($this->get_timezone());
        $datetime = new DateTime($local_time, $timezone);
        $datetime->setTimezone(new DateTimeZone('UTC'));
        
        return $datetime->format('Y-m-d H:i:s');
    }

    /**
     * Get language name from code
     *
     * @since    3.0.0
     * @param    string    $language_code    Language code.
     * @return   string                      Language name.
     */
    public function get_language_name($language_code) {
        $languages = array(
            'en_US' => __('English (United States)', 'smart-page-builder'),
            'en_GB' => __('English (United Kingdom)', 'smart-page-builder'),
            'es_ES' => __('Spanish (Spain)', 'smart-page-builder'),
            'es_MX' => __('Spanish (Mexico)', 'smart-page-builder'),
            'fr_FR' => __('French (France)', 'smart-page-builder'),
            'de_DE' => __('German', 'smart-page-builder'),
            'it_IT' => __('Italian', 'smart-page-builder'),
            'pt_BR' => __('Portuguese (Brazil)', 'smart-page-builder'),
            'pt_PT' => __('Portuguese (Portugal)', 'smart-page-builder'),
            'ru_RU' => __('Russian', 'smart-page-builder'),
            'zh_CN' => __('Chinese (Simplified)', 'smart-page-builder'),
            'zh_TW' => __('Chinese (Traditional)', 'smart-page-builder'),
            'ja'    => __('Japanese', 'smart-page-builder'),
            'ko_KR' => __('Korean', 'smart-page-builder'),
            'ar'    => __('Arabic', 'smart-page-builder'),
            'he_IL' => __('Hebrew', 'smart-page-builder'),
            'hi_IN' => __('Hindi', 'smart-page-builder'),
            'th'    => __('Thai', 'smart-page-builder'),
            'vi'    => __('Vietnamese', 'smart-page-builder'),
            'tr_TR' => __('Turkish', 'smart-page-builder'),
            'pl_PL' => __('Polish', 'smart-page-builder'),
            'nl_NL' => __('Dutch', 'smart-page-builder'),
            'sv_SE' => __('Swedish', 'smart-page-builder'),
            'da_DK' => __('Danish', 'smart-page-builder'),
            'no'    => __('Norwegian', 'smart-page-builder'),
            'fi'    => __('Finnish', 'smart-page-builder'),
        );
        
        return isset($languages[$language_code]) ? $languages[$language_code] : $language_code;
    }

    /**
     * Get currency symbol for locale
     *
     * @since    3.0.0
     * @param    string    $currency_code    Currency code (USD, EUR, etc.).
     * @return   string                      Currency symbol.
     */
    public function get_currency_symbol($currency_code = 'USD') {
        $symbols = array(
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'CNY' => '¥',
            'KRW' => '₩',
            'INR' => '₹',
            'RUB' => '₽',
            'BRL' => 'R$',
            'CAD' => 'C$',
            'AUD' => 'A$',
            'CHF' => 'CHF',
            'SEK' => 'kr',
            'NOK' => 'kr',
            'DKK' => 'kr',
            'PLN' => 'zł',
            'CZK' => 'Kč',
            'HUF' => 'Ft',
            'TRY' => '₺',
            'MXN' => '$',
            'ARS' => '$',
            'CLP' => '$',
            'COP' => '$',
            'PEN' => 'S/',
            'THB' => '฿',
            'VND' => '₫',
            'IDR' => 'Rp',
            'MYR' => 'RM',
            'SGD' => 'S$',
            'PHP' => '₱',
            'ZAR' => 'R',
            'EGP' => 'E£',
            'AED' => 'د.إ',
            'SAR' => 'ر.س',
            'ILS' => '₪',
        );
        
        return isset($symbols[$currency_code]) ? $symbols[$currency_code] : $currency_code;
    }

    /**
     * Format currency according to locale
     *
     * @since    3.0.0
     * @param    float     $amount          Amount to format.
     * @param    string    $currency_code   Currency code.
     * @param    bool      $show_symbol     Whether to show currency symbol.
     * @return   string                     Formatted currency.
     */
    public function format_currency($amount, $currency_code = 'USD', $show_symbol = true) {
        $formatted = $this->format_number($amount, 2);
        
        if ($show_symbol) {
            $symbol = $this->get_currency_symbol($currency_code);
            
            // Position symbol based on locale
            if (in_array($currency_code, array('EUR', 'CHF'))) {
                $formatted = $formatted . ' ' . $symbol;
            } else {
                $formatted = $symbol . $formatted;
            }
        }
        
        return $formatted;
    }

    /**
     * Get plugin translation status
     *
     * @since    3.0.0
     * @return   array    Translation status information.
     */
    public function get_translation_status() {
        $current_language = $this->get_current_language();
        $available_languages = $this->get_available_languages();
        
        return array(
            'current_language' => $current_language,
            'available_languages' => $available_languages,
            'is_translated' => in_array($current_language, $available_languages),
            'text_direction' => $this->get_text_direction(),
            'is_rtl' => $this->is_rtl_language(),
        );
    }
}
