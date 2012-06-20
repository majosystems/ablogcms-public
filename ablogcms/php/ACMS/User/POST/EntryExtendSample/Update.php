<?php
/**
 * ACMS_User_POST_EntryExtendSample_Update
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_User_POST_EntryExtendSample_Update extends ACMS_POST_Entry_Update
{
    /**
     * ここで指定したフィールド名を元に、saveCustomFieldが自動コールされる
     *
     * @var array
     */
    var $fieldNames  = array ();

    /**
     * ユーザー定義カスタムフィールドの注入メソッド
     * この中で独自拡張したフィールドを、何らかの場所(DBなど)に保存する
     *
     * @param string            $fieldName フィールド名
     * @param int               $eid       エントリーID
     * @param Field_Validation  $Field     フィールド名でPOSTから抽出されたField
     * @return void
     */
    function saveCustomField($fieldName, $eid, $Field)
    {
        /*
        // バリデートメソッドを追加
        $Field->setMethod('param1', 'required');
        $Field->setMethod('param2', 'required');
        $Field->setMethod('param3', 'required');

        // バリデート実行
        $Field->validate(new ACMS_Validator());

        if ( $Field->isValidAll() ) {
            // 更新処理
        } else {
            // エラー処理
        }
        */
    }
}
