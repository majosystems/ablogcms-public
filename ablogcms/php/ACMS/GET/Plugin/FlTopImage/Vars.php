<?php
/**
 * ACMS_GET_Plugin_FlTopImage_Vars
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Plugin_FlTopImage_Vars extends ACMS_GET
{
    var $_axis = array(
        'bid'   => 'descendant-or-self',
        'cid'   => 'descendant-or-self',
    );

    var $_scope = array(
        'cid'       => 'global',
        'eid'       => 'global',
        'page'      => 'global',
    );

    function get()
    {
        $license    = (IS_LICENSED and defined('LICENSE_PLUGIN_FLTOPIMAGE') and !!LICENSE_PLUGIN_FLTOPIMAGE);

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $Vars   = array();
        $Vars   += array(
            'width'         => config('fltopimage_width'),
            'height'        => config('fltopimage_height'),
            'xmlAddress'    => acmsLink(array(
                'bid'       => $this->bid,
                'cid'       => $this->cid,
                'eid'       => $this->eid,
                'order'     => config('fltopimage_order'),
                'limit'     => config('fltopimage_limit'),
                'tpl'       => config('fltopimage_tpl'),
                'query'     => array(
                    'baxis' => $this->blogAxis(),
                    'caxis' => $this->categoryAxis(),
                ),
            ), false),
            'coverImageSrc' => config('fltopimage_coverImageSrc'),
            'coverImageX'   => config('fltopimage_coverImageX'),
            'coverImageY'   => config('fltopimage_coverImageY'),
            'fadeinTime'    => config('fltopimage_fadeinTime'),
            'displayTime'   => config('fltopimage_displayTime'),
            'fadeoutTime'   => config('fltopimage_fadeoutTime'),
            'fadeinoutColor'=> '#'.config('fltopimage_fadeinoutColor'),
            'moveflg'       => config('fltopimage_moveflg'),
            'linkflg'       => config('fltopimage_linkflg'),
            'fadeinoutColor'=> config('fltopimage_fadeinoutColor'),
            'swfUrlStr'     => config('fltopimage_swfUrlStr'),
        );

        $Tpl->add(null, $Vars);
        return $Tpl->get();

    }
}
