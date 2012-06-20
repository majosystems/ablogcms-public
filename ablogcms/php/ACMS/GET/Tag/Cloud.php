<?php
/**
 * ACMS_GET_Tag_Cloud
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Tag_Cloud extends ACMS_GET
{
    var $_axis  = array(
        'bid'   => 'self',
    );

    function get()
    {
        $SQL    = SQL::newSelect('tag');
        $SQL->addSelect('tag_name');
        $SQL->addSelect('tag_name', 'tag_amount', null, 'count');
        $SQL->addLeftJoin('blog', 'blog_id', 'tag_blog_id');
        ACMS_Filter::blogTree($SQL, $this->bid, $this->blogAxis());
        ACMS_Filter::blogStatus($SQL);
        $SQL->addLeftJoin('entry', 'entry_id', 'tag_entry_id');
        ACMS_Filter::entrySession($SQL);
        ACMS_Filter::entrySpan($SQL, $this->start, $this->end);
        if ( !empty($this->Field) ) { ACMS_Filter::entryField($SQL, $this->Field); }
        $SQL->addGroup('tag_name');
        if ( 1 < ($tagThreshold = idval(config('tag_cloud_threshold'))) ) {
            $SQL->addHaving('tag_amount >= '.$tagThreshold);
        }
        $SQL->setLimit(config('tag_cloud_limit'));
        ACMS_Filter::tagOrder($SQL, config('tag_cloud_order'));
        $q  = $SQL->get(dsn());

        $DB     = DB::singleton(dsn());
        $all    = $DB->query($q, 'all');
        if ( !$cnt = count($all) ) {
            return false;
        }

        $tags       = array();
        $amounts    = array();
        foreach ( $all as $row ) {
            $tag        = $row['tag_name'];
            $amount     = $row['tag_amount'];
            $tags[$tag] = $amount;
            $amounts[]  = $amount;
        }
        $min    = min($amounts);
        $max    = max($amounts);

        $c  = ($max <> $min) ? (24 / (sqrt($max) - sqrt($min))) : 1;
        $x  = ceil(sqrt($min) * $c);

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $i      = 0;
        foreach ( $tags as $tag => $amount ) {
            if ( !empty($i) ) $Tpl->add('glue'); 
            $Tpl->add('tag:loop', array(
                'level'     => ceil(sqrt($amount) * $c) - $x + 1,
                'url'       => acmsLink(array(
                    'bid'   => $this->bid,
                    'tag'   => $tag,
                )),
                'amount'    => $amount,
                'name'      => $tag,
            ));
            $i++;
        }

        return $Tpl->get();
    }
}
