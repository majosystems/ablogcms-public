<?php
/**
 * ACMS_GET_Touch_Login
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Touch_Login extends ACMS_GET
{
    function get()
    {
        return !!SID ? $this->tpl : false;
    }
}
