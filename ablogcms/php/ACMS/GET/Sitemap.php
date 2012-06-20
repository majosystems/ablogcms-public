<?php
/**
 * ACMS_GET_Sitemap
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Sitemap extends ACMS_GET
{
    var $_axis = array(
        'bid'   => 'descendant-or-self',
        'cid'   => 'descendant-or-self',
    );

    function get()
    {
        $Tpl    = new Template($this->tpl);
        $DB     = DB::singleton(dsn());

        /**
         * Blog
         */
        $SQL    = SQL::newSelect('blog');
        $SQL->setSelect('blog_id');
        ACMS_Filter::blogStatus($SQL);
        ACMS_Filter::blogTree($SQL, $this->bid, $this->blogAxis());

        // indexing
        if ( 'on' == config('sitemap_blog_indexing') ) {
            $SQL->addWhereOpr('blog_indexing', 'on');
        }

        // order
        $order = config('sitemap_blog_order', 'id-asc');
        ACMS_Filter::blogOrder($SQL, $order);

        $bQ = $SQL->get(dsn());

        if ( $DB->query($bQ, 'fetch') ) { while ( $bid = intval(ite($DB->fetch($bQ), 'blog_id')) ) {
            $Tpl->add('url:loop', array(
                'loc'   => acmsLink(array(
                    'bid' => $bid,
                 ), false),
            ));

            /**
             * Category
             */
            $SQL    = SQL::newSelect('category');
            $SQL->setSelect('category_id');
            ACMS_Filter::categoryStatus($SQL);
            $SQL->addWhereOpr('category_blog_id', $bid);

            // indexing
            if ( 'on' == config('sitemap_category_indexing') ) {
                $SQL->addWhereOpr('category_indexing', 'on');
            }

            // order
            $order = config('sitemap_category_order', 'id-asc');
            ACMS_Filter::categoryOrder($SQL, $order);

            $cQ = $SQL->get(dsn());
            $DB->query($cQ, 'fetch');

            $cid    = null;
            do {

                if ( !empty($cid) ) {
                    $Tpl->add('url:loop', array(
                        'loc'   => acmsLink(array(
                            'bid'   => $bid,
                            'cid'   => $cid,
                        ), false),
                    ));
                }

                /**
                 * Entry
                 */
                $SQL    = SQL::newSelect('entry');
                $SQL->addSelect('entry_id');
                $SQL->addSelect('entry_updated_datetime');
                ACMS_Filter::entryStatus($SQL);
                $SQL->addWhereOpr('entry_category_id', $cid);
                $SQL->addWhereOpr('entry_blog_id', $bid);

                // indexing
                if ( 'on' == config('sitemap_entry_indexing') ) {
                    $SQL->addWhereOpr('entry_indexing', 'on');
                }

                // order
                $order = config('sitemap_entry_order', 'id-asc');
                ACMS_Filter::entryOrder($SQL, $order);

                // limit
                if ( !!($limit = config('sitemap_entry_limit')) && $limit != 0 ) {
                    $SQL->setLimit($limit);
                }

                $eQ = $SQL->get(dsn());
                if ( !$DB->query($eQ, 'fetch') ) break;

                while ( $row = $DB->fetch($eQ) ) {
                    $eid        = intval($row['entry_id']);
                    $t          = strtotime($row['entry_updated_datetime']);
                    $lastmod    = date('Y-m-d', $t).'T'.date('H:i:s', $t).preg_replace('@(?=\d{2,2}$)@', ':', date('O', $t));
                    $Tpl->add('url:loop', array(
                        'loc'   => acmsLink(array(
                            'bid'   => $bid,
                            'cid'   => $cid,
                            'eid'   => $eid,
                        ), false),
                        'lastmod'   => $lastmod,
                    ));
                }
            } while ( $cid = intval(ite($DB->fetch($cQ), 'category_id')) );
        } }

        return $Tpl->get();
    }
}
