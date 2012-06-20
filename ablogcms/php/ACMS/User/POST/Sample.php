<?php
/**
 * ACMS_User_POST_Sample
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
/**
 * php/ACMS/User/POST/Sample.php
 *
 * テンプレート上では、標準のPOSTモジュールと同様に、
 * '<input type="submit" name="ACMS_POST_Sample" value="送信" />' で呼び出されます。
 */
class ACMS_User_POST_Sample extends ACMS_POST
{
    function post()
    {
        return $this->Post;
    }
}
