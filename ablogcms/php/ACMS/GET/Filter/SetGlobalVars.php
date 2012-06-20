<?php
/**
 * ACMS_GET_Filter_SetGlobalVars
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Filter_SetGlobalVars extends ACMS_GET_Filter
{
    function get()
    {
        return setGlobalVars($this->tpl);
    }
}
