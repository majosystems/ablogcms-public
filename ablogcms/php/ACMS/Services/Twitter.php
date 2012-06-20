<?php
/**
 * ACMS_Services_Twitter
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
require_once LIB_DIR.'OAuth.php';

/**
 * トークン類を制御して，OAuthリクエストできる
 */
class ACMS_Services_Twitter extends ACMS_Services
{
    /**
     * アクセストークンでAPIの初期化を試みる
     *
     * @param int $bid
     * @param string $type
     * @return Services_Twitter
     */
    static public function establish($bid, $type = 'access')
    {
        $key    = config('twitter_consumer_key');
        $secret = config('twitter_consumer_secret');

        switch ( $type ) {
            case 'access':
                $tokens = ACMS_Services_Twitter::loadAcsToken($bid);
                $token        = $tokens['twitter_oauth_access_token'];
                $token_secret = $tokens['twitter_oauth_access_token_secret'];
                break;
            case 'request':
                $tokens = ACMS_Services_Twitter::loadReqToken($bid);
                $token        = $tokens['twitter_oauth_request_token'];
                $token_secret = $tokens['twitter_oauth_request_token_secret'];
                break;
            default:
                $token        = null;
                $token_secret = null;
                break;
        }

        return new Services_Twitter($key, $secret, $token, $token_secret, $type);
    }

    /**
     * すべてのトークン類を取得する
     *
     * @param int $bid
     * @return array|bool
     */
    static public function loadAllToken($bid)
    {
        return parent::loadOAuthToken($bid, 'all', 'twitter');
    }

    /**
     * リクエストトークンを取得する
     *
     * @param int $bid
     * @return array|bool
     */
    static public function loadReqToken($bid)
    {
        return parent::loadOAuthToken($bid, 'request', 'twitter');
    }

    /**
     * アクセストークンを取得する
     *
     * @param int $bid
     * @return array|bool
     */
    static public function loadAcsToken($bid)
    {
        return parent::loadOAuthToken($bid, 'access', 'twitter');
    }

    /**
     * リクエストトークンを保存する
     *
     * @param int $bid
     * @param string $token
     * @param string $secret
     * @return bool
     */
    static public function insertReqToken($bid, $token, $secret)
    {
        return parent::insertOAuthToken($bid, $token, $secret, 'request', 'twitter');
    }

    /**
     * アクセストークンを保存する
     *
     * @param int $bid
     * @param string $token
     * @param string $secret
     * @return bool
     */
    static public function insertAcsToken($bid, $token, $secret)
    {
        return parent::insertOAuthToken($bid, $token, $secret, 'access', 'twitter');
    }

    /**
     * すべてのトークン類を削除する
     *
     * @param int $bid
     * @return mixed
     */
    static public function deleteOAuthToken($bid)
    {
        return parent::deleteOAuthToken($bid, 'all', 'twitter');
    }

    /**
     * リクエストトークンを削除する
     *
     * @param int $bid
     * @return mixed
     */
    static public function deleteReqToken($bid)
    {
        return parent::deleteOAuthToken($bid, 'request', 'twitter');
    }

    /**
     * アクセストークンを削除する
     *
     * @param int $bid
     * @return mixed
     */
    static public function deleteAcsToken($bid)
    {
        return parent::deleteOAuthToken($bid, 'access', 'twitter');
    }
}

/**
 * Twitterコンシューマーオブジェクト
 * OAuthライブラリに依存し，OAuth1.0に対応する
 *
 * @package     Services
 * @copyright   2010 ayumusato.com
 * @license     MIT License
 * @author      Ayumu Sato
 */
class Services_Twitter extends OAuth_Consumer
{
    protected $api_host = 'https://api.twitter.com/1/';

    /**
     * @param string $key
     * @param string $secret
     * @param null $token_key
     * @param null $token_secret
     * @param string $token_type
     */
    public function __construct($key, $secret, $token_key = null, $token_secret = null, $token_type = 'access')
    {
        // 親のコンストラクタで初期化
        parent::__construct($key, $secret);

        // 状態の昇格を試みる
        if ( !empty($token_key) && !empty($token_secret) ) {
            if ( $token_type == 'access' )
            $this->OAuth = OAuth_Client::AccessToken($this->OAuth, $token_key, $token_secret);

            if ( $token_type == 'request' )
            $this->OAuth = OAuth_Client::RequestToken($this->OAuth, $token_key, $token_secret);
        }
    }

    /**
     * 各種URLをセットする
     */
    public function setUrl()
    {
        $this->request_token_url    = 'https://api.twitter.com/oauth/request_token';
        $this->authorize_url        = 'https://api.twitter.com/oauth/authorize';
        $this->access_token_url     = 'https://api.twitter.com/oauth/access_token';
    }

    /**
     * 認証URLを取得する
     *
     * @return string
     */
    public function getAuthUrl()
    {
        return $this->authorize_url."?oauth_token={$this->OAuth->token}";
    }

    /**
     * リクエストトークンを取得する
     *
     * @return array|bool
     */
    public function getReqToken()
    {
        return parent::getReqToken();
    }

    /**
     * アクセストークンを取得する
     *
     * @return array|bool
     */
    public function getAcsToken()
    {
        return parent::getAcsToken();
    }

    /**
     * APIへのHTTPリクエストを試みる
     * レスポンスは $this->Responseを参照して確認する
     *
     * @param string $url
     * @param array $params
     * @param string $http_method
     * @return bool
     */
    public function httpRequest($url, $params = array(), $http_method = 'GET')
    {
        $url        = !!(strpos($url, 'https') === 0) ? $url : $this->api_host.$url;

        $request    = $this->OAuth->buildRequest($url, $params, 'HMAC-SHA1', $http_method);
        $method     = strtoupper($http_method);

        include_once 'HTTP/Request.php';

        // ここを書き換えれば，使用するHTTPリクエスト用のライブラリは変更できる
        // 旧仕様に合わせるために，HTTP_Reuqestにbodyとerrorプロパティを独自拡張
        $req  =& new HTTP_Request($request, array(
            // TODO issue: タイムアウトをconfigで設定可能にする
            'timeout'     => 3,
            'readTimeout' => array(5, 0),
        ));
        $req->setMethod($method);
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