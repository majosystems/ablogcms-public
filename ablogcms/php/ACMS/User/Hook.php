<?php
/**
 * ACMS_User_Hook
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_User_Hook extends ACMS_Hook
{
    /**
     * GETモジュール処理前
     * 解決前テンプレートの中間処理など
     *
     * @param string   &$tpl
     * @param ACMS_GET $thisModule
     */
    public function beforeGetFire($tpl, $thisModule)
    {

    }

    /**
     * GETモジュール処理後
     * 解決済みテンプレートの中間処理など
     *
     * @param string   &$res
     * @param ACMS_GET $thisModule
     */
    public function afterGetFire($res, $thisModule)
    {

    }

    /**
     * POSTモジュール処理前
     * $thisModuleのプロパティを参照・操作するなど
     *
     * @param ACMS_POST $thisModule
     */
    public function beforePostFire($thisModule)
    {

    }

    /**
     * POSTモジュール処理後
     * $thisModuleのプロパティを参照・操作するなど
     *
     * @param ACMS_POST $thisModule
     */
    public function afterPostFire($thisModule)
    {

    }

    /**
     * ビルド前（GETモジュール解決前）
     *
     * @param $tpl &$tpl テンプレート文字列
     */
    public function beforeBuild($tpl)
    {

    }

    /**
     * ビルド後（GETモジュール解決後）
     * ※ 空白の除去・文字コードの変換・POSTモジュールに対するSIDの割り当てなどはこの後に行われます
     *
     * @param string &$res レスポンス文字列
     */
    public function afterBuild($res)
    {

    }

    /**
     * 処理の一番最後のシャットダウン時
     *
     *
     */
    public function beforeShutdown()
    {

    }
}
