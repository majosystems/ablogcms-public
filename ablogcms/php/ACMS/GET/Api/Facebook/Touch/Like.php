<?php
/**
 * ACMS_GET_Api_Facebook_Touch_Like
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Api_Facebook_Touch_Like extends ACMS_GET_Api_Facebook
{
    function touch()
    {
        return isset($this->signed_data['page']['liked']) && $this->signed_data['page']['liked'] === true ? $this->tpl : '';
    }
}
