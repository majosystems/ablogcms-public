<?php
/**
 * ACMS_GET_Unit_Fetch
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Unit_Fetch extends ACMS_GET_Unit
{
    function get()
    {
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());

        $utid       = $this->Post->get('utid', UTID);
        $eid        = $this->Post->get('eid', EID);
        $seeked     = false;
        $preAlign   = null;

        // if Add
        if ( empty($utid) ) {
            $sort = $this->Get->get('sort');
            $DB     = DB::singleton(dsn());
            $SQL    = SQL::newSelect('column');
            $SQL->addSelect('column_id');
            $SQL->addWhereOpr('column_sort', $sort);
            $SQL->addWhereOpr('column_entry_id', $eid);
            $utid   = $DB->query($SQL->get(dsn()), 'one');
        }

        if ( $Column = array_reverse(loadColumn($eid)) ) {
            foreach ( $Column as $i => $row ) {
                if ( $seeked !== false ) {
                    $preAlign = $row['align'];
                    $seeked   = false;
                }

                if ( $row['clid'] != $utid ) {
                    unset($Column[$i]);
                } else {
                    $seeked = true;
                }
            }
            $this->buildUnit($Column, $Tpl, $eid, $preAlign, false);
        }

        return $Tpl->get();
    }
}
