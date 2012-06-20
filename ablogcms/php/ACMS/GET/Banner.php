<?php
/**
 * ACMS_GET_Banner
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Banner extends ACMS_GET
{
    function get()
    {
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());

        if ( !$aryStatus = configArray('banner_status') ) return '';
        foreach ( $aryStatus as $i => $status ) {
            if ( 'open' <> $status ) continue;
            if ( $img = config('banner_img', '', $i) ) {
                $xy = getimagesize(ARCHIVES_DIR.$img);
                $Tpl->add('banner#img', array(
                    'img'   => $img,
                    'x'     => $xy[0],
                    'y'     => $xy[1],
                    'url'   => config('banner_url', '', $i),
                    'alt'   => config('banner_alt', '', $i),
                    'target'=> config('banner_target', '', $i),
                    'nth'   => $i,
                ));
            } else {
                $Tpl->add('banner#src', array(
                    'src'   => config('banner_src', '', $i),
                    'nth'   => $i,
                ));
            }
            $Tpl->add('banner:loop');
        }

        return $Tpl->get();
    }
}
