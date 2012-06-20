<?php
/**
 * ACMS_GET_Links
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Links extends ACMS_GET
{
    function get()
    {
        if ( !$vals = configArray('links_value') ) return '';
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $labels = configArray('links_label');
        foreach ( $vals as $i => $val ) {
            $Tpl->add('loop', array(
                'url' => $val,
                'name' => $labels[$i],
            ));
        }
        return $Tpl->get();
    }
}
