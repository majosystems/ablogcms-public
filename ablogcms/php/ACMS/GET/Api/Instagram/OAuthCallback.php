<?php
/**
 * ACMS_GET_Api_Instagram_OAuthCallback
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Api_Instagram_OAuthCallback extends ACMS_GET_Api_Instagram
{
    function get()
    {
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());

        // ログインチェック
        if ( !SUID && !sessionWithAdministration(BID) ) {
            $Tpl->add('unlogin');
            return $Tpl->get();
        }

        $code  = $this->Get->get('code');

        // request tokenの保持をチェック
        if ( !empty($code) ) {

            // access tokenの取得を試行
            $API    = ACMS_Services_Instagram::establish(BID);
            $acsUrl = $API->getAcsTokenUrl(array('code' => $code));

            include_once 'HTTP/Request.php';

            $req  =& new HTTP_Request($acsUrl);
            $req->setMethod('POST');

            $data = $req->_url->querystring;

            if ( !empty($data) ) {
                foreach ( $data as $k => $v ) {
                    $req->addPostData($k, urldecode($v));
                }
            }

            $req->addHeader('User-Agent', 'ablogcms/'.VERSION);
            $req->addHeader('Accept-Language', HTTP_ACCEPT_LANGUAGE);
            $req->sendRequest();

            $response = json_decode($req->getResponseBody(), true);

            $access = $response['access_token'];

            // access tokenを保存
            $res    = ACMS_Services_Instagram::insertAcsToken(BID, $access);

            if ( $res !== false && !empty($access) ) {
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
