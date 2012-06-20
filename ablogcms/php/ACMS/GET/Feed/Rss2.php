<?php
/**
 * ACMS_GET_Feed_Rss2
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Feed_Rss2 extends ACMS_GET_Entry
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
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $DB     = DB::singleton(dsn());

        $limit  = config('feed_rss2_limit');
        $order  = ORDER ? ORDER : config('feed_rss2_order');

        $SQL    = SQL::newSelect('entry');
        $SQL->addLeftJoin('category', 'category_id', 'entry_category_id');
        $SQL->addLeftJoin('blog', 'blog_id', 'entry_blog_id');

        ACMS_Filter::blogTree($SQL, $this->bid, $this->blogAxis());
        ACMS_Filter::blogStatus($SQL);
        ACMS_Filter::categoryTree($SQL, $this->cid, $this->categoryAxis());
        ACMS_Filter::categoryStatus($SQL);

        if ( !empty($this->eid) ) {
            $SQL->addWhereOpr('entry_id', $this->eid);
        } else {
            $SQL->addWhereOpr('entry_indexing', 'on');
        }
        ACMS_Filter::entrySession($SQL);
        ACMS_Filter::entrySpan($SQL, $this->start, $this->end);

        if ( !empty($this->tags) ) {
            ACMS_Filter::entryTag($SQL, $this->tags);
        }
        if ( !empty($this->keyword) ) {
            ACMS_Filter::entryKeyword($SQL, $this->keyword);
        }
        if ( !empty($this->Field) ) {
            ACMS_Filter::entryField($SQL, $this->Field);
        }

        $Amount = new SQL_Select($SQL);
        $Amount->setSelect('*', 'entry_amount', null, 'COUNT');
        if ( !$pageAmount = $DB->query($Amount->get(dsn()), 'one') ) {
            return '';
        }

        $from   = 0;
        $SQL->setLimit(($from + $limit > $pageAmount) ? ($pageAmount - $from) : $limit, $from);
        ACMS_Filter::entryOrder($SQL, $order, $this->uid, $this->cid);

        $q  = $SQL->get(dsn());
        $DB->query($q, 'fetch');

        $lastBuildDate  = '1000-01-01 00:00:00';
        while ( $row = $DB->fetch($q) ) {
            $bid    = $row['entry_blog_id'];
            $uid    = $row['entry_user_id'];
            $cid    = $row['entry_category_id'];
            $eid    = $row['entry_id'];
            $link   = $row['entry_link'];
            $title  = $row['entry_title'];
            $summaryRange   = $row['entry_summary_range'];
            $permalink  = acmsLink(array(
                'bid'   => $bid,
                'cid'   => $cid,
                'eid'   => $eid,
            ));

            $vars   = array(
                'title'     => $row['entry_title'],
                'link'      => !empty($link) ? $link : $permalink,
                'creator'   => ACMS_RAM::userName($uid),
                'permalink' => $permalink,
                'pubDate'   => date('r', strtotime($row['entry_datetime'])),
            );

            if ( !empty($cid) ) {
                $vars['category'] = ACMS_RAM::categoryName($cid);
            }

            //--------
            // column
            if ( empty($link) or 'on' == config('feed_rss2_outsidelink_description') ) {
                if ( $Column = loadColumn($eid, $summaryRange) ) {
                    $this->buildColumn($Column, $Tpl, $eid);
                    if ( !empty($summaryRange) ) {
                        $SQL    = SQL::newSelect('column');
                        $SQL->addSelect('*', 'column_amount', null, 'COUNT');
                        $SQL->addWhereOpr('column_entry_id', $eid);
                        $amount = $DB->query($SQL->get(dsn()), 'one');
                        if ( $summaryRange < $amount ) {
                            $vars['continueUrl']    = $permalink;
                            $vars['continueName']   = $title;
                        }
                    }
                }
            }

            $Tpl->add('item:loop', $vars);

            if ( $lastBuildDate < $row['entry_updated_datetime'] ) {
                $lastBuildDate = $row['entry_updated_datetime'];
            }
        }

        $Tpl->add(null, array(
            'lastBuildDate' => date('r', strtotime($lastBuildDate))
        ));
        return $Tpl->get();
    }
}
