<?php
/**
 * ACMS_GET_Touch_Ssl
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Touch_Ssl extends ACMS_GET
{
    function get()
    {
        return HTTPS ? $this->tpl : false;
    }
}
