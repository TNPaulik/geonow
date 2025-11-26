<?php

namespace App\MyTrait;

use Symfony\Component\HttpFoundation\Request;

trait MyString {
    private static function strposX($haystack, $needle, $number){
        if($number == '1'){
            return strpos($haystack, $needle);
        }elseif($number > '1'){
            return strpos($haystack, $needle, self::strposX($haystack, $needle, $number - 1) + strlen($needle));
        }else{
            return error_log('Error: Value for parameter $number is out of range');
        }
    }
}