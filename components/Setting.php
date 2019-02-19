<?php

namespace app\components;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;


class Setting
{
    const EVENT_AFTER_MESSAGE = 'afterMessage';
    const EVENT_AFTER_NOTIFICATION = 'afterNotification';

    protected static $_file = 'settings/general.json';
    protected static $_defaultsFile = 'settings/defaults.json';

    private static $_filteredSettings = [
        'language',
        'defaultRole'
    ];

    public static $_validShortKeys = [
        'esc' => 'esc',
        'tab' => 'tab',
        'space' => 'space',
        'return' => 'return',
        'backspace' => 'backspace',
        'scroll' => 'scroll',
        'capslock' => 'capslock',
        'numlock' => 'numlock',
        'pause' => 'pause',
        'insert' => 'insert',
        'home' => 'home',
        'del' => 'del',
        'end' => 'end',
        'pageup' => 'pageup',
        'pagedown' => 'pagedown',
        'left' => 'left',
        'up' => 'up',
        'right' => 'right',
        'down' => 'down',
        'f1' => 'f1',
        'f2' => 'f2',
        'f3' => 'f3',
        'f4' => 'f4',
        'f5' => 'f5',
        'f6' => 'f6',
        'f7' => 'f7',
        'f8' => 'f8',
        'f9' => 'f9',
        'f10' => 'f10',
        'f11' => 'f11',
        'f12' => 'f12',
        'Ctrl_a' => 'Ctrl_a',
        'Ctrl_b' => 'Ctrl_b',
        'Ctrl_c' => 'Ctrl_c',
        'Ctrl_d' => 'Ctrl_d',
        'Ctrl_e' => 'Ctrl_e',
        'Ctrl_f' => 'Ctrl_f',
        'Ctrl_g' => 'Ctrl_g',
        'Ctrl_h' => 'Ctrl_h',
        'Ctrl_i' => 'Ctrl_i',
        'Ctrl_j' => 'Ctrl_j',
        'Ctrl_k' => 'Ctrl_k',
        'Ctrl_l' => 'Ctrl_l',
        'Ctrl_m' => 'Ctrl_m',
        'Ctrl_n' => 'Ctrl_n',
        'Ctrl_o' => 'Ctrl_o',
        'Ctrl_p' => 'Ctrl_p',
        'Ctrl_q' => 'Ctrl_q',
        'Ctrl_r' => 'Ctrl_r',
        'Ctrl_s' => 'Ctrl_s',
        'Ctrl_t' => 'Ctrl_t',
        'Ctrl_u' => 'Ctrl_u',
        'Ctrl_v' => 'Ctrl_v',
        'Ctrl_w' => 'Ctrl_w',
        'Ctrl_x' => 'Ctrl_x',
        'Ctrl_y' => 'Ctrl_y',
        'Ctrl_z' => 'Ctrl_z',
        'Shift_a' => 'Shift_a',
        'Shift_b' => 'Shift_b',
        'Shift_c' => 'Shift_c',
        'Shift_d' => 'Shift_d',
        'Shift_e' => 'Shift_e',
        'Shift_f' => 'Shift_f',
        'Shift_g' => 'Shift_g',
        'Shift_h' => 'Shift_h',
        'Shift_i' => 'Shift_i',
        'Shift_j' => 'Shift_j',
        'Shift_k' => 'Shift_k',
        'Shift_l' => 'Shift_l',
        'Shift_m' => 'Shift_m',
        'Shift_n' => 'Shift_n',
        'Shift_o' => 'Shift_o',
        'Shift_p' => 'Shift_p',
        'Shift_q' => 'Shift_q',
        'Shift_r' => 'Shift_r',
        'Shift_s' => 'Shift_s',
        'Shift_t' => 'Shift_t',
        'Shift_u' => 'Shift_u',
        'Shift_v' => 'Shift_v',
        'Shift_w' => 'Shift_w',
        'Shift_x' => 'Shift_x',
        'Shift_y' => 'Shift_y',
        'Shift_z' => 'Shift_z',
        'Alt_a' => 'Alt_a',
        'Alt_b' => 'Alt_b',
        'Alt_c' => 'Alt_c',
        'Alt_d' => 'Alt_d',
        'Alt_e' => 'Alt_e',
        'Alt_f' => 'Alt_f',
        'Alt_g' => 'Alt_g',
        'Alt_h' => 'Alt_h',
        'Alt_i' => 'Alt_i',
        'Alt_j' => 'Alt_j',
        'Alt_k' => 'Alt_k',
        'Alt_l' => 'Alt_l',
        'Alt_m' => 'Alt_m',
        'Alt_n' => 'Alt_n',
        'Alt_o' => 'Alt_o',
        'Alt_p' => 'Alt_p',
        'Alt_q' => 'Alt_q',
        'Alt_r' => 'Alt_r',
        'Alt_s' => 'Alt_s',
        'Alt_t' => 'Alt_t',
        'Alt_u' => 'Alt_u',
        'Alt_v' => 'Alt_v',
        'Alt_w' => 'Alt_w',
        'Alt_x' => 'Alt_x',
        'Alt_y' => 'Alt_y',
        'Alt_z' => 'Alt_z',
        'Ctrl_esc' => 'Ctrl_esc',
        'Ctrl_tab' => 'Ctrl_tab',
        'Ctrl_space' => 'Ctrl_space',
        'Ctrl_return' => 'Ctrl_return',
        'Ctrl_backspace' => 'Ctrl_backspace',
        'Ctrl_scroll' => 'Ctrl_scroll',
        'Ctrl_capslock' => 'Ctrl_capslock',
        'Ctrl_numlock' => 'Ctrl_numlock',
        'Ctrl_pause' => 'Ctrl_pause',
        'Ctrl_insert' => 'Ctrl_insert',
        'Ctrl_home' => 'Ctrl_home',
        'Ctrl_del' => 'Ctrl_del',
        'Ctrl_end' => 'Ctrl_end',
        'Ctrl_pageup' => 'Ctrl_pageup',
        'Ctrl_pagedown' => 'Ctrl_pagedown',
        'Ctrl_left' => 'Ctrl_left',
        'Ctrl_up' => 'Ctrl_up',
        'Ctrl_right' => 'Ctrl_right',
        'Ctrl_down' => 'Ctrl_down',
        'Ctrl_f1' => 'Ctrl_f1',
        'Ctrl_f2' => 'Ctrl_f2',
        'Ctrl_f3' => 'Ctrl_f3',
        'Ctrl_f4' => 'Ctrl_f4',
        'Ctrl_f5' => 'Ctrl_f5',
        'Ctrl_f6' => 'Ctrl_f6',
        'Ctrl_f7' => 'Ctrl_f7',
        'Ctrl_f8' => 'Ctrl_f8',
        'Ctrl_f9' => 'Ctrl_f9',
        'Ctrl_f10' => 'Ctrl_f10',
        'Ctrl_f11' => 'Ctrl_f11',
        'Ctrl_f12' => 'Ctrl_f12',
        'Shift_esc' => 'Shift_esc',
        'Shift_tab' => 'Shift_tab',
        'Shift_space' => 'Shift_space',
        'Shift_return' => 'Shift_return',
        'Shift_backspace' => 'Shift_backspace',
        'Shift_scroll' => 'Shift_scroll',
        'Shift_capslock' => 'Shift_capslock',
        'Shift_numlock' => 'Shift_numlock',
        'Shift_pause' => 'Shift_pause',
        'Shift_insert' => 'Shift_insert',
        'Shift_home' => 'Shift_home',
        'Shift_del' => 'Shift_del',
        'Shift_end' => 'Shift_end',
        'Shift_pageup' => 'Shift_pageup',
        'Shift_pagedown' => 'Shift_pagedown',
        'Shift_left' => 'Shift_left',
        'Shift_up' => 'Shift_up',
        'Shift_right' => 'Shift_right',
        'Shift_down' => 'Shift_down',
        'Shift_f1' => 'Shift_f1',
        'Shift_f2' => 'Shift_f2',
        'Shift_f3' => 'Shift_f3',
        'Shift_f4' => 'Shift_f4',
        'Shift_f5' => 'Shift_f5',
        'Shift_f6' => 'Shift_f6',
        'Shift_f7' => 'Shift_f7',
        'Shift_f8' => 'Shift_f8',
        'Shift_f9' => 'Shift_f9',
        'Shift_f10' => 'Shift_f10',
        'Shift_f11' => 'Shift_f11',
        'Shift_f12' => 'Shift_f12',
        'Alt_esc' => 'Alt_esc',
        'Alt_tab' => 'Alt_tab',
        'Alt_space' => 'Alt_space',
        'Alt_return' => 'Alt_return',
        'Alt_backspace' => 'Alt_backspace',
        'Alt_scroll' => 'Alt_scroll',
        'Alt_capslock' => 'Alt_capslock',
        'Alt_numlock' => 'Alt_numlock',
        'Alt_pause' => 'Alt_pause',
        'Alt_insert' => 'Alt_insert',
        'Alt_home' => 'Alt_home',
        'Alt_del' => 'Alt_del',
        'Alt_end' => 'Alt_end',
        'Alt_pageup' => 'Alt_pageup',
        'Alt_pagedown' => 'Alt_pagedown',
        'Alt_left' => 'Alt_left',
        'Alt_up' => 'Alt_up',
        'Alt_right' => 'Alt_right',
        'Alt_down' => 'Alt_down',
        'Alt_f1' => 'Alt_f1',
        'Alt_f2' => 'Alt_f2',
        'Alt_f3' => 'Alt_f3',
        'Alt_f4' => 'Alt_f4',
        'Alt_f5' => 'Alt_f5',
        'Alt_f6' => 'Alt_f6',
        'Alt_f7' => 'Alt_f7',
        'Alt_f8' => 'Alt_f8',
        'Alt_f9' => 'Alt_f9',
        'Alt_f10' => 'Alt_f10',
        'Alt_f11' => 'Alt_f11',
        'Alt_f12' => 'Alt_f12',
    ];

    public static $_timeZones = array(
        "America/Noronha"=> "Brazil",
        "America/Nassau"=> "Bahamas",
        "Asia/Thimphu"=> "Bhutan",
        "Africa/Gaborone"=> "Botswana",
        "Europe/Minsk"=> "Belarus",
        "America/Belize"=> "Belize",
        "America/St_Johns"=> "Canada",
        "Indian/Cocos"=> "Cocos Islands",
        "Africa/Kinshasa"=> "Democratic Republic of the Congo",
        "Africa/Bangui"=> "Central African Republic",
        "Africa/Brazzaville"=> "Republic of the Congo",
        "Europe/Zurich"=> "Switzerland",
        "Africa/Abidjan"=> "Ivory Coast",
        "Pacific/Rarotonga"=> "Cook Islands",
        "America/Santiago"=> "Chile",
        "Africa/Douala"=> "Cameroon",
        "Asia/Shanghai"=> "China",
        "America/Bogota"=> "Colombia",
        "America/Costa_Rica"=> "Costa Rica",
        "America/Havana"=> "Cuba",
        "Atlantic/Cape_Verde"=> "Cape Verde",
        "America/Curacao"=> "Curaçao",
        "Indian/Christmas"=> "Christmas Island",
        "Asia/Nicosia"=> "Cyprus",
        "Europe/Prague"=> "Czech Republic",
        "Europe/Berlin"=> "Germany",
        "Africa/Djibouti"=> "Djibouti",
        "Europe/Copenhagen"=> "Denmark",
        "America/Dominica"=> "Dominica",
        "America/Santo_Domingo"=> "Dominican Republic",
        "Africa/Algiers"=> "Algeria",
        "America/Guayaquil"=> "Ecuador",
        "Europe/Tallinn"=> "Estonia",
        "Africa/Cairo"=> "Egypt",
        "Africa/El_Aaiun"=> "Western Sahara",
        "Africa/Asmara"=> "Eritrea",
        "Europe/Madrid"=> "Spain",
        "Africa/Addis_Ababa"=> "Ethiopia",
        "Europe/Helsinki"=> "Finland",
        "Pacific/Fiji"=> "Fiji",
        "Atlantic/Stanley"=> "Falkland Islands",
        "Pacific/Chuuk"=> "Micronesia",
        "Atlantic/Faroe"=> "Faroe Islands",
        "Europe/Paris"=> "France",
        "Africa/Libreville"=> "Gabon",
        "Europe/London"=> "United Kingdom",
        "America/Grenada"=> "Grenada",
        "Asia/Tbilisi"=> "Georgia",
        "America/Cayenne"=> "French Guiana",
        "Europe/Guernsey"=> "Guernsey",
        "Africa/Accra"=> "Ghana",
        "Europe/Gibraltar"=> "Gibraltar",
        "America/Godthab"=> "Greenland",
        "Africa/Banjul"=> "Gambia",
        "Africa/Conakry"=> "Guinea",
        "America/Guadeloupe"=> "Guadeloupe",
        "Africa/Malabo"=> "Equatorial Guinea",
        "Europe/Athens"=> "Greece",
        "Atlantic/South_Georgia"=> "South Georgia and the South Sandwich Islands",
        "America/Guatemala"=> "Guatemala",
        "Pacific/Guam"=> "Guam",
        "Africa/Bissau"=> "Guinea-Bissau",
        "America/Guyana"=> "Guyana",
        "Asia/Hong_Kong"=> "Hong Kong",
        "America/Tegucigalpa"=> "Honduras",
        "Europe/Zagreb"=> "Croatia",
        "America/Port-au-Prince"=> "Haiti",
        "Europe/Budapest"=> "Hungary",
        "Asia/Jakarta"=> "Indonesia",
        "Europe/Dublin"=> "Ireland",
        "Asia/Jerusalem"=> "Israel",
        "Europe/Isle_of_Man"=> "Isle of Man",
        "Asia/Kolkata"=> "India",
        "Indian/Chagos"=> "British Indian Ocean Territory",
        "Asia/Baghdad"=> "Iraq",
        "Asia/Tehran"=> "Iran",
        "Atlantic/Reykjavik"=> "Iceland",
        "Europe/Rome"=> "Italy",
        "Europe/Jersey"=> "Jersey",
        "America/Jamaica"=> "Jamaica",
        "Asia/Amman"=> "Jordan",
        "Asia/Tokyo"=> "Japan",
        "Africa/Nairobi"=> "Kenya",
        "Asia/Bishkek"=> "Kyrgyzstan",
        "Asia/Phnom_Penh"=> "Cambodia",
        "Pacific/Tarawa"=> "Kiribati",
        "Indian/Comoro"=> "Comoros",
        "America/St_Kitts"=> "Saint Kitts and Nevis",
        "Asia/Pyongyang"=> "North Korea",
        "Asia/Seoul"=> "South Korea",
        "Asia/Kuwait"=> "Kuwait",
        "America/Cayman"=> "Cayman Islands",
        "Asia/Almaty"=> "Kazakhstan",
        "Asia/Vientiane"=> "Laos",
        "Asia/Beirut"=> "Lebanon",
        "America/St_Lucia"=> "Saint Lucia",
        "Europe/Vaduz"=> "Liechtenstein",
        "Asia/Colombo"=> "Sri Lanka",
        "Africa/Monrovia"=> "Liberia",
        "Africa/Maseru"=> "Lesotho",
        "zone_name"=> "country_name",
        "Europe/Vilnius"=> "Lithuania",
        "Europe/Luxembourg"=> "Luxembourg",
        "Europe/Riga"=> "Latvia",
        "Africa/Tripoli"=> "Libya",
        "Africa/Casablanca"=> "Morocco",
        "Europe/Monaco"=> "Monaco",
        "Europe/Chisinau"=> "Moldova",
        "Europe/Podgorica"=> "Montenegro",
        "America/Marigot"=> "Saint Martin",
        "Indian/Antananarivo"=> "Madagascar",
        "Pacific/Majuro"=> "Marshall Islands",
        "Europe/Skopje"=> "Macedonia",
        "Africa/Bamako"=> "Mali",
        "Asia/Rangoon"=> "Myanmar",
        "Asia/Ulaanbaatar"=> "Mongolia",
        "Asia/Macau"=> "Macao",
        "Pacific/Saipan"=> "Northern Mariana Islands",
        "America/Martinique"=> "Martinique",
        "Africa/Nouakchott"=> "Mauritania",
        "America/Montserrat"=> "Montserrat",
        "Europe/Malta"=> "Malta",
        "Indian/Mauritius"=> "Mauritius",
        "Indian/Maldives"=> "Maldives",
        "Africa/Blantyre"=> "Malawi",
        "America/Mexico_City"=> "Mexico",
        "Asia/Kuala_Lumpur"=> "Malaysia",
        "Africa/Maputo"=> "Mozambique",
        "Africa/Windhoek"=> "Namibia",
        "Pacific/Noumea"=> "New Caledonia",
        "Africa/Niamey"=> "Niger",
        "Pacific/Norfolk"=> "Norfolk Island",
        "Africa/Lagos"=> "Nigeria",
        "America/Managua"=> "Nicaragua",
        "Europe/Amsterdam"=> "Netherlands",
        "Europe/Oslo"=> "Norway",
        "Asia/Kathmandu"=> "Nepal",
        "Pacific/Nauru"=> "Nauru",
        "Pacific/Niue"=> "Niue",
        "Pacific/Auckland"=> "New Zealand",
        "Asia/Muscat"=> "Oman",
        "America/Panama"=> "Panama",
        "America/Lima"=> "Peru",
        "Pacific/Tahiti"=> "French Polynesia",
        "Pacific/Port_Moresby"=> "Papua New Guinea",
        "Asia/Manila"=> "Philippines",
        "Asia/Karachi"=> "Pakistan",
        "Europe/Warsaw"=> "Poland",
        "America/Miquelon"=> "Saint Pierre and Miquelon",
        "Pacific/Pitcairn"=> "Pitcairn",
        "America/Puerto_Rico"=> "Puerto Rico",
        "Asia/Gaza"=> "Palestinian Territory",
        "Europe/Lisbon"=> "Portugal",
        "Pacific/Palau"=> "Palau",
        "America/Asuncion"=> "Paraguay",
        "Asia/Qatar"=> "Qatar",
        "Indian/Reunion"=> "Reunion",
        "Europe/Bucharest"=> "Romania",
        "Europe/Belgrade"=> "Serbia",
        "Europe/Kaliningrad"=> "Russia",
        "Africa/Kigali"=> "Rwanda",
        "Asia/Riyadh"=> "Saudi Arabia",
        "Pacific/Guadalcanal"=> "Solomon Islands",
        "Indian/Mahe"=> "Seychelles",
        "Africa/Khartoum"=> "Sudan",
        "Europe/Stockholm"=> "Sweden",
        "Asia/Singapore"=> "Singapore",
        "Atlantic/St_Helena"=> "Saint Helena",
        "Europe/Ljubljana"=> "Slovenia",
        "Arctic/Longyearbyen"=> "Svalbard and Jan Mayen",
        "Europe/Bratislava"=> "Slovakia",
        "Africa/Freetown"=> "Sierra Leone",
        "Europe/San_Marino"=> "San Marino",
        "Africa/Dakar"=> "Senegal",
        "Africa/Mogadishu"=> "Somalia",
        "America/Paramaribo"=> "Suriname",
        "Africa/Juba"=> "South Sudan",
        "Africa/Sao_Tome"=> "Sao Tome and Principe",
        "America/El_Salvador"=> "El Salvador",
        "America/Lower_Princes"=> "Sint Maarten",
        "Asia/Damascus"=> "Syria",
        "Africa/Mbabane"=> "Swaziland",
        "America/Grand_Turk"=> "Turks and Caicos Islands",
        "Africa/Ndjamena"=> "Chad",
        "Indian/Kerguelen"=> "French Southern Territories",
        "Africa/Lome"=> "Togo",
        "Asia/Bangkok"=> "Thailand",
        "Asia/Dushanbe"=> "Tajikistan",
        "Pacific/Fakaofo"=> "Tokelau",
        "Asia/Dili"=> "East Timor",
        "Asia/Ashgabat"=> "Turkmenistan",
        "Africa/Tunis"=> "Tunisia",
        "Pacific/Tongatapu"=> "Tonga",
        "Europe/Istanbul"=> "Turkey",
        "America/Port_of_Spain"=> "Trinidad and Tobago",
        "Pacific/Funafuti"=> "Tuvalu",
        "Asia/Taipei"=> "Taiwan",
        "Africa/Dar_es_Salaam"=> "Tanzania",
        "Europe/Kiev"=> "Ukraine",
        "Africa/Kampala"=> "Uganda",
        "Pacific/Johnston"=> "United States Minor Outlying Islands",
        "America/New_York"=> "United States",
        "America/Montevideo"=> "Uruguay",
        "Asia/Samarkand"=> "Uzbekistan",
        "Europe/Vatican"=> "Vatican",
        "America/St_Vincent"=> "Saint Vincent and the Grenadines",
        "America/Caracas"=> "Venezuela",
        "America/Tortola"=> "British Virgin Islands",
        "America/St_Thomas"=> "U.S. Virgin Islands",
        "Asia/Ho_Chi_Minh"=> "Vietnam",
        "Pacific/Efate"=> "Vanuatu",
        "Pacific/Wallis"=> "Wallis and Futuna",
        "Pacific/Apia"=> "Samoa",
        "Asia/Aden"=> "Yemen",
        "Indian/Mayotte"=> "Mayotte",
        "Africa/Johannesburg"=> "South Africa",
        "Africa/Lusaka"=> "Zambia",
        "Africa/Harare"=> "Zimbabwe"
    );

    /**
     * Get configuration value from ini file.
     *
     * @param string $key
     * @return string|array
     */
    public static function get($key)
    {
        $config = self::getAll();
        if (strpos($key, '.') !== false) {
            $keys = explode('.', $key);
            foreach ($keys as $key) {
                if (isset($config[$key])) {
                    $config = $config[$key];
                } else
                    return null;
                if ($key == end($keys))
                    return $config;
            }
        }

        if (isset($config[$key]))
            return $config[$key];
        return null;
    }

    /**
     * Set configuration value to ini file.
     *
     * @param string $key
     * @param string $value
     * @return boolean
     */
    public static function set($key, $value)
    {
        $config = self::getAll();
        $config[$key] = $value;
        $res = file_put_contents(self::$_file, Json::encode($config));
        return $res === false ? false : true;
    }

    /**
     * Set All configuration array to ini file.
     *
     * @param array $config
     *
     * @return boolean
     */
    public static function setAll($config)
    {
        $res = file_put_contents(self::$_file, Json::encode($config));
        return $res === false ? false : true;
    }

    /**
     * Get all configuration values from ini file.
     *
     * @param $filter boolean
     *
     * @return array
     */
    public static function getAll($filter = false)
    {
        $filePath = self::$_file;
        $json = file_get_contents($filePath);
        if ($filter) {
            $settings = Json::decode($json);
            $filteredSettings = self::$_filteredSettings;
            $settings = array_filter($settings, function ($key) use ($filteredSettings) {
                return !in_array($key, $filteredSettings);
            }, ARRAY_FILTER_USE_KEY);
        } else
            $settings = Json::decode($json);
        return $settings;
    }

    public static function existsDefaults()
    {
        return is_file(self::$_defaultsFile);
    }

    public static function getDefaults()
    {
        $filePath = self::$_defaultsFile;
        return Json::decode(file_get_contents($filePath));
    }

    public static function createOrUpdateDefaults($newConfig)
    {
        if(self::existsDefaults()){
            $defs = self::getDefaults();
            foreach ($newConfig as $key => $value){
                if(!isset($defs[$key]))
                    $defs[$key] = $value;
            }
            @file_put_contents(self::$_defaultsFile, Json::encode($defs));
        }else
            @file_put_contents(self::$_defaultsFile, Json::encode($newConfig));
    }
}