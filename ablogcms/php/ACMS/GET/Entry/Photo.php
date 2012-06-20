<?php
/**
 * ACMS_GET_Entry_Photo
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Entry_Photo extends ACMS_GET_Entry_Summary
{
    function initVars()
    {
        return array(
            'order'            => $this->order ? $this->order : config('entry_photo_order'),
            'limit'            => intval(config('entry_photo_limit')),
            'offset'           => intval(config('entry_photo_offset')),
            'indexing'         => config('entry_photo_indexing'),
            'secret'           => config('entry_photo_secret'),
            'newtime'          => config('entry_photo_newtime'),
            'unit'             => config('entry_photo_unit'),
            'notfound'         => config('mo_entry_photo_notfound'),
            'notfoundStatus404'=> config('entry_photo_notfound_status_404'),
            'noimage'          => config('entry_photo_noimage'),
            'imageX'           => intval(config('entry_photo_image_x')),
            'imageY'           => intval(config('entry_photo_image_y')),
            'imageTrim'        => config('entry_photo_image_trim'),
            'imageZoom'        => config('entry_photo_image_zoom'),
            'imageCenter'      => config('entry_photo_image_center'),
            'pagerDelta'       => config('entry_photo_pager_delta'),
            'pagerCurAttr'     => config('entry_photo_pager_cur_attr'),
        );
    }
}
