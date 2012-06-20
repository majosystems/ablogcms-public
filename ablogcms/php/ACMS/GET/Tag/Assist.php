<?php
/**
 * ACMS_GET_Tag_Assist
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Tag_Assist extends ACMS_GET
{
    function get()
    {
        $SQL    = SQL::newSelect('tag');
        $SQL->addSelect('tag_name');
        $SQL->addSelect('tag_name', 'tag_amount', null, 'count');
        $SQL->addWhereOpr('tag_blog_id', $this->bid);
        $SQL->addGroup('tag_name');
        $SQL->setLimit(config('tag_assist_limit'));
        ACMS_Filter::tagOrder($SQL, config('tag_assist_order'));
        if ( 1 < ($tagThreshold = idval(config('tag_assist_threshold'))) ) {
            $SQL->addHaving('tag_amount >= '.$tagThreshod);
        }
        $q  = $SQL->get(dsn());

        $DB = DB::singleton(dsn());
        if ( !$DB->query($q, 'fetch') ) return '';

        if ( !$row = $DB->fetch() ) return '';
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        do {
            $Tpl->add('tag:loop', array(
                'name'      => $row['tag_name'],
                'amount'    => $row['tag_amount'],
            ));
        } while ( $row = $DB->fetch() );

        return $Tpl->get();
    }
}
