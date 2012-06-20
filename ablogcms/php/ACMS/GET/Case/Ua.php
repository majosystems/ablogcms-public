<?php
/**
 * ACMS_GET_Case_Ua
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Case_Ua extends ACMS_GET
{
    function get()
    {
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $Tpl->add(config('ua'));
        return $Tpl->get();
    }
}
