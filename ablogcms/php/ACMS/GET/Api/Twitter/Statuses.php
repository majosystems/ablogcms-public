<?php
/**
 * ACMS_GET_Api_Twitter_Statuses
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Api_Twitter_Statuses extends ACMS_GET_Api_Twitter
{
    function statuses()
    {
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());

        $this->resolveRequest($Tpl, 'twitter');

        return $Tpl->get();
    }

    function build($response, & $Tpl)
    {
        $xml = $this->xml_decode($response);

        if ( $xml === false ) {
            $Tpl->add('unavailable');
            return false;
        }

        if ( count($xml) === 0 ) {
            $Tpl->add('notFound');
            return false;
        }

        $loop  = 0;
        $args  = array();

        foreach ( $xml as $row ) {
            if ( 'true' == $row->user->protected && 'on' == $this->ignore ) {
                continue;
            }

            $vars   = array(
                'text'      => $row->text,
                'name'      => $row->user->name,
                'screen_name'=> $row->user->screen_name,
                'user_id'   => $row->user->id,
                'status_id' => $row->id,
                'image'     => $row->user->profile_image_url,
                'l-image'   => $this->largeImageUrl($row->user->profile_image_url),
                'permalink' => ACMS_GET_Api_Twitter::WEB_URL.$row->user->screen_name.'/status/'.$row->id,
            );

            $vars  += $this->buildDate($row->created_at, $Tpl, 'tweet:loop');

            $Tpl->add('tweet:loop', $vars);
            $loop++;

            if ( $loop == 1 ) {
                $args['first_id'] = $row->id;
            } elseif ( $loop == count($xml) ) {
                $args['last_id']  = $row->id;
            }
        }

        /**
         * build pagger
         */
        $Tpl->add('pager', $args);

        /**
         * merge user detail
         */
        if ( $this->Field->isExists('screen_name') || $this->Field->isExists('user_id') )
        {
            $user   = array(
                'name'      => $row->user->name,
                'screen_name'=> $row->user->screen_name,
                'user_id'   => $row->user->id,
                'image'     => $row->user->profile_image_url,
                'l-image'   => $this->largeImageUrl($row->user->profile_image_url),
            );
            $Tpl->add('user', $user);
        }

        /**
         * merge fields
         */
        $fds    = $this->Field->listFields();
        $field  = array();
        foreach ( $fds as $fd ) {
            $field[$fd] = $this->Field->get($fd);
        }

        $Tpl->add(null, $field);
    }
}
