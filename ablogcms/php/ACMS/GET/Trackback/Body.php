<?php
/**
 * ACMS_GET_Trackback_Body
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Trackback_Body extends ACMS_GET
{
    var $_scope    = array(
        'eid'   => 'global',
    );

    function get()
    {
        if ( !$this->eid ) return '';
        if ( 'on' <> config('trackback') ) return '';

        $DB     = DB::singleton(dsn());
        $Tpl    = new Template($this->tpl);

        $BaseSQL    = SQL::newSelect('trackback');
        $BaseSQL->addWhereOpr('trackback_entry_id', $this->eid);
        if ( 1
            and !sessionWithCompilation()
            and SUID <> ACMS_RAM::entryUser($this->eid)
        ) {
            $BaseSQL->addWhereOpr('trackback_status', 'close', '<>');
        }

        //--------
        // amount
        $SQL    = new SQL_Select($BaseSQL);
        $SQL->setSelect('*', 'trackback_amount', '', 'count');
        if ( !($amount = intval($DB->query($SQL->get(dsn()), 'one'))) ) return '';

        $desc       = ('datetime-asc' <> config('trackback_body_order'));
        $limit      = config('trackback_body_limit');
        $page       = null;
        $forwardId  = null;
        $backId     = null;
        $isForward  = null;
        $isBack     = null;

        //---------
        // forward
        if ( TBID ) {
            $SQL    = new SQL_Select($BaseSQL);
            $SQL->setSelect('*', 'trackback_pos', '', 'count');
            $SQL->addWhereOpr('trackback_id', TBID, $desc ? '>=' : '<=');
            $pos    = intval($DB->query($SQL->get(dsn()), 'one'));
            $page   = intval(ceil($pos / $limit));
            if ( 1 < $page ) {
                $SQL    = new SQL_Select($BaseSQL);
                $SQL->setSelect('trackback_id');
                $SQL->setOrder('trackback_id', $desc ? 'DESC' : 'ASC');
                $SQL->setLimit(1, ($page - 1) * $limit - 1);
                $forwardId  = intval($DB->query($SQL->get(dsn()), 'one'));
                $isForward  = true;
            }
        } else {
            $page   = 1;
        }
        if ( 1 == $page ) {
            $SQL   = new SQL_Select($BaseSQL);
            $SQL->setSelect('trackback_id');
            $SQL->setOrder('trackback_id', $desc ? 'DESC' : 'ASC');
            $SQL->setLimit(1);
            $forwardId  = intval($DB->query($SQL->get(dsn()), 'one'));
            $isForward  = false;
        }

        //------
        // back
        $SQL    = new SQL_Select($BaseSQL);
        $SQL->setSelect('trackback_id');
        $SQL->setOrder('trackback_id', $desc ? 'DESC' : 'ASC');
        $SQL->setLimit(1, $page*$limit);
        if ( $backId = intval($DB->query($SQL->get(dsn()), 'one')) ) {
            $isBack = true;
        } else {
            $SQL    = new SQL_Select($BaseSQL);
            $SQL->setSelect('trackback_id');
            $SQL->setOrder('trackback_id', !$desc ? 'DESC' : 'ASC');
            $SQL->setLimit(1);
            $backId = intval($DB->query($SQL->get(dsn()), 'one'));
            $isBack = false;
        }

        $reverse    = ('on' == config('trackback_body_reverse'));
        $SQL    = new SQL_Select($BaseSQL);
        $SQL->setOrder('trackback_id', ($desc and !$reverse) ? 'DESC' : 'ASC');
        $SQL->addWhereBw('trackback_id', min($forwardId, $backId), max($forwardId, $backId));
        if ( $isBack ) $SQL->addWhereOpr('trackback_id', $backId, '<>');
        if ( $isForward ) $SQL->addWhereOpr('trackback_id', $forwardId, '<>');



        $q  = $SQL->get(dsn());
        $DB->query($q, 'fetch');

        while ( $row = $DB->fetch($q) ) {
            $tbid   = intval($row['trackback_id']);
            $status = $row['trackback_status'];
            $vars   = $this->buildDate($row['trackback_datetime'], $Tpl, 'trackback:loop');
            $vars   += array(
                'tbid'      => $tbid,
                'title'     => $row['trackback_title'],
                'excerpt'   => $row['trackback_excerpt'],
                'blogName'  => $row['trackback_blog_name'],
                'url'       => $row['trackback_url'],
                'status'    => $status,
            );

            //----------
            // awaiting
            if ( 1
                and 'awaiting' == $status
                and !sessionWithCompilation()
                and SUID <> ACMS_RAM::entryUser($this->eid)
            ) {
                unset($vars['title']);
                unset($vars['excerpt']);
                unset($vars['blogName']);
                unset($vars['url']);
                $Tpl->add('title#awaiting');
                $Tpl->add('excerpt#awaiting');
            }

            if ( sessionWithCompilation() ) {
                if ( 'open' <> $status ) $Tpl->add('status#open');
                if ( 'close' <> $status ) $Tpl->add('status#close');
                if ( 'awaiting' <> $status ) $Tpl->add('status#awaiting');
                $vars['action'] = acmsLink(array(
                    'tbid'  => $tbid,
                    'fragment'  => 'trackback-'.$tbid,
                ));
            }

            $Tpl->add('trackback:loop', $vars);
        }

        if ( $isBack ) {
            $Tpl->add('backLink', array(
                'url'   => acmsLink(array(
                    'tbid'      => $backId,
                    'fragment'  => 'trackback-'.$backId,
                )),
            ));
        }

        if ( $isForward ) {
            $Tpl->add('forwardLink', array(
                'url'   => acmsLink(array(
                    'tbid'      => $forwardId,
                    'fragment'  => 'trackback-'.$forwardId,
                )),
            ));
        }

        $Tpl->add(null, array(
            'amount'    => $amount,
            'from'      => ($page - 1)*$limit + 1,
            'to'        => (($page*$limit) < $amount) ? ($page*$limit) : $amount,
        ));

        return $Tpl->get();
    }
}
