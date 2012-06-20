<?php
/**
 * ACMS_GET_Entry_Field
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Entry_Field extends ACMS_GET
{
    var $_scope = array(
        'eid'   => 'global',
    );

    function get()
    {
        if ( !$this->eid ) return '';
        if ( !$row = ACMS_RAM::entry($this->eid) ) return '';

        $status = ACMS_RAM::entryStatus($this->eid);
        $allow = false;

        // 公開されていなくて編集者未満
        if ( 'open' !== $status && !sessionWithCompilation() ) {
            // 公開期間に該当している or 投稿者かつ自分のエントリー
            if ( 1
                and REQUEST_TIME >= strtotime(ACMS_RAM::entryStartDatetime($this->eid))
                and REQUEST_TIME <= strtotime(ACMS_RAM::entryEndDatetime($this->eid))
            ) {
                $allow = true;
            }
            elseif ( 1
                and sessionWithContribution()
                and SUID == ACMS_RAM::entryUser($this->eid)
            ) {
                $allow = true;
            }
        } else {
            $allow = true;
        }
        if ( !$allow ) return '';

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $Field  = loadEntryField($this->eid);
        foreach ( $row as $key => $val ) {
            $Field->setField(preg_replace('@^entry_@', '', $key), $val);
        }
        $Tpl->add(null, $this->buildField($Field, $Tpl));
        return $Tpl->get();
    }
}
