<?php
/**
 * ACMS_GET_Api_Facebook
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Api_Facebook extends ACMS_GET_Api
{
    const SIGNATURE_IS_NOT_VALID = 1;
    const REQUEST_IS_NOT_SIGNED  = 2;

    var $app_id      = null;
    var $app_secret  = null;

    var $signed_data = null;

    function get()
    {
        $this->app_id       = config('facebook_app_id');
        $this->app_secret   = config('facebook_app_secret');

        $this->signed_data  = $this->signCapture();

        switch ( $this->signed_data ) {
            case ACMS_GET_Api_Facebook::SIGNATURE_IS_NOT_VALID :
                $warn = str_replace('%CLASS%', get_class($this), config('api_facebook_warn_invalid_sign'));
                return !!DEBUG_MODE ? $warn : null;
                break;
            case ACMS_GET_Api_Facebook::REQUEST_IS_NOT_SIGNED :
            default :
                return $this->touch();
                break;
        }
    }

    function touch()
    {
        return $this->tpl;
    }

    function signCapture()
    {
        if ( $this->Post->isExists('signed_request') ) {
            list($encoded_sig, $payload) = explode('.', $this->Post->get('signed_request'), 2);
            $data = json_decode($this->base64_url_decode($payload), true);
            $sig  = $this->base64_url_decode($encoded_sig);

            $sig_method = $data['algorithm'];

            switch ($sig_method) {
                case 'HMAC-SHA256'  :
                    $expected_sig = hash_hmac('sha256', $payload, $this->app_secret, true);
                    break;
                default :
                    $expected_sig = null;
                    break;
            }

            if ( $sig === $expected_sig ) {
                return $data;
            } else {
                return ACMS_GET_Api_Facebook::SIGNATURE_IS_NOT_VALID;
            }
        }
        return ACMS_GET_Api_Facebook::REQUEST_IS_NOT_SIGNED;
    }

    function base64_url_decode($str)
    {
        return base64_decode(strtr($str, '-_', '+/'));
    }
}
