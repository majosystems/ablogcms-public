<?php
/**
 * ACMS_GET_Api_Facebook_Touch_Signed
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Api_Facebook_Touch_Signed extends ACMS_GET_Api_Facebook
{
    function touch()
    {
        return $this->signed_data !== ACMS_GET_Api_Facebook::REQUEST_IS_NOT_SIGNED ? $this->tpl : '';
    }
}
