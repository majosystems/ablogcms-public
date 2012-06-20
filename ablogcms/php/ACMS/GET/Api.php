<?php
/**
 * ACMS_GET_Api
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Api extends ACMS_GET
{
    var $id;
    var $api;
    var $params;
    var $limit;
    var $crit;
    var $error_msg;

    // リクエストURLをハッシュ化して，IDのユニークを保証している
    // その為，ブログIDは関係なく，均一に扱われる
    /**
     * キャッシュの読み込み，利用できるキャッシュがなければfalseを返す
     *
     * @param string $hash
     * @return bool|null|string
     */
    function detectCache($hash)
    {
        if ( !!DEBUG_MODE  || ('on' != config('cache')) ) return false;

        $DB     = DB::singleton(dsn());
        $expire = date('Y-m-d H:i:s');

        $SQL    = SQL::newSelect('cache');
        $SQL->setSelect('cache_data');
        $SQL->addWhereOpr('cache_id', $hash);
        $SQL->addWhereOpr('cache_expire', $expire, '>', 'AND');

        return gzdecode($DB->query($SQL->get(dsn()), 'one'));
    }

    /**
     * キャッシュを保存する
     *
     * @param string $hash
     * @param int $expire キャッシュの有効期限(秒)
     * @param mixed $rawData gzencode前のデータ
     * @return miexed
     */
    function saveCache($hash, $expire, $rawData)
    {
        if ( !!DEBUG_MODE  || ('on' != config('cache')) ) return false;

        $DB     = DB::singleton(dsn());
        $expire = date('Y-m-d H:i:s', strtotime('+'.$expire.' seconds'));

        $SQL    = SQL::newDelete('cache');
        $SQL->addWhereOpr('cache_id', $hash);
        $DB->query($SQL->get(dsn()), 'exec');

        $SQL = SQL::newInsert('cache');
        $SQL->addInsert('cache_id', $hash);
        $SQL->addInsert('cache_data', gzencode($rawData));
        $SQL->addInsert('cache_expire', $expire);

        return $DB->query($SQL->get(dsn()), 'exec');
    }

    function getHash($url = null)
    {
        if ( empty($url) ) {
            return md5($this->id.$this->api.serialize($this->params));
        } else {
            return md5($url);
        }
    }

    function resolveRequest(& $Tpl, $type)
    {
        $hash   = $this->getHash();

        // キャッシュ確認
        if ( !($response = $this->detectCache($hash)) ) {
            $response   = $this->apiRequest(strtolower($type));

            // 取得できたら保存
            if ( !empty($response) ) {
                $this->saveCache($hash, $this->crit, $response);
            }
        }

        // レスポンスをビルド
        if ( !empty($response) ) {
            $this->build($response, $Tpl);
        } else {
            $this->failed($Tpl);
        }
    }

    function apiRequest($type)
    {
        try {
            switch ($type) {
                case 'instagram':
                    if ( !($API = ACMS_Services_Instagram::establish($this->id)) ) {
                        throw new Exception('establish failed');
                    }
                break;
                case 'twitter':
                default:
                    if ( !($API = ACMS_Services_Twitter::establish($this->id)) ) {
                        throw new Exception('establish failed');
                    }
                break;
            }

            if ( !($API->httpRequest($this->api, $this->params)) ) {
                throw new Exception('transaction failed');
            }

            return $API->Response->body;

        } catch (Exception $e) {
            $this->error_msg = $e->getMessage();
            return false;
        }
    }

    function failed(& $Tpl)
    {
        $Tpl->add('failed', array('error_msg' => $this->error_msg));
    }
}
