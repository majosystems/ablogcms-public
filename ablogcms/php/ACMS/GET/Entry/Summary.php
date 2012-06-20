<?php
/**
 * ACMS_GET_Entry_Summary
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Entry_Summary extends ACMS_GET_Entry
{
    var $_axis = array(
        'bid'   => 'self',
        'cid'   => 'self',
    );

    function initVars()
    {
        return array(
            'order'                 => $this->order ? $this->order : config('entry_summary_order'),
            'limit'                 => intval(config('entry_summary_limit')),
            'offset'                => intval(config('entry_summary_offset')),
            'indexing'              => config('entry_summary_indexing'),
            'secret'                => config('entry_summary_secret'),
            'notfound'              => config('mo_entry_summary_notfound'),
            'notfoundStatus404'     => config('entry_summary_notfound_status_404'),
            'noimage'               => config('entry_summary_noimage'),
            'pagerDelta'            => config('entry_summary_pager_delta'),
            'pagerCurAttr'          => config('entry_summary_pager_cur_attr'),

            'unit'                  => config('entry_summary_unit'),
            'newtime'               => config('entry_summary_newtime'),
            'imageX'                => intval(config('entry_summary_image_x')),
            'imageY'                => intval(config('entry_summary_image_y')),
            'imageTrim'             => config('entry_summary_image_trim'),
            'imageZoom'             => config('entry_summary_image_zoom'),
            'imageCenter'           => config('entry_summary_image_center'),

            'entryFieldOn'          => config('entry_summary_entry_field'),
            'categoryInfoOn'        => config('entry_summary_category_on'),
            'categoryFieldOn'       => config('entry_summary_category_field_on'),
            'userInfoOn'            => config('entry_summary_user_on'),
            'userFieldOn'           => config('entry_summary_user_field_on'),
            'blogInfoOn'            => config('entry_summary_blog_on'),
            'blogFieldOn'           => config('entry_summary_blog_field_on'),
            'pagerOn'               => config('entry_summary_pager_on'),
            'mainImageOn'           => config('entry_summary_image_on'),
            'detailDateOn'          => config('entry_summary_date'),
            'fullTextOn'            => config('entry_summary_fulltext'),
        );
    }

    function get()
    {
        $config = $this->initVars();

        $order  = $config['order'];
        $this->initVars();
        if ( !empty($order) ) { $config['order'] = $order; }

        $DB     = DB::singleton(dsn());
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());

        $SQL    = SQL::newSelect('entry');
        $SQL->addLeftJoin('category', 'category_id', 'entry_category_id');
        $SQL->addLeftJoin('blog', 'blog_id', 'entry_blog_id');

        ACMS_Filter::blogTree($SQL, $this->bid, $this->blogAxis());
        if ( 'on' === $config['secret'] ) {
            ACMS_Filter::blogDisclosureSecretStatus($SQL);
        } else {
            ACMS_Filter::blogStatus($SQL);
        }
        ACMS_Filter::categoryTree($SQL, $this->cid, $this->categoryAxis());
        ACMS_Filter::categoryStatus($SQL);

        if ( $uid = intval($this->uid) ) {
            $SQL->addWhereOpr('entry_user_id', $uid);
        }

        if ( empty($this->cid) and null !== $this->cid ) {
            $SQL->addWhereOpr('entry_category_id', null);
        }

        if ( !empty($this->eid) ) {
            $SQL->addWhereOpr('entry_id', $this->eid);
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
        if ( 'on' === $config['indexing'] ) {
            $SQL->addWhereOpr('entry_indexing', 'on');
        }
        if ( 'on' <> $config['noimage'] ) {
            $SQL->addWhereOpr('entry_primary_image', null, '<>');
        }

        $Amount = new SQL_Select($SQL);
        $Amount->setSelect('*', 'entry_amount', null, 'count');
        if ( !$itemsAmount = intval($DB->query($Amount->get(dsn()), 'one')) ) {
            if ( 'on' == $config['notfound'] ) {
                $Tpl->add('notFound');
                $blogName   = ACMS_RAM::blogName($this->bid);
                $vars   = array(
                    'indexUrl'  => acmsLink(array(
                        'bid'   => $this->bid,
                        'cid'   => $this->cid,
                    )),
                    'indexBlogName' => $blogName,
                );
                if ( !empty($this->cid) ) {
                    $categoryName   = ACMS_RAM::categoryName($this->cid);
                    $vars['indexCategoryName']  = $categoryName;
                }
                $Tpl->add(null, $vars);
                if ( 'on' == $config['notfoundStatus404'] ) {
                    httpStatusCode('404 Not Found');
                }
                return $Tpl->get();
            } else {
                return false;
            }
        }

        ACMS_Filter::entryOrder($SQL, $config['order'], $this->uid, $this->cid);
        $from   = ($this->page - 1) * $config['limit'] + $config['offset'];

        $limit  = ((($from + $config['limit']) > $itemsAmount) ? ($itemsAmount - $from) : $config['limit']);
        if ( 1 > $limit ) return '';

        $SQL->setLimit($limit, ($from));
        $q  = $SQL->get(dsn());

        //------------------
        //build summary tpl
        $remainingEntries = $itemsAmount - $from;
        $gluePoint = ($remainingEntries > $limit) ? $limit : $remainingEntries;

        $i = 0;
        $DB->query($q, 'fetch');
        while ( $row = $DB->fetch($q) ) {
            $i++;
            $this->buildSummary($Tpl, $row, $i, $gluePoint, $config);
        }

        $blogName   = ACMS_RAM::blogName($this->bid);
        $vars   = array(
            'indexUrl'  => acmsLink(array(
                'bid'   => $this->bid,
                'cid'   => $this->cid,
            )),
            'indexBlogName' => $blogName,
            'blogName'      => $blogName,
        );
        if ( !empty($this->cid) ) {
            $categoryName   = ACMS_RAM::categoryName($this->cid);
            $vars['indexCategoryName']  = $categoryName;
            $vars['categoryName']       = $categoryName;
        }

        if ( 'random' <> $config['order'] ) {
            //-------
            // pager
            if ( !isset($config['pagerOn']) or $config['pagerOn'] === 'on' ) {
                $vars += $this->buildPager($this->page, $config['limit'], $itemsAmount, $config['pagerDelta'], $config['pagerCurAttr'], $Tpl);
            }
        }

        $Tpl->add(null, $vars);

        return $Tpl->get();
    }
}