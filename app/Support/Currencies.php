<?php

namespace App\Support;

/**
 * ISO 4217 (active) currency codes with display names.
 * Symbols are derived from the intl extension when available.
 */
class Currencies
{
    /**
     * Full active list, alphabetically by code => display name.
     */
    public static function list(): array
    {
        return [
            'AED' => 'UAE Dirham', 'AFN' => 'Afghan Afghani', 'ALL' => 'Albanian Lek',
            'AMD' => 'Armenian Dram', 'ANG' => 'Netherlands Antillean Guilder', 'AOA' => 'Angolan Kwanza',
            'ARS' => 'Argentine Peso', 'AUD' => 'Australian Dollar', 'AWG' => 'Aruban Florin',
            'AZN' => 'Azerbaijani Manat', 'BAM' => 'Bosnia & Herzegovina Convertible Mark', 'BBD' => 'Barbados Dollar',
            'BDT' => 'Bangladeshi Taka', 'BGN' => 'Bulgarian Lev', 'BHD' => 'Bahraini Dinar',
            'BIF' => 'Burundian Franc', 'BMD' => 'Bermudian Dollar', 'BND' => 'Brunei Dollar',
            'BOB' => 'Boliviano', 'BRL' => 'Brazilian Real', 'BSD' => 'Bahamian Dollar',
            'BTN' => 'Bhutanese Ngultrum', 'BWP' => 'Botswana Pula', 'BYN' => 'Belarusian Ruble',
            'BZD' => 'Belize Dollar', 'CAD' => 'Canadian Dollar', 'CDF' => 'Congolese Franc',
            'CHF' => 'Swiss Franc', 'CLP' => 'Chilean Peso', 'CNY' => 'Chinese Yuan',
            'COP' => 'Colombian Peso', 'CRC' => 'Costa Rican Colon', 'CUP' => 'Cuban Peso',
            'CVE' => 'Cape Verdean Escudo', 'CZK' => 'Czech Koruna', 'DJF' => 'Djiboutian Franc',
            'DKK' => 'Danish Krone', 'DOP' => 'Dominican Peso', 'DZD' => 'Algerian Dinar',
            'EGP' => 'Egyptian Pound', 'ERN' => 'Eritrean Nakfa', 'ETB' => 'Ethiopian Birr',
            'EUR' => 'Euro', 'FJD' => 'Fiji Dollar', 'FKP' => 'Falkland Islands Pound',
            'GBP' => 'British Pound', 'GEL' => 'Georgian Lari', 'GHS' => 'Ghanaian Cedi',
            'GIP' => 'Gibraltar Pound', 'GMD' => 'Gambian Dalasi', 'GNF' => 'Guinean Franc',
            'GTQ' => 'Guatemalan Quetzal', 'GYD' => 'Guyanese Dollar', 'HKD' => 'Hong Kong Dollar',
            'HNL' => 'Honduran Lempira', 'HTG' => 'Haitian Gourde', 'HUF' => 'Hungarian Forint',
            'IDR' => 'Indonesian Rupiah', 'ILS' => 'Israeli New Shekel', 'INR' => 'Indian Rupee',
            'IQD' => 'Iraqi Dinar', 'IRR' => 'Iranian Rial', 'ISK' => 'Icelandic Krona',
            'JMD' => 'Jamaican Dollar', 'JOD' => 'Jordanian Dinar', 'JPY' => 'Japanese Yen',
            'KES' => 'Kenyan Shilling', 'KGS' => 'Kyrgyzstani Som', 'KHR' => 'Cambodian Riel',
            'KMF' => 'Comorian Franc', 'KPW' => 'North Korean Won', 'KRW' => 'South Korean Won',
            'KWD' => 'Kuwaiti Dinar', 'KYD' => 'Cayman Islands Dollar', 'KZT' => 'Kazakhstani Tenge',
            'LAK' => 'Lao Kip', 'LBP' => 'Lebanese Pound', 'LKR' => 'Sri Lankan Rupee',
            'LRD' => 'Liberian Dollar', 'LSL' => 'Lesotho Loti', 'LYD' => 'Libyan Dinar',
            'MAD' => 'Moroccan Dirham', 'MDL' => 'Moldovan Leu', 'MGA' => 'Malagasy Ariary',
            'MKD' => 'Macedonian Denar', 'MMK' => 'Myanmar Kyat', 'MNT' => 'Mongolian Tugrik',
            'MOP' => 'Macanese Pataca', 'MRU' => 'Mauritanian Ouguiya', 'MUR' => 'Mauritian Rupee',
            'MVR' => 'Maldivian Rufiyaa', 'MWK' => 'Malawian Kwacha', 'MXN' => 'Mexican Peso',
            'MYR' => 'Malaysian Ringgit', 'MZN' => 'Mozambican Metical', 'NAD' => 'Namibian Dollar',
            'NGN' => 'Nigerian Naira', 'NIO' => 'Nicaraguan Cordoba', 'NOK' => 'Norwegian Krone',
            'NPR' => 'Nepalese Rupee', 'NZD' => 'New Zealand Dollar', 'OMR' => 'Omani Rial',
            'PAB' => 'Panamanian Balboa', 'PEN' => 'Peruvian Sol', 'PGK' => 'Papua New Guinean Kina',
            'PHP' => 'Philippine Peso', 'PKR' => 'Pakistani Rupee', 'PLN' => 'Polish Zloty',
            'PYG' => 'Paraguayan Guarani', 'QAR' => 'Qatari Rial', 'RON' => 'Romanian Leu',
            'RSD' => 'Serbian Dinar', 'RUB' => 'Russian Ruble', 'RWF' => 'Rwandan Franc',
            'SAR' => 'Saudi Riyal', 'SBD' => 'Solomon Islands Dollar', 'SCR' => 'Seychellois Rupee',
            'SDG' => 'Sudanese Pound', 'SEK' => 'Swedish Krona', 'SGD' => 'Singapore Dollar',
            'SHP' => 'Saint Helena Pound', 'SLE' => 'Sierra Leonean Leone', 'SOS' => 'Somali Shilling',
            'SRD' => 'Surinamese Dollar', 'SSP' => 'South Sudanese Pound', 'STN' => 'Sao Tome & Principe Dobra',
            'SYP' => 'Syrian Pound', 'SZL' => 'Swazi Lilangeni', 'THB' => 'Thai Baht',
            'TJS' => 'Tajikistani Somoni', 'TMT' => 'Turkmenistani Manat', 'TND' => 'Tunisian Dinar',
            'TOP' => 'Tongan Paanga', 'TRY' => 'Turkish Lira', 'TTD' => 'Trinidad & Tobago Dollar',
            'TZS' => 'Tanzanian Shilling', 'UAH' => 'Ukrainian Hryvnia', 'UGX' => 'Ugandan Shilling',
            'USD' => 'US Dollar', 'UYU' => 'Uruguayan Peso', 'UZS' => 'Uzbekistani Sum',
            'VES' => 'Venezuelan Bolivar', 'VND' => 'Vietnamese Dong', 'VUV' => 'Vanuatu Vatu',
            'WST' => 'Samoan Tala', 'XAF' => 'Central African CFA Franc', 'XCD' => 'East Caribbean Dollar',
            'XOF' => 'West African CFA Franc', 'XPF' => 'CFP Franc', 'YER' => 'Yemeni Rial',
            'ZAR' => 'South African Rand', 'ZMW' => 'Zambian Kwacha', 'ZWG' => 'Zimbabwe Gold',
        ];
    }

    /**
     * Sorted alphabetical by display name for dropdowns.
     */
    public static function sorted(): array
    {
        $list = self::list();
        asort($list);

        return $list;
    }

    public static function codes(): array
    {
        return array_keys(self::list());
    }

    public static function symbol(string $code): ?string
    {
        static $known = null;

        if ($known === null) {
            $known = [
                'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'INR' => '₹',
                'JPY' => '¥', 'CNY' => '¥', 'AUD' => 'A$', 'CAD' => 'C$',
                'NZD' => 'NZ$', 'HKD' => 'HK$', 'SGD' => 'S$', 'CHF' => 'CHF',
            ];
        }

        if (isset($known[$code])) {
            return $known[$code];
        }

        if (class_exists(\NumberFormatter::class)) {
            try {
                $nf = new \NumberFormatter('en_US', \NumberFormatter::CURRENCY, $code);
                $formatted = $nf->formatCurrency(0, $code);
                $symbol = trim(preg_replace('/[\d.,\s\x{00A0}]+/u', '', $formatted) ?? '');

                if ($symbol !== '' && $symbol !== $code) {
                    return $symbol;
                }
            } catch (\Throwable) {
                // fall through to the code placeholder
            }
        }

        return $code;
    }
}
