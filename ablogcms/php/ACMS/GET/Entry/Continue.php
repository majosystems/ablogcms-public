<?php
/**
 * ACMS_GET_Entry_Continue
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Entry_Continue extends ACMS_GET_Entry
{
    var $_axis = array(
        'bid'   => 'descendant-or-self',
        'cid'   => 'descendant-or-self',
    );

    var $_scope = array(
        'uid'       => 'global',
        'cid'       => 'global',
        'eid'       => 'global',
        'keyword'   => 'global',
        'tag'       => 'global',
        'field'     => 'global',
        'date'      => 'global',
        'start'     => 'global',
        'end'       => 'global',
        'page'      => 'global',
    );

    function get()
    {
        $DB     = DB::singleton(dsn());
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());

        $SQL    = SQL::newSelect('entry');
        $SQL->addWhereOpr('entry_id', $this->eid);

        $q      = $SQL->get(dsn());
        if ( !$row = $DB->query($q, 'row') ) {
            $Tpl->add('notFound');
            return $Tpl->get();
        }
        if ( !IS_LICENSED ) $row['entry_title'] = '[test]'.$row['entry_title'];

        $bid    = $row['entry_blog_id'];
        $uid    = $row['entry_user_id'];
        $cid    = $row['entry_category_id'];
        $eid    = $row['entry_id'];
        $link   = $row['entry_link'];
        $datetime   = $row['entry_datetime'];
        $inheritUrl = acmsLink(array(
            'eid'       => $eid,
        ));

        $vars   = array();

        //---------
        // column
        if ( $Column = loadColumn($eid) ) {
            $Display = array_slice($Column, $row['entry_summary_range']);
            if ( !empty($Display) ) {
                $this->buildColumn($Display, $Tpl, $eid);
            }
        }

        //-------
        // field
        if ( 'on' == config('entry_continue_field') ) {
            $vars   += $this->buildField(loadEntryField($this->eid), $Tpl, null, 'entry');
        }

        $vars   += array(
            'status'    => $row['entry_status'],
            'url'       => !empty($link) ? $link : $inheritUrl,
            'title'     => addPrefixEntryTitle($row['entry_title']
                , $row['entry_status']
                , $row['entry_start_datetime']
                , $row['entry_end_datetime']
            ),
            'bid'       => $bid,
            'cid'       => $cid,
            'eid'       => $eid,
        );

        //------
        // date
        $vars   += $this->buildDate($row['entry_datetime'], $Tpl, null);
        $vars   += $this->buildDate($row['entry_updated_datetime'], $Tpl, null, 'udate#');
        $vars   += $this->buildDate($row['entry_posted_datetime'], $Tpl, null, 'pdate#');

        $Tpl->add(null, $vars);

        return $Tpl->get();
    }
}