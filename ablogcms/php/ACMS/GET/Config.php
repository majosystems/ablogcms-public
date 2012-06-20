<?php
/**
 * ACMS_GET_Config
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Config extends ACMS_GET
{
    function get ()
    {
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $Tpl->add(null, $this->buildField(Field::singleton('config'), $Tpl));
        return $Tpl->get();
    }
}
