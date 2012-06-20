<?php
/**
 * ACMS_GET_Entry_TagRelational
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Entry_TagRelational extends ACMS_GET_Entry_Summary
{
    var $_axis = array(
        'bid'   => 'self',
        'cid'   => 'self',
    );

    var $_scope = array(
        'eid'   => 'global',
    );

    function initVars()
    {
        return array(
            'order'            => $this->order ? $this->order : config('entry_tag-relational_order'),
            'limit'            => intval(config('entry_tag-relational_limit')),
            'indexing'         => config('entry_tag-relational_indexing'),
            'secret'           => config('entry_tag-relational_secret'),
            'notfound'         => config('mo_entry_tag-relational_notfound'),
            'notfoundStatus404'=> config('entry_tag-relational_notfound_status_404'),
            'noimage'          => config('entry_tag-relational_noimage'),
            'imageX'           => intval(config('entry_tag-relational_image_x')),
            'imageY'           => intval(config('entry_tag-relational_image_y')),
            'imageTrim'        => config('entry_tag-relational_image_trim'),
            'imageZoom'        => config('entry_tag-relational_image_zoom'),
            'imageCenter'      => config('entry_tag-relational_image_center'),
            'offset'           => config('entry_tag-relational_offset'),
            'unit'             => config('entry_tag-relational_unit'),
            'newtime'          => config('entry_tag-relational_newtime'),
            'pagerDelta'       => config('entry_tag-relational_pager_delta'),
            'pagerCurAttr'     => config('entry_tag-relational_pager_cur_attr'),
        );
    }

    function get()
    {
        $config = $this->initVars();
        
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());

        if ( empty($this->eid) ) return false;

        $this->initVars();
        $DB     = DB::singleton(dsn());

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

        ACMS_Filter::entrySession($SQL);
        ACMS_Filter::entrySpan($SQL, $this->start, $this->end);

        if ( !empty($this->keyword) ) {
            ACMS_Filter::entryKeyword($SQL, $this->keyword);
        }
        if ( !empty($this->Field) ) {
            ACMS_Filter::entryField($SQL, $this->Field);
        }
        if ( 'on' == $config['indexing'] ) {
            $SQL->addWhereOpr('entry_indexing', 'on');
        }
        if ( 'on' <> $config['noimage'] ) {
            $SQL->addWhereOpr('entry_primary_image', null, '<>');
        }

        /**
         * Detect Tag Relationality
         */
        $SQL->addLeftJoin('tag', 'tag_entry_id', 'entry_id');

        $Tag    = SQL::newSelect('tag');
        $Tag->addSelect('tag_name');
        $Tag->addWhereOpr('tag_entry_id', $this->eid);

        $SQL->addSelect('tag_name', 'tag_similar_grade', null, 'count');
        $SQL->addSelect('entry_title');
        $SQL->addSelect('entry_blog_id');
        $SQL->addSelect('entry_category_id');
        $SQL->addSelect('entry_primary_image');
        $SQL->addSelect('entry_sort');
        $SQL->addSelect('entry_category_sort');
        $SQL->addSelect('entry_user_sort');
        $SQL->addSelect('entry_id');
        $SQL->addSelect('entry_status');
        $SQL->addSelect('entry_link');
        $SQL->addSelect('entry_start_datetime');
        $SQL->addSelect('entry_end_datetime');
        $SQL->addSelect('entry_code');
        $SQL->addSelect('entry_datetime');
        $SQL->addSelect('entry_updated_datetime');
        $SQL->addSelect('entry_posted_datetime');
        $SQL->addSelect('entry_user_id');

        $SQL->addWhereIn('tag_name', $Tag);
        $SQL->addWhereOpr('entry_id', $this->eid, '!=');

        /**
         * Detect Finding Amount
         */
        $Amount = new SQL_Select($SQL);
        $Amount->setSelect('*', 'entry_amount', null, 'count');
        if ( !$itemsAmount = intval($DB->query($Amount->get(dsn()), 'one')) ) {
            if ( 'on' == $config['notfound'] ) {
                $Tpl->add('notFound');
                if ( 'on' == $config['notfoundStatus404'] ) {
                    httpStatusCode('404 Not Found');
                }
                return $Tpl->get();
            } else {
                return false;
            }
        }
        
        ACMS_Filter::entryOrder($SQL, $config['order'], $this->uid, $this->cid);
        $from   = ($this->page - 1) * $config['limit'];
        $limit  = ((($from + $config['limit']) > $itemsAmount) ? ($itemsAmount - $from) : $config['limit']);
        if ( 1 > $limit ) return '';
        
        $remainingEntries = $itemsAmount - $from;
        $gluePoint = ($remainingEntries > $limit) ? $limit : $remainingEntries;

        $SQL->setLimit($limit, ($from));
        $SQL->addGroup('entry_id');

        if ( $config['order'] == 'relationality' ) {
            $SQL->setOrder('tag_similar_grade', 'DESC');
            $SQL->addOrder('entry_datetime', 'DESC');
        }

        $q          = $SQL->get(dsn());
        $extraVars  = array('grade' => 'tag_similar_grade');
        
        $i = 0;
        $DB->query($q, 'fetch');
        while ( $row = $DB->fetch($q) ) {
            $i++;
            $this->buildSummary($Tpl, $row, $i, $gluePoint, $config, $extraVars);
        }

        return $Tpl->get();
    }
}
