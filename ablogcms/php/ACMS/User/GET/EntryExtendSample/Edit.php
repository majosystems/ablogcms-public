<?php
/**
 * ACMS_User_GET_EntryExtendSample_Edit
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_User_GET_EntryExtendSample_Edit extends ACMS_GET_Admin_Entry_Edit
{
    /**
     * ここで指定したフィールド名を元に、loadCustomFieldが自動コールされる
     *
     * @var array
     */
    var $fieldNames  = array ();

    /**
     * ユーザー定義カスタムフィールドの注入メソッド
     * この中で独自拡張したフィールドを、何らかの場所(DBなど)から読み込む
     * すべて同じブロック（entry:loop）をルートとして展開されるため、変数名の衝突に注意すること
     * fieldNameをprefixとするような命名を推奨（e.g, hoge_var1, hoge_var2, hoge_var3...）
     *
     * @param string $fieldName フィールド名
     * @param int    $eid       エントリーID
     * @return Field            展開したい独自に読み込んだField
     */
    function loadCustomField($fieldName, $eid)
    {
        $Field = new Field();
        return $Field;
    }
}
