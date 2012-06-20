<?php
/**
 * ACMS_GET_Api_Instagram_Users_Media
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Api_Instagram_Users_Media extends ACMS_GET_Api_Instagram
{
    function build($json, & $Tpl) {
        $json = json_decode($json, true);

        $photos = $json['data'];

        foreach ( $photos as $photo ) {
            $vars = array(
                'smallImg'      => $photo['images']['low_resolution']['url'],
                'smallWidth'    => $photo['images']['low_resolution']['width'],
                'smallHeight'   => $photo['images']['low_resolution']['height'],
                'thumbImg'      => $photo['images']['thumbnail']['url'],
                'thumbWidth'    => $photo['images']['thumbnail']['width'],
                'thumbHeight'   => $photo['images']['thumbnail']['height'],
                'largeImg'      => $photo['images']['standard_resolution']['url'],
                'largeWidth'    => $photo['images']['standard_resolution']['width'],
                'largeHeight'   => $photo['images']['standard_resolution']['height'],
                'link'          => $photo['link'],
                'caption'       => $photo['caption']['text'],
                'userName'      => $photo['user']['username'],
                'profileImg'    => $photo['user']['profile_picture'],
                'userId'        => $photo['user']['id'],
                'createdTime'   => date('Y-m-d H:i:s', $photo['created_time']),
                'type'          => $photo['type'],
                'filter'        => $photo['filter'],
                'countComments' => $photo['comments']['count'],
                'countLikes'    => $photo['likes']['count'],
            );
            if ( !empty($vars['locatoin']) ) {
                $vars += array(
                    'lat'           => $photo['location']['latitude'],
                    'lng'           => $photo['location']['longitude'],
                    'place'         => $photo['location']['name']
                );
            }
            $Tpl->add('photo:loop', $vars);
        }
        $Tpl->add('pager', array_clean(array(
            'next_max_id'   => @$json['pagination']['next_max_id']
        )));
    }
}
