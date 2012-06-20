<?php
/**
 * ACMS_GET_Api_Twitter_OAuthCallback
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Api_Twitter_OAuthCallback extends ACMS_GET_Api_Twitter
{
    function get()
    {
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());

        // ログインチェック
        if ( !SUID && !sessionWithAdministration(BID) ) {
            $Tpl->add('unlogin');
            return $Tpl->get();
        }

        $token  = $this->Get->get('oauth_token');

        // request tokenの保持をチェック
        if ( count(ACMS_Services_Twitter::loadReqToken(BID)) == 2 && !empty($token) ) {

            // access tokenの取得を試行
            $API    = ACMS_Services_Twitter::establish(BID, 'request');
            $acs    = $API->getAcsToken();

            // access tokenを保存
            $res    = ACMS_Services_Twitter::insertAcsToken(BID, $acs['oauth_token'], $acs['oauth_token_secret']);

            if ( $res !== false && !empty($acs) ) {
                // 使用済みのrequest tokenを掃除
                ACMS_Services_Twitter::deleteReqToken(BID);
                $Tpl->add('successed');
            } else {
                $Tpl->add('failed');
            }
        } else {
            $Tpl->add('failed');
        }

        return $Tpl->get();
    }
}
