<?php
/**
 * ACMS_GET_Response
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Response extends ACMS_GET
{
    function get()
    {
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $Tpl->add('response:isValid#'.($this->Post->isValidAll() ? 'true' : 'false'));
        $Tpl->add(null, $this->buildField($this->Post, $Tpl));
        return $Tpl->get();
    }
}
