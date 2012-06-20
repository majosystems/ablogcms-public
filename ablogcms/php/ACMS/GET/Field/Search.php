<?php
/**
 * ACMS_GET_Field_Search
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Field_Search extends ACMS_GET
{
    var $_scope = array(
        'field' => 'global',
    );

    function get()
    {
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        if ( empty($this->Field) ) {
            $Tpl->add();
            return $Tpl->get();
        }

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $vars   = $this->buildField($this->Field, $Tpl);
        $Tpl->add(null, $vars);

        return $Tpl->get();
    }
}
