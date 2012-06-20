<?php
/**
 * ACMS_GET_Touch_Keyword
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Touch_Keyword extends ACMS_GET
{
    function get()
    {
        return (!!KEYWORD and !ADMIN) ? $this->tpl : '';
    }
}
