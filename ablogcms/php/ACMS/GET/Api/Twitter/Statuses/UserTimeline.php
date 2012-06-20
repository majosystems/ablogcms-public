<?php
/**
 * ACMS_GET_Api_Twitter_Statuses_UserTimeline
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Api_Twitter_Statuses_UserTimeline extends ACMS_GET_Api_Twitter_Statuses
{
    var $ignore;

    var $_scope = array(
        'field'     => 'global',
    );

    function get()
    {
        $this->limit  = !!LIMIT ? LIMIT : config('twitter_statuses_usertl_limit');
        $this->ignore = config('twitter_statuses_usertl_private');
        $this->id     = $this->bid;
        $this->api    = 'statuses/user_timeline.xml';
        $this->params = array_clean(array(
            'screen_name' => $this->Field->get('screen_name'),
            'user_id'   => ($user_id  = $this->Field->get('user_id'))  ? intval($user_id)  : null,
            'since_id'  => ($since_id = $this->Field->get('since_id')) ? intval($since_id) : null,
            'max_id'    => ($max_id   = $this->Field->get('max_id'))   ? intval($max_id)   : null,
            'count'     => intval($this->limit),
        ));
        $this->crit = config('twitter_statuses_usertl_cache_expire');

        return $this->statuses();
    }
}
