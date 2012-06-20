<?php
/**
 * ACMS_GET_Blog_Field
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Blog_Field extends ACMS_GET
{
    var $_scope = array(
        'bid'   => 'global',
    );

    function get()
    {
        if ( !$this->bid ) return '';
        if ( !$row = ACMS_RAM::blog($this->bid) ) return '';

        $status = ACMS_RAM::blogStatus($this->bid);
        if (!sessionWithAdministration() and 'close' === $status) return '';
        if (!sessionWithSubscription() and 'secret'  === $status) return '';

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $Field  = loadBlogField($this->bid);
        foreach ( $row as $key => $val ) {
            $Field->setField(preg_replace('@^blog_@', '', $key), $val);
        }
        $Tpl->add(null, $this->buildField($Field, $Tpl));
        return $Tpl->get();
    }
}
