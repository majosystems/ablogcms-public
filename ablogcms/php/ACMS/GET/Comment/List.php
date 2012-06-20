<?php
/**
 * ACMS_GET_Comment_List
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Comment_List extends ACMS_GET
{
    function get()
    {
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('comment');
        $SQL->addSelect('comment_blog_id');
        $SQL->addSelect('comment_entry_id');
        $SQL->addSelect('comment_id');
        $SQL->addSelect('comment_status');
        $SQL->addSelect('comment_title');
        $SQL->addSelect('comment_name');
        $SQL->addSelect('comment_datetime');

        $SQL->addWhereOpr('comment_blog_id', $this->bid);
        if ( !sessionWithCompilation() ) {
            $SQL->addLeftJoin('entry', 'entry_id', 'comment_entry_id');
            $SQL->addWhereOpr('entry_status', 'open' , '=');
            $SQL->addWhereOpr('entry_indexing', 'on');
            $SQL->addWhereOpr('comment_status', 'close', '<>');
        }
        $SQL->setOrder('comment_id',
            'datetime-asc' <> config('comment_list_order') ? 'DESC' : 'ASC'
        );
        $SQL->setLimit(intval(config('comment_list_limit')));

        $q  = $SQL->get(dsn());
        if ( !$DB->query($q, 'fetch') or !($row = $DB->fetch($q)) ) return '';
        do {
            $bid    = intval($row['comment_blog_id']);
            $eid    = intval($row['comment_entry_id']);
            $cmid   = intval($row['comment_id']);
            $status = $row['comment_status'];
            $vars   = array(
                'bid'   => $bid,
                'eid'   => $eid,
                'cmid'  => $cmid,
                'title' => $row['comment_title'],
                'name'  => $row['comment_name'],
                'url'   => acmsLink(array(
                    'bid'   => $bid,
                    'eid'   => $eid,
                    'cmid'  => $cmid,
                )),
                'status'=> $status,
            );
            if ( 'awaiting' == $status and !sessionWithCompilation() ) {
                unset($vars['title']);
                unset($vars['name']);
                $Tpl->add('awaiting');
            }
            $vars   += $this->buildDate(strtotime($row['comment_datetime']), $Tpl, 'comment:loop');
            $Tpl->add('comment:loop', $vars);
        } while ( $row = $DB->fetch($q) );

        return $Tpl->get();
    }
}
