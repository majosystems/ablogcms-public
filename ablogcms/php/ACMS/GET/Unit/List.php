<?php
/**
 * ACMS_GET_Unit_List
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Unit_List extends ACMS_GET
{
    var $_axis = array(
        'bid'   => 'descendant-or-self',
        'cid'   => 'descendant-or-self',
    );

    var $_scope = array(
        'cid'       => 'global',
        'eid'       => 'global',
        'start'     => 'global',
        'end'       => 'global',
    );

    function get()
    {
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('column');
        $SQL->addLeftJoin('entry', 'entry_id', 'column_entry_id');
        $SQL->addLeftJoin('category', 'category_id', 'entry_category_id');
        $SQL->addLeftJoin('blog', 'blog_id', 'entry_blog_id');

        ACMS_Filter::blogTree($SQL, $this->bid, $this->blogAxis());
        ACMS_Filter::blogStatus($SQL);
        ACMS_Filter::categoryTree($SQL, $this->cid, $this->categoryAxis());
        ACMS_Filter::categoryStatus($SQL);

        if ( !empty($this->eid) ) {
            $SQL->addWhereOpr('column_entry_id', $this->eid);
        }
        ACMS_Filter::entrySession($SQL);
        ACMS_Filter::entrySpan($SQL, $this->start, $this->end);

        $SQL->addWhereIn('column_type', configArray('column_list_type'));
        $SQL->setLimit(intval(config('column_list_limit')));

        $order  = config('column_list_order');
        if ( 'random' == $order ) {
            $SQL->setOrder('RAND()');
        } else if ( 'datetime-asc' == $order ) {
            $SQL->addOrder('entry_datetime', 'ASC');
        } else {
            $SQL->addOrder('entry_datetime', 'DESC');
        }

        $q  = $SQL->get(dsn());

        if ( $DB->query($q, 'fetch') and $row = $DB->fetch($q) ) { do {
            $clid   = intval($row['column_id']);
            $eid    = intval($row['entry_id']);
            $cid    = intval($row['category_id']);
            $bid    = intval($row['blog_id']);

            if ( 'image' == $row['column_type'] ) {
                $normal = $row['column_field_2'];
                $tiny   = preg_replace('@(^|/)(?=[^/]+$)@', '$1tiny-', $normal);
                $large  = preg_replace('@(^|/)(?=[^/]+$)@', '$1large-', $normal);

                $row['tiny']    = $tiny;
                $row['normal']  = $normal;
                if ( is_file(ARCHIVES_DIR.$large) ) {
                    $row['large']   = $large;
                }
            }

            $row['entry_url']   = acmsLink(array(
                'bid'   => $bid,
                'eid'   => $eid,
            ));
            if ( !empty($cid) ) {
                $row['category_url']    = acmsLink(array(
                    'bid'   => $bid,
                    'cid'   => $cid,
                ));
            } else {
                unset($row['category_name']);
            }
            $row['blog_url']    = acmsLink(array(
                'bid'   => $bid,
            ));

            foreach ( $row as $key => $val ) {
                if ( empty($val) ) { unset($row[$key]); }
            }

            $Tpl->add('column:loop', $row);
        } while ( $row = $DB->fetch($q) ); }

        return $Tpl->get();
    }
}
