<?php
/**
 * ACMS_GET_Api_Instagram_Users_Media_Recent
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Api_Instagram_Users_Media_Recent extends ACMS_GET_Api_Instagram_Users_Media
{
    var $_scope = array(
        'field'     => 'global',
    );

    function get() {

        $this->id     = $this->bid;
        $this->api    = '/users/self/media/recent';
        $this->params = array_clean(array(
            'max_id'  => ($next_max_id = $this->Field->get('next_max_id')) ? intval($next_max_id) : null,
            'count'   => !!LIMIT ? LIMIT : config('instagram_users_media_recet_limit')
        ));
        $this->crit   = config('instagram_users_media_recet_cache_expire');

        $Tpl = new Template($this->tpl, new ACMS_Corrector());

        $this->resolveRequest($Tpl, 'instagram');

        return $Tpl->get();
    }
}
