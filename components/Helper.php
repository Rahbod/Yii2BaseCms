<?php

namespace app\components;

class Helper
{
    /**
     * convert persian special letters to arabic letters
     *
     * @param $value string
     * @return string
     */
    public static function persian2Arabic($value)
    {
        $patterns = array('/([.\\+*?\[^\]$(){}=!<>|:-])/', '/ی|ي|ئ/', '/ک|ك/', '/ه|ة/', '/ا|آ|إ|أ/', '/\s/');
        $replacements = array('', '[ی|ي|ئ]', '[ک|ك]', '[ه|ة]', '[اآإأ]', ' ');
        return preg_replace($patterns, $replacements, $value);
    }
}