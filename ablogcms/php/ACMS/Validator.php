<?php
/**
 * ACMS_Validator
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_Validator
{
    function required($val)
    {
        //return ('' !== $val);
        return !empty($val) or ('0' === $val);
    }

    function minlength($val, $arg)
    {
        if ( '' === $val ) return true;
        return intval($arg) <= mb_strlen($val);
    }

    function maxlength($val, $arg)
    {
        if ( '' === $val ) return true;
        return intval($arg) >= mb_strlen($val);
    }

    function min($val, $arg)
    {
        if ( '' === $val ) return true;
        return intval($arg) <= intval($val);
    }

    function max($val, $arg)
    {
        if ( '' === $val ) return true;
        return intval($arg) >= intval($val);
    }

    function regex($val, $regex)
    {
        if ( empty($regex) ) return false;
        if ( empty($val) ) return true;

        //---------------
        // compatibility
        if ( '@' !== substr($regex, 0, 1) ) $regex  = '@'.$regex.'@';

        return preg_match($regex, $val);
    }

    function regexp($val, $regexp)
    {
        return ACMS_Validator::regex($val, $regexp);
    }

    function digits($val)
    {
        if ( empty($val) ) return true;
        return is_numeric($val);
    }

    function email($val)
    {
        if ( empty($val) ) return true;
        $ptn    = '/^(?:[^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff])|"[^\\\\\x80-\xff\n\015"]*(?:\\\\[^\x80-\xff][^\\\\\x80-\xff\n\015"]*)*")(?:\.(?:[^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff])|"[^\\\\\x80-\xff\n\015"]*(?:\\\\[^\x80-\xff][^\\\\\x80-\xff\n\015"]*)*"))*@(?:[^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff])|\[(?:[^\\\\\x80-\xff\n\015\[\]]|\\\\[^\x80-\xff])*\])(?:\.(?:[^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\\\[\]\000-\037\x80-\xff])|\[(?:[^\\\\\x80-\xff\n\015\[\]]|\\\\[^\x80-\xff])*\]))*$/';
        return preg_match($ptn, $val);
    }

    function url($val)
    {
        if ( empty($val) ) return true;
        return preg_match('@^(https?|ftp)(:\/\/[-_.!~*\'()a-zA-Z0-9;\/?:\@&=+\$,%#]+)$@', $val);
    }

    function equalTo($val, $name, & $Field)
    {
        if ( empty($name) ) return false;
        //if ( !isset($post[$name]) ) return false;
        return $val == $Field->get($name);
    }

    function dates($val)
    {
        if ( empty($val) ) return true;
        $ptn    = '@^[sS]{1,2}(\d{2})\W{1}\d{1,2}\W{1}\d{0,2}$|^[hH]{1}(\d{1,2})\W{1}\d{1,2}\W{1}\d{0,2}$|^\d{1,2}$|^\d{1,2}\W{1}\d{1,2}$|^\d{2,4}\W{1}\d{1,2}\W{1}\d{0,2}$|^\d{4}\d{2}\d{2}$@';
        return preg_match($ptn, $val);
    }

    function times($val)
    {
        if ( empty($val) ) return true;
        $ptn    = '@^\d{1,2}$|^\d{1,2}\W{1}\d{1,2}$|^\d{1,2}\W{1}\d{1,2}\W{1}\d{1,2}$|^\d{2}\d{2}\d{2}$@';
        return preg_match($ptn, $val);
    }

    function in($val, $choice)
    {
        if ( empty($val) ) return true;
        if ( !is_array($choice) ) return false;
        return in_array($val, $choice);
    }

    function all_justChecked($ary, $cnt)
    {
        return intval($cnt) == count($ary);
    }

    function all_minChecked($ary, $min)
    {
        return intval($min) <= count($ary);
    }

    function all_maxChecked($ary, $max)
    {
        return intval($max) >= count($ary);
    }

    function all_unique($ary)
    {
        $_ary = array_unique($ary);
        return count($ary) === count($_ary);
    }
}
