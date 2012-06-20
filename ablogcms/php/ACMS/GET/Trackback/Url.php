<?php
/**
 * ACMS_GET_Trackback_Url
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Trackback_Url extends ACMS_GET
{
    var $_scope    = array(
        'eid'   => 'global',
    );

    function get()
    {
        if ( !$this->eid ) return '';
        if ( 'on' <> config('trackback') ) return '';

        $Tpl    = new Template($this->tpl);
        $Tpl->add(null, array(
            'url'   => acmsLink(array(
                'bid'   => BID,
                'eid'   => $this->eid,
                'trackback' => true,
            )),
        ));

        return $Tpl->get();

    }
}
