<?php
/**
 * ACMS_GET_Api_Twitter_List_Statuses
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Api_Twitter_List_Statuses extends ACMS_GET_Api_Twitter_Statuses
{
    var $ignore;

    var $_scope = array(
        'field'     => 'global',
    );

    function get()
    {
        $user   = $this->Field->get('user');
        $list   = $this->Field->get('list');

        $this->limit  = !!LIMIT ? LIMIT : config('twitter_list_statuses_limit');
        $this->ignore = config('twitter_list_statuses_private');
        $this->id     = $this->bid;
        $this->api    = "lists/statuses.xml";
        $this->params = array_clean(array(
            'slug'              => $list,
            'owner_screen_name' => $user,
            'since_id'          => ($since_id = $this->Field->get('since_id')) ? intval($since_id) : null,
            'max_id'            => ($max_id   = $this->Field->get('max_id'))   ? intval($max_id)   : null,
            'per_page'          => intval($this->limit),
        ));
        $this->crit = config('twitter_list_statuses_cache_expire');

        return $this->statuses();
    }
} 