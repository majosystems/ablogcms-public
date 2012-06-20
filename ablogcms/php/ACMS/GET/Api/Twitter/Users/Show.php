<?php
/**
 * ACMS_GET_Api_Twitter_Users_Show
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Api_Twitter_Users_Show extends ACMS_GET_Api_Twitter
{
    var $_scope = array(
        'field'     => 'global',
    );

    function get()
    {
        // OAuth認証済みのBID
        $this->id     = $this->bid;
        $this->api    = 'users/show.xml';
        $this->params = array_clean(array(
            'user_id'     => ($user_id  = $this->Field->get('user_id'))  ? intval($user_id)  : null,
            'screen_name' => $this->Field->get('screen_name'),
        ));
        $this->crit   = config('twitter_users_show_cache_expire');

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());

        $this->resolveRequest($Tpl, 'twitter');

        return $Tpl->get();
    }

    function build($response, & $Tpl)
    {
        $xml   = $this->xml_decode($response);

        $vars   = array(
            'friends_count'     => $xml->friends_count,
            'statuses_count'    => $xml->statuses_count,
            'followers_count'   => $xml->followers_count,
            'name'              => $xml->name,
            'screen_name'       => $xml->screen_name,
            'url'               => $xml->url,
            'id'                => $xml->id,
            'image'             => $xml->profile_image_url,
            'l-image'           => $this->largeImageUrl($xml->profile_image_url),
            'bg-image'          => $xml->profile_background_image_url,
            'p-bg-color'        => $xml->profile_background_color,
            'p-txt-color'       => $xml->profile_text_color,
            'description'       => $xml->description,
            'location'          => $xml->location,
            'created_at'        => $xml->created_at,
            'duration'          => $this->calcDuration($xml->created_at),
        );

        $vars  += $this->buildDate($xml->created_at, $Tpl, 'user');

        $Tpl->add('user', $vars);
    }

    function calcDuration($since)
    {
        $since  = strtotime($since);
        $now    = time();

        $dur    = $now - $since;
        return intval($dur / (60*60*24));
    }
}
