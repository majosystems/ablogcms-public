<?php
/**
 * ACMS_GET_Entry_Headline
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Entry_Headline extends ACMS_GET_Entry_Summary
{
    var $_scope = array(
        'uid'       => 'global',
        'cid'       => 'global',
        'eid'       => 'global',
        'keyword'   => 'global',
        'tag'       => 'global',
        'field'     => 'global',
        'start'     => 'global',
        'end'       => 'global',
        'page'      => 'global',
    );

    function initVars()
    {
        return array(
            'order'            => $this->order ? $this->order : config('entry_headline_order'),
            'limit'            => intval(config('entry_headline_limit')),
            'offset'           => intval(config('entry_headline_offset')),
            'indexing'         => config('entry_headline_indexing'),
            'secret'           => config('entry_headline_secret'),
            'newtime'          => config('entry_headline_newtime'),
            'unit'             => config('entry_headline_unit'),
            'notfound'         => config('mo_entry_headline_notfound'),
            'notfoundStatus404'=> config('entry_headline_notfound_status_404'),
            'noimage'          => config('entry_headline_noimage'),
            'imageX'           => intval(config('entry_headline_image_x')),
            'imageY'           => intval(config('entry_headline_image_y')),
            'imageTrim'        => config('entry_headline_image_trim'),
            'imageZoom'        => config('entry_headline_image_zoom'),
            'imageCenter'      => config('entry_headline_image_center'),
            'pagerDelta'       => config('entry_headline_pager_delta'),
            'pagerCurAttr'     => config('entry_headline_pager_cur_attr'),
        );
    }
}
