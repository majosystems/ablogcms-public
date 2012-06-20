<?php
/**
 * ACMS_GET_Analytics_Mobile
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Analytics_Mobile extends ACMS_GET
{
    function get()
    {
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());

        $GA_ACCOUNT = config('google_analytics_mobile_account');
        $GA_PIXEL   = config('google_analytics_mobile_pixel');

        $url    = "";
        $url   .= $GA_PIXEL . "?";
        $url   .= "utmac=" . $GA_ACCOUNT;
        $url   .= "&utmn=" . rand(0, 0x7fffffff);

        $refer  = REFERER;
        $query  = $_SERVER["QUERY_STRING"];
        $path   = $_SERVER["REQUEST_URI"];

        if ( empty($refer) )
        {
        $refer  = "-";
        }

        $url   .= "&utmr=" . urlencode($refer);

        if ( !empty($path) )
        {
        $url   .= "&utmp=" . urlencode($path);
        }

        $url   .= "&guid=ON";
        $url    =  str_replace("&", "&amp;", $url);

        $Tpl->add(null, array('tracksrc' => $url));

        return $Tpl->get();
    }
}
