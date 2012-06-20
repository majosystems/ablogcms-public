<?php
/**
 * ACMS_GET_Plugin_FlTopImage_Xml
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Plugin_FlTopImage_Xml extends ACMS_GET
{
    var $_axis = array(
        'bid'   => 'descendant-or-self',
        'cid'   => 'descendant-or-self',
    );

    var $_scope = array(
        'cid'       => 'global',
        'eid'       => 'global',
        'page'      => 'global',
    );

    function get()
    {
        $license    = (IS_LICENSED and defined('LICENSE_PLUGIN_FLTOPIMAGE') and !!LICENSE_PLUGIN_FLTOPIMAGE);

        if ( !!($check = $this->Post->get('ACMS_POST_FlTopImage_Check')) ) {
            header('Content-Type: text/html');
            if ( !$license ) $check .= ' false';
            die($check);
        }

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $DB     = DB::singleton(dsn());

        $SQL    = SQL::newSelect('column');
        $SQL->addLeftJoin('entry', 'entry_id', 'column_entry_id');
        $SQL->addLeftJoin('category', 'category_id', 'entry_category_id');
        $SQL->addLeftJoin('blog', 'blog_id', 'entry_blog_id');

        ACMS_Filter::blogTree($SQL, $this->bid, $this->Get->get('baxis', $this->blogAxis()));
        ACMS_Filter::blogStatus($SQL);
        ACMS_Filter::categoryTree($SQL, $this->cid, $this->Get->get('caxis', $this->categoryAxis()));
        ACMS_Filter::categoryStatus($SQL);

        if ( !empty($this->eid) ) {
            $SQL->addWhereOpr('column_entry_id', $this->eid);
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

        $SQL->addWhereOpr('column_type', 'image');

        $order  = ORDER ? ORDER : 'datetime-asc';
        if ( !$license ) $order = 'datetime-asc';
        if ( 'random' == $order ) {
            $SQL->setOrder('RAND()');
        } else if ( 'datetime-asc' == $order ) {
            $SQL->addOrder('entry_datetime', 'ASC');
        } else {
            $SQL->addOrder('entry_datetime', 'DESC');
        }

        $limit  = LIMIT ? LIMIT : 1;
        if ( !$license ) $limit = 1;
        $from   = ($this->page - 1) * $limit;
        $SQL->setLimit(intval($limit), $from);

        $imageBaseUrl   = BASE_URL.ARCHIVES_DIR;

        $q  = $SQL->get(dsn());
        if ( $DB->query($q, 'fetch') and $row = $DB->fetch($q) ) { do {
            $clid   = intval($row['column_id']);
            $eid    = intval($row['entry_id']);
            $cid    = intval($row['category_id']);
            $bid    = intval($row['blog_id']);

            $image  = $row['column_field_2'];
            $large  = preg_replace('@(^|/)(?=[^/]+$)@', '$1large-', $image);
            if ( is_file(ARCHIVES_DIR.$large) ) $image = $large;
            $row['image_url']   = $imageBaseUrl.$image;

            $row['entry_url']   = acmsLink(array(
                'bid'   => $bid,
                'eid'   => $eid,
            ));

            $Tpl->add('image:loop', $row);
        } while ( $row = $DB->fetch($q) ); }

        return $Tpl->get();

    }
}
