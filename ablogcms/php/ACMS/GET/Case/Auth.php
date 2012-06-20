<?php
/**
 * ACMS_GET_Case_Auth
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Case_Auth extends ACMS_GET
{
    function get()
    {
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        if ( !SUID ) return false;
        $Tpl->add(ACMS_RAM::userAuth(SUID));
        return $Tpl->get();
    }
}
