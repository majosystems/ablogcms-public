<?php
/**
 * ACMS_GET_Touch_Index
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Touch_Index extends ACMS_GET
{
    function get()
    {
        return !('top' == VIEW or 'entry' == VIEW) ? $this->tpl : '';
    }
}
