<?php
/**
 * ACMS_GET_Touch_EditInsert
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Touch_EditInsert extends ACMS_GET
{
    function get()
    {
        return ( 1
            and !EID
            and !!ADMIN
            and ( 0
                or 'entry-edit' == ADMIN
                or 'entry-add' == substr(ADMIN, 0, 9)
            )
        ) ? $this->tpl : false;
    }
}
