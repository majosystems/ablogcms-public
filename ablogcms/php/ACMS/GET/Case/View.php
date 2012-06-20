<?php
/**
 * ACMS_GET_Case_View
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Case_View extends ACMS_GET
{
    function get()
    {
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $Tpl->add(VIEW);
        return $Tpl->get();
    }
}
