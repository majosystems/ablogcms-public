<?php
/**
 * ACMS_User_Corrector
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
/**
 * php/ACMS/User/Corrector.php
 * 校正オプションにユーザー定義のメソッドを追加します
 *
 * ユーザー定義のメソッドが優先されます。
 * ユーザー定義の関数を利用する場合は、所定の位置にこのファイルをコピーまたは作成します。
 * 所定位置に存在しなければ、このファイルは読み込まれません。
 *
 * @package ACMS
 */
class ACMS_User_Corrector
{
    /**
     * sample
     * 校正オプションのサンプルメソッド
     *
     * @param  string $txt  - 校正オプションが適用されている文字列
     * @param  array  $args - 校正オプションの引数　{var}[sample('ここの値')]
     * @return string       - 校正後の文字列
     */
    public static function sample($txt, $args = array())
    {
        // 例 {var}[sample('hoge','fuga')]
        // {var}の中は，'a-blogcms' とする

        $hoge = isset($args[0]) ? $args[0] : null; // 'hoge'
        $fuga = isset($args[1]) ? $args[1] : null; // 'fuga'

        return $hoge.$fuga.'+'.$txt; // 'hogefuga+a-blog cms'
    }
}

