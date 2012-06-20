<?php
/**
 * ACMS_Services_Instagram
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
/**
 * トークン類を制御して，OAuthリクエストできる
 */
class ACMS_Services_Instagram extends ACMS_Services
{
    /**
     * アクセストークンでAPIの初期化を試みる
     *
     * @param int $bid
     * @return Services_Instagram
     */
    static public function establish($bid)
    {
        $id       = config('instagram_client_id');
        $secret   = config('instagram_client_secret');
        $redirect = config('instagram_client_redirect');

        if ( !!($access = ACMS_Services_Instagram::loadAcsToken($bid)) ) {
            return new Services_Instagram($id, $secret, $redirect, $access);
        } else {
            return new Services_Instagram($id, $secret, $redirect);
        }
    }

    /**
     * アクセストークンが取得できれば認証済みと考える
     *
     * @param int $bid
     * @return bool
     */
    static public function loadAcsToken($bid)
    {
        $tokens = parent::loadOAuthToken($bid, 'access', 'instagram');
        return !empty($tokens['instagram_oauth_access_token']) ? $tokens['instagram_oauth_access_token'] : false;
    }

    /**
     * OAuth2.0は，access secretをもたないので無視する
     *
     * @param int $bid
     * @param string $token
     * @return bool
     */
    static public function insertAcsToken($bid, $token)
    {
        return parent::insertOAuthToken($bid, $token, '', 'access', 'instagram');
    }

    /**
     * 残留したアクセストークンを除去
     *
     * @param int $bid
     * @return mixed
     */
    static public function deleteAcsToken($bid)
    {
        return parent::deleteOAuthToken($bid, 'access', 'instagram');
    }
}

/**
 * Instagramコンシューマークラス
 * OAuthライブラリに依存せず，OAuth2.0に対応する
 *
 * @package     Services
 * @copyright   2011 ayumusato.com
 * @license     MIT License
 * @author      Ayumu Sato
 */
class Services_Instagram
{
    /**
     * @var HTTP_Request
     */
    public $Response;

    protected $client_id;
    protected $client_secret;
    protected $redirect_uri;
    protected $access_token;

    protected $authorize_url = 'https://api.instagram.com/oauth/authorize/';
    protected $access_url    = 'https://api.instagram.com/oauth/access_token/';

    protected $api_host      = 'https://api.instagram.com/v1';

    /**
     * @param int $id
     * @param string $secret
     * @param string $redirect
     * @param null $access
     */
    public function __construct($id, $secret, $redirect, $access = null)
    {
        $this->client_id     = $id;
        $this->client_secret = $secret;
        $this->redirect_uri  = $redirect;

        if ( $access !== null ) {
            $this->access_token = $access;
        }
    }

    /**
     * 認証URL
     *
     * @param array $params
     * @return string
     */
    public function getAuthUrl($params = array())
    {
        $query = http_build_query(array_unique(array_merge(array(
            'client_id'    => $this->client_id,
            'redirect_uri' => $this->redirect_uri,
            'response_type'=> 'code',
        ), $params)));
        return $this->authorize_url.'?'.$query;
    }

    /**
     * アクセストークンURL
     *
     * @param array $params
     * @return string
     */
    public function getAcsTokenUrl($params = array())
    {
        $query = http_build_query(array_unique(array_merge(array(
            'client_id'    => $this->client_id,
            'client_secret'=> $this->client_secret,
            'redirect_uri' => $this->redirect_uri,
            'grant_type'   => 'authorization_code',
        ), $params)));
        return $this->access_url.'?'.$query;
    }

    /**
     * APIへのHTTPリクエストを試みる
     * レスポンスは $this->Responseを参照して確認する
     *
     * @param string $path リクエストパス
     * @param array $params リクエストパラメータ
     * @param string $http_method HTTPメソッド
     * @return bool リクエストの実行結果
     */
    public function httpRequest($path, $params = array(), $http_method = 'GET')
    {
        if ( !isset($params['access_token']) ) {
            $params['access_token'] = $this->access_token;
        }
        if ( strpos($path, $this->api_host) === 0 ) {
            $request = $path;
        } else {
            $query   = http_build_query($params);
            $request = $this->api_host.$path.'?'.$query;
        }
        $method  = strtoupper($http_method);

        include_once 'HTTP/Request.php';

        // ここを書き換えれば，使用するHTTPリクエスト用のライブラリは変更できる
        // 旧仕様に合わせるために，HTTP_Reuqestにbodyとerrorプロパティを独自拡張
        $req  =& new HTTP_Request($request, array(
            // TODO issue: タイムアウトをconfigで設定可能にする
            'timeout'     => 3,
            'readTimeout' => array(5, 0),
        ));
        $req->setMethod($method);
        if ( $method === 'POST' ) {
            $data = $req->_url->getQueryString();
            if ( !empty($data) ) {
                foreach ( $data as $k => $v ) {
                    $req->addPostData($k, $v);
                }
            }
        }
        $req->addHeader('User-Agent', 'ablogcms/'.VERSION);
        $req->addHeader('Accept-Language', HTTP_ACCEPT_LANGUAGE);

        $this->Response = $req;
        $this->Response->error = false;

        if ( $this->Response->sendRequest() ) {
            if ( $this->Response->getResponseCode() != 200 ) {
                $this->Response->error = true;
            }
            $this->Response->body = $this->Response->getResponseBody();
        } else {
            $this->Response->error = true;
        }

        return !($this->Response->error);
    }
}