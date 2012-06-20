<?php
/**
 * ACMS_GET_Comment_Body
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Comment_Body extends ACMS_GET
{
    var $map    = array();
    var $score  = array();
    var $status = array();

    function get()
    {
        if ( !EID ) return false;
        if ( ADMIN ) return false;

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());

        if ( ALT or !$this->Post->isNull() ) {
            $DB     = DB::singleton(dsn());
            $SQL    = SQL::newSelect('comment');
            $SQL->addSelect('comment_id');
            $SQL->addSelect('comment_datetime');
            $SQL->addSelect('comment_status');
            $SQL->addSelect('comment_title');
            $SQL->addSelect('comment_body');
            $SQL->addSelect('comment_name');
            $SQL->addSelect('comment_mail');
            $SQL->addSelect('comment_url');
            $SQL->addSelect('comment_parent');
            $SQL->addLeftJoin('user', 'user_id', 'comment_user_id');
            $SQL->addSelect('user_name');
            $SQL->addSelect('user_mail');
            $SQL->addSelect('user_url');
            $SQL->addWhereOpr('comment_id', CMID);
            $q  = $SQL->get(dsn());
            if ( !$row = $DB->query($q, 'row') ) return '';

            $Tpl->add('div#front');
            $Tpl->add('div#rear');
            $this->buildComment($Tpl, array(), $row);
        } else if ( 'thread' == config('comment_body_display') ) {
            $this->buildThread($Tpl);
        } else {
            $this->buildList($Tpl);
        }

        return $Tpl->get();

    }

    function buildComment(& $Tpl, $vars=array(), $row)
    {
        $cmid   = $row['comment_id'];
        $status = $row['comment_status'];

        if ( !sessionWithAdministration() and 'awaiting' == $row['comment_status'] ) {
            $Tpl->add('awaiting#header');
            $Tpl->add('awaiting#body');
        } else {
            $vars['title']  = $row['comment_title'];
            $vars['body']   = $row['comment_body'];

            if ( !empty($row['comment_user_id']) ) {
                $name   = $row['user_name'];
                $mail   = $row['user_mail'];
                $url    = $row['user_url'];
            } else {
                $name   = $row['comment_name'];
                $mail   = $row['comment_mail'];
                $url    = $row['comment_url'];
            }

            $vars['posterName']   = $name;
            if ( !empty($url) ) {
                $Tpl->add('posterLink#front', array('url' => $url));
                $Tpl->add('posterLink#rear');
            }
            if ( !empty($mail) ) {
                $Tpl->add('posterMail#front', array('mail' => $mail));
                $Tpl->add('posterMail#rear');
            }
        }

        $vars['cmid']   = $cmid;
        $vars['status'] = $status;

        //------
        // date
        $vars   += $this->buildDate($row['comment_datetime'], $Tpl, 'comment:loop');

        if ( $this->Post->isNull() ) {
            $vars   += array(
                'target'    => acmsLink(array(
                    'eid'       => EID,
                    'cmid'      => $cmid,
                    'fragment'  => 'comment-'.$cmid,
                )),
            );
            if ( 1
                and !!SUID
                and sessionWithContribution()
                and ( 0
                    or sessionWithCompilation() 
                    or ACMS_RAM::entryUser(EID) == SUID 
                    or ACMS_RAM::commentUser($cmid) == SUID 
                )
            ) {
                $pstatus    = 'open';
                if ( ($pid = intval($row['comment_parent'])) and isset($this->status[$pid]) ) {
                    $pstatus    = $this->status[$pid];
                }
                if ( 'open' <> $status and 'open' == $pstatus ) $Tpl->add('status#open');
                if ( 'close' <> $status and 'open' == $pstatus ) $Tpl->add('status#close');
                if ( 'awaiting' <> $status and 'open' == $pstatus ) $Tpl->add('status#awaiting');
            }
        }

        $Tpl->add('comment:loop', $vars);

        return true;
    }

    function buildThread(& $Tpl)
    {
        $limit  = config('comment_body_limit');
        $DB     = DB::singleton(dsn());

        $SQL    = SQL::newSelect('comment');
        $SQL->addSelect('*', 'comment_amount', null, 'COUNT');
        $SQL->addWhereOpr('comment_entry_id', EID);
        if ( !sessionWithCompilation() and (ACMS_RAM::entryUser(EID) <> SUID) ) {
            $SQL->addWhereOpr('comment_status', 'close', '<>');
        }
        if ( !$amount = $DB->query($SQL->get(dsn()), 'one') ) return false;

        $from   = 0;
        $page   = 1;
        if ( CMID ) {
            $SQL    = SQL::newSelect('comment');
            $SQL->addSelect('*', 'comment_amount', null, 'COUNT');
            $SQL->addWhereOpr('comment_right', intval(ACMS_RAM::commentRight(CMID)), '>=');
            $SQL->addWhereOpr('comment_entry_id', EID);
            if ( !sessionWithCompilation() and (ACMS_RAM::entryUser(EID) <> SUID) ) {
                $SQL->addWhereOpr('comment_status', 'close', '<>');
            }
            $SQL->addOrder('comment_right', 'DESC');
            $cnt    = $DB->query($SQL->get(dsn()), 'one');
            $page   = ceil($cnt / $limit);
        }
        $from   = ($page - 1) * $limit;
        $offset = empty($from) ? 1 : 2;

        $SQL    = SQL::newSelect('comment');
        $SQL->addSelect('comment_id');
        $SQL->addSelect('comment_datetime');
        $SQL->addSelect('comment_status');
        $SQL->addSelect('comment_title');
        $SQL->addSelect('comment_body');
        $SQL->addSelect('comment_name');
        $SQL->addSelect('comment_mail');
        $SQL->addSelect('comment_url');
        $SQL->addSelect('comment_left');
        $SQL->addSelect('comment_right');
        $SQL->addSelect('comment_parent');
        $SQL->addLeftJoin('user', 'user_id', 'comment_user_id');
        $SQL->addSelect('user_name');
        $SQL->addSelect('user_url');
        $SQL->addWhereOpr('comment_entry_id', EID);
        if ( !sessionWithCompilation() and (ACMS_RAM::entryUser(EID) <> SUID) ) {
            $SQL->addWhereOpr('comment_status', 'close', '<>');
        }
        $SQL->setLimit($limit + $offset, empty($from) ? 0 : $from - 1);
        $SQL->addOrder('comment_right', 'DESC');
        $q  = $SQL->get(dsn());
        if ( !$DB->query($q, 'fetch') ) return false;

        $aligns = array();
        if ( !empty($from) and $row = $DB->fetch($q) ) {
            $cmid   = intval($row['comment_id']);
            $l      = intval($row['comment_left']);
            $r      = intval($row['comment_right']);
            $Tpl->add('forwardLink', array('url' => acmsLink(array(
                'cmid'      => $cmid,
                'fragment'  => 'comment-'.$cmid,
            ))));
        }

        $row    = $DB->fetch($q);
        if ( $row['comment_parent'] ) {
            $SQL    = SQL::newSelect('comment');
            $SQL->addSelect('comment_left');
            $SQL->addSelect('comment_right');
            $SQL->addWhereOpr('comment_entry_id', EID);
            $SQL->addWhereOpr('comment_left', $row['comment_left'], '<');
            $SQL->addWhereOpr('comment_right', $row['comment_right'], '>');
            if ( !sessionWithCompilation() and (ACMS_RAM::entryUser(EID) <> SUID) ) {
                $SQL->addWhereOpr('comment_status', 'close', '<>');
            }
            $SQL->addOrder('comment_left', 'ASC');
            $all    = $DB->query($SQL->get(dsn()), 'all');

            foreach ( $all as $_row ) {
                array_push($aligns, array(
                    'l' => intval($_row['comment_left']),
                    'r' => intval($_row['comment_right']),
                ));
                $Tpl->add('div#front');
                $Tpl->add('comment:loop');
            }
        }
        array_push($aligns, array(
            'l' => $row['comment_left'],
            'r' => $row['comment_right'],
        ));
        $Tpl->add('div#front');
        $this->buildComment($Tpl, array(
            'replyUrl'  => acmsLink(array(
                'bid'   => BID,
                'cid'   => CID,
                'eid'   => EID,
                'alt'   => 'reply',
                'cmid'  => $row['comment_id'],
            )),
        ), $row);

        $i  = 1;
        while ( true ) {
            if ( ($limit > $i) and ($row = $DB->fetch($q)) ) {
                $l  = intval($row['comment_left']);
                $r  = intval($row['comment_right']);

                $cmid   = intval($row['comment_id']);
                $this->status[$cmid]    = $row['comment_status'];
                if ( 1 == $i ) {
                    if ( $pid = intval($row['comment_parent']) ) {
                        $this->status[$pid] = ACMS_RAM::commentStatus($pid);
                    }
                }
            } else {
                $l  = 2147483647;
                $r  = 2147483647;
            }

            while ( $a = array_pop($aligns) ) {
                if ( $r < $a['r'] and $l > $a['l'] ) {
                    array_push($aligns, $a);
                    break;
                }
                $Tpl->add('div#rear');
                $Tpl->add('comment:loop');
            }

            if ( ($limit <= $i) or empty($row) ) break;

            array_push($aligns, array(
                'l' => $l,
                'r' => $r,
            ));

            $Tpl->add('div#front');
            $this->buildComment($Tpl, array(
                'replyUrl'  => acmsLink(array(
                    'bid'   => BID,
                    'cid'   => CID,
                    'eid'   => EID,
                    'alt'   => 'reply',
                    'cmid'  => $row['comment_id'],
                )),
            ), $row);

            $i++;
        }

        if ( $row = $DB->fetch($q) ) {
            $cmid   = $row['comment_id'];
            $Tpl->add('backLink' , array('url' => acmsLink(array(
                'cmid'      => $cmid,
                'fragment'  => 'comment-'.$cmid,
            ))));
        }

        $Tpl->add(null, array(
            'amount'    => $amount,
            'from'      => $from + 1,
            'to'        => $amount > ($from + $limit) ? ($from + $limit) : $amount,
        ));

        return true;
    }

    function buildList(& $Tpl)
    {
        $DB     = DB::singleton(dsn());

        $limit  = config('comment_body_limit');

        list($kipple, $order) = explode('-', config('comment_body_order'));
        $desc   = 'DESC' == strtoupper($order) ? true : false;
        $rev    = 'on' == config('comment_body_reverse');

        $SQL    = SQL::newSelect('comment');
        $SQL->addSelect('*', 'comment_amount', null, 'count');
        $SQL->addWhereOpr('comment_entry_id', EID);
        if ( !sessionWithCompilation() and (ACMS_RAM::entryUser(EID) <> SUID) ) {
            $SQL->addWhereOpr('comment_status', 'close', '<>');
        }
        if ( !$amount = $DB->query($SQL->get(dsn()), 'one') ) return false;

        $from   = 0;
        $page   = 1;
        if ( CMID ) {
            $SQL    = SQL::newSelect('comment');
            $SQL->addSelect('*', 'comment_amount', null, 'COUNT');
            $SQL->addWhereOpr('comment_id', CMID, $desc ? '>=' : '<=');
            $SQL->addWhereOpr('comment_entry_id', EID);
            if ( !sessionWithCompilation() and (ACMS_RAM::entryUser(EID) <> SUID) ) {
                $SQL->addWhereOpr('comment_status', 'close', '<>');
            }
            $SQL->addOrder('comment_id', $desc ? 'DESC' : 'ASC');
            $cnt    = $DB->query($SQL->get(dsn()), 'one');

            $page   = ceil($cnt / $limit);
        }
        $from   = ($page - 1) * $limit;

        $leftPos    = $from - 1;
        if ( 0 < $leftPos ) {
            $SQL    = SQL::newSelect('comment');
            $SQL->addSelect('comment_id');
            $SQL->addWhereOpr('comment_entry_id', EID);
            if ( !sessionWithCompilation() and (ACMS_RAM::entryUser(EID) <> SUID) ) {
                $SQL->addWhereOpr('comment_status', 'close', '<>');
            }
            $SQL->setLimit(1, $leftPos);
            $SQL->addOrder('comment_id', $desc ? 'DESC' : 'ASC');
            $leftCmid = $DB->query($SQL->get(dsn()), 'one');
        }

        $rightPos   = $from + $limit;
        if ( $amount > $rightPos ) {
            $SQL    = SQL::newSelect('comment');
            $SQL->addSelect('comment_id');
            $SQL->addWhereOpr('comment_entry_id', EID);
            if ( !sessionWithCompilation() and (ACMS_RAM::entryUser(EID) <> SUID) ) {
                $SQL->addWhereOpr('comment_status', 'close', '<>');
            }
            $SQL->setLimit(1, $rightPos);
            $SQL->addOrder('comment_id', $desc ? 'DESC' : 'ASC');
            $rightCmid   = $DB->query($SQL->get(dsn()), 'one');
            $to = $rightPos;
        } else {
            $to = $amount;
        }

        if ( isset($leftCmid) ) {
            $Tpl->add($desc ? 'forwardLink' : 'backLink', array('url' => acmsLink(array(
                'cmid'      => $leftCmid,
                'fragment'  => 'comment-'.$leftCmid,
            ))));
        }
        if ( isset($rightCmid) ) {
            $Tpl->add($desc ? 'backLink' : 'forwardLink' , array('url' => acmsLink(array(
                'cmid'      => $rightCmid,
                'fragment'  => 'comment-'.$rightCmid,
            ))));
        }

        $SQL    = SQL::newSelect('comment');
        $SQL->addSelect('comment_id');
        $SQL->addSelect('comment_datetime');
        $SQL->addSelect('comment_status');
        $SQL->addSelect('comment_title');
        $SQL->addSelect('comment_body');
        $SQL->addSelect('comment_name');
        $SQL->addSelect('comment_mail');
        $SQL->addSelect('comment_url');
        $SQL->addSelect('comment_parent');
        $SQL->addLeftJoin('user', 'user_id', 'comment_user_id');
        $SQL->addSelect('user_name');
        $SQL->addSelect('user_url');
        $SQL->addWhereOpr('comment_entry_id', EID);
        if ( !sessionWithCompilation() and (ACMS_RAM::entryUser(EID) <> SUID) ) {
            $SQL->addWhereOpr('comment_status', 'close', '<>');
        }
        if ( isset($rightCmid) ) $SQL->addWhereOpr('comment_id', $rightCmid, $desc ? '>' : '<');
        if ( isset($leftCmid) ) $SQL->addWhereOpr('comment_id', $leftCmid, $desc ? '<' : '>');
        $SQL->setLimit($limit);

        $SQL->addOrder('comment_id', (($rev ? !$desc : $desc) ? 'DESC' : 'ASC'));
        $q  = $SQL->get(dsn());

        if ( !$DB->query($q, 'fetch') ) return false;
        $i  = 1;
        while ( $row = $DB->fetch($q) ) {
            $Tpl->add('div#front');
            $Tpl->add('div#rear');

            $seq    = $desc ? ($rev ? ($amount - $to + $i) : ($amount - $from - $i  + 1)) :
                ($rev ? ($to - $i + 1) : ($from + $i))
            ;

            $vars   = array('seq' => $seq);
            $this->buildComment($Tpl, $vars, $row);

            $i++;
        }

        if ( $desc ) {
            $pageFrom   = $amount - $to + 1;
            $pageTo     = $amount - $from;
        } else {
            $pageFrom   = $from + 1;
            $pageTo     = $to;
        }

        $Tpl->add(null, array(
            'itemsAmount'    => $amount,
            'itemsFrom'      => $pageFrom,
            'itemsTo'        => $pageTo,
        ));

        return true;
    }
}
