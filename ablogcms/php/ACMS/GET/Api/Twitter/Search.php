<?php
/**
 * ACMS_GET_Api_Twitter_Search
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Api_Twitter_Search extends ACMS_GET_Api_Twitter_Statuses
{
    var $id;
    var $api;
    var $params;
    var $ignore;

    var $_scope = array(
        'page'      => 'global',
        'field'     => 'global',
        'keyword'   => 'global',
    );

    function get()
    {
        $this->limit  = !!LIMIT ? LIMIT : config('twitter_search_limit');
        $this->ignore = config('twitter_search_private');
        $this->id     = $this->bid;
        $this->api    = 'https://search.twitter.com/search.json';
        $this->params = array_clean(array(
            'since_id'  => ($since_id = $this->Field->get('since_id')) ? intval($since_id) : null,
            'max_id'    => ($max_id   = $this->Field->get('max_id'))   ? intval($max_id)   : null,
            'rpp'       => intval($this->limit),
            'page'      => intval($this->page),
            'q'         => $this->keyword,
            'locale'    => ('on' == config('twitter_search_locale') ) ? 'jp' : null,
//            'geocode'   => config('twitter_search_lat').','.config('twitter_search_lon').','.config('twitter_search_radius').'km',
        ));
        $this->crit = config('twitter_search_cache_expire');

        return $this->statuses();
    }

    // Search APIはJSONかATOMしかレスポンスを取得できない
    function build($response, & $Tpl)
    {
        $json = json_decode($response);

        if ( $json === false ) {
            $Tpl->add('unavailable');
            return false;
        }

        if ( count($json->results) === 0 ) {
            $Tpl->add('notFound');
            return false;
        }

        $loop  = 0;
        $args  = array();

        foreach ( $json->results as $row ) {

            $vars   = array(
                'text'      => $row->text,
                'screen_name' => $row->from_user,
                'user_id'   => $row->from_user_id,
                'status_id' => $row->id,
                'image'     => $row->profile_image_url,
                'l-image'   => $this->largeImageUrl($row->profile_image_url),
                'permalink' => ACMS_GET_Api_Twitter::WEB_URL.$row->from_user.'/status/'.$row->id,
            );

            $vars  += $this->buildDate($row->created_at, $Tpl, 'tweet:loop');

            $Tpl->add('tweet:loop', $vars);
            $loop++;

            if ( $loop == 1 ) {
                $args['first_id'] = $row->id;
            } elseif ( $loop == $this->limit ) {
                $args['last_id']  = $row->id;
            }
        }

        $args   = array_merge($args, array(
            'page'  => $this->page,
            'next'  => ($this->page + 1),
        ));
        if ( $this->page != 1 ) $args['prev'] = $this->page - 1;

        $Tpl->add('pager', $args);

        $fds    = $this->Field->listFields();
        $field  = array();
        foreach ( $fds as $fd ) {
            $field[$fd] = $this->Field->get($fd);
        }

        $Tpl->add(null, $field);
        return true;
    }
}
