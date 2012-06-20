<?php
/**
 * ACMS_GET_Column_List
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
/**
 * LEGACY CLASS
 *
 * @old ACMS_GET_Column_List
 * @new ACMS_GET_Unit_List
 */
class ACMS_GET_Column_List extends ACMS_GET_Unit_List
{
    var $_axis = array(
        'bid'   => 'descendant-or-self',
        'cid'   => 'descendant-or-self',
    );

    var $_scope = array(
        'cid'       => 'global',
        'eid'       => 'global',
        'start'     => 'global',
        'end'       => 'global',
    );

    function get()
    {
        return parent::get();
    }
}
