<?php
/**
 * ACMS_GET_Entry_List
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Entry_List extends ACMS_GET_Entry_Summary
{
    function initVars()
    {
        return array(
            'order'            => $this->order ? $this->order : config('entry_list_order'),
            'limit'            => intval(config('entry_list_limit')),
            'offset'           => intval(config('entry_list_offset')),
            'indexing'         => config('entry_list_indexing'),
            'secret'           => config('entry_list_secret'),
            'newtime'          => config('entry_list_newtime'),
            'unit'             => config('entry_list_unit'),
            'notfound'         => config('mo_entry_list_notfound'),
            'notfoundStatus404'=> config('entry_list_notfound_status_404'),
            'noimage'          => config('entry_list_noimage'),
            'imageX'           => intval(config('entry_list_image_x')),
            'imageY'           => intval(config('entry_list_image_y')),
            'imageTrim'        => config('entry_list_image_trim'),
            'imageZoom'        => config('entry_list_image_zoom'),
            'imageCenter'      => config('entry_list_image_center'),
            'pagerDelta'       => config('entry_list_pager_delta'),
            'pagerCurAttr'     => config('entry_list_pager_cur_attr'),
        );
    }
}
