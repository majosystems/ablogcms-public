<?php
/**
 * ACMS_GET_Trackback_List
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Trackback_List extends ACMS_GET
{
    function get()
    {
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('trackback');
        $SQL->addSelect('trackback_blog_id');
        $SQL->addSelect('trackback_entry_id');
        $SQL->addSelect('trackback_id');
        $SQL->addSelect('trackback_status');
        $SQL->addSelect('trackback_title');
        $SQL->addSelect('trackback_blog_name');
        $SQL->addSelect('trackback_datetime');

        $SQL->addWhereOpr('trackback_blog_id', $this->bid);
        $SQL->addWhereOpr('trackback_flow', 'receive');
        if ( !sessionWithCompilation() ) {
            $SQL->addLeftJoin('entry', 'entry_id', 'trackback_entry_id');
            $SQL->addWhereOpr('entry_status', 'open' , '=');
            $SQL->addWhereOpr('entry_indexing', 'on');
            $SQL->addWhereOpr('trackback_status', 'close', '<>');
        }
        $SQL->setOrder('trackback_id', 
            'datetime-asc' <> config('trackback_list_order') ? 'DESC' : 'ASC'
        );
        $SQL->setLimit(intval(config('trackback_list_limit')));

        $q  = $SQL->get(dsn());
        if ( !$DB->query($q, 'fetch') or !($row = $DB->fetch($q)) ) return '';
        do {
            $bid    = intval($row['trackback_blog_id']);
            $eid    = intval($row['trackback_entry_id']);
            $tbid   = intval($row['trackback_id']);
            $status = $row['trackback_status'];
            $vars   = array(
                'bid'   => $bid,
                'eid'   => $eid,
                'tbid'  => $tbid,
                'title' => $row['trackback_title'],
                'blog_name'  => $row['trackback_blog_name'],
                'url'   => acmsLink(array(
                    'bid'   => $bid,
                    'eid'   => $eid,
                    'tbid'  => $tbid,
                )),
                'status'=> $status,
            );
            if ( 'awaiting' == $status and !sessionWithCompilation() ) {
                unset($vars['title']);
                unset($vars['blog_name']);
                $Tpl->add('awaiting');
            }
            $vars   += $this->buildDate(strtotime($row['trackback_datetime']), $Tpl, 'trackback:loop');
            $Tpl->add('trackback:loop', $vars);
        } while ( $row = $DB->fetch($q) );

        return $Tpl->get();
    }
}
