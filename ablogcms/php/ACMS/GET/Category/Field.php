<?php
/**
 * ACMS_GET_Category_Field
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Category_Field extends ACMS_GET
{
    var $_scope = array(
        'cid'   => 'global',
    );

    function get()
    {
        if ( !$this->cid ) return '';
        if ( !$row = ACMS_RAM::category($this->cid) ) return '';

        $status = ACMS_RAM::categoryStatus($this->cid);
        if (!sessionWithAdministration() and 'close' === $status) return '';

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $Field  = loadCategoryField($this->cid);
        foreach ( $row as $key => $val ) {
            $Field->setField(preg_replace('@^category_@', '', $key), $val);
        }
        $Tpl->add(null, $this->buildField($Field, $Tpl));
        return $Tpl->get();
    }
}
