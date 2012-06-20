<?php
/**
 * ACMS_GET_Api_Twitter_AdminOAuth
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Api_Twitter_AdminOAuth extends ACMS_GET_Api_Twitter
{
    function get()
    {
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());

        $key    = config('twitter_consumer_key');
        $secret = config('twitter_consumer_secret');

        // access tokenの保持をチェック
        if ( count(ACMS_Services_Twitter::loadAcsToken(BID)) == 2 ) {

            $API = ACMS_Services_Twitter::establish(BID);

            if ( !!($API->httpRequest('account/verify_credentials.xml', array(), 'GET')) ) {
                $xml    = $API->Response->body;
                $xml    = $this->xml_decode($xml);

                $vars   = array(
                    'id'                => $xml->id,
                    'screen_name'       => $xml->screen_name,
                    'user_name'         => $xml->name,
                    'statuses_count'    => $xml->statuses_count,
                    'followers_count'   => $xml->followers_count,
                    'friends_count'     => $xml->friends_count,
                    'limit'             => $API->Response->getResponseHeader('x-ratelimit-limit'),
                    'remaining'         => $API->Response->getResponseHeader('x-ratelimit-remaining'),
                );
    
                $Tpl->add('Auth', $vars);
            } else {
                $Tpl->add('failed');
            }
        } elseif ( !empty($key) && !empty($secret) ) {

            $API    = ACMS_Services_Twitter::establish(BID, 'none');
            $token  = $API->getReqToken();
            $url    = $API->getAuthUrl();

            $vars   = array(
                'oauth_url'             => $url,
                'oauth_token'           => $token['oauth_token'],
                'oauth_token_secret'    => $token['oauth_token_secret'],
            );
            $Tpl->add('notAuth', $vars);
        } else {
            $Tpl->add('notFoundKeys');
        }

        return $Tpl->get();
    }
}
