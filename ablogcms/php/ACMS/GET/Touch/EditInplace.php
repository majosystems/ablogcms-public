<?php
/**
 * ACMS_GET_Touch_EditInplace
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Touch_EditInplace extends ACMS_GET
{
    function get()
    {
        return ('on' == config('entry_edit_inplace_enable') && 'on' == config('entry_edit_inplace') ) ? $this->tpl : false;
    }
}
