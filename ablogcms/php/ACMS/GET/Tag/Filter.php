<?php
/**
 * ACMS_GET_Tag_Filter
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Tag_Filter extends ACMS_GET
{
    var $_scope = array(
        'tag'   => 'global',
    );

    function get()
    {
        if ( !$cnt = count($this->tags) ) { return false; }
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());

        if ( $cnt > config('tag_filter_selected_limit') ) {
            $cnt    = config('tag_filter_selected_limit');
        }

        $stack  = array();
        for ( $i=0; $i<$cnt;$i++ ) {
            $stack[] = $this->tags[$i];
        }

        $tags   = array();
        for ( $i=0; $i<$cnt; ) {
            $tag    = $this->tags[$i];
            $tags[] = $tag;
            if ( $cnt <> ++$i ) { $Tpl->add('glue'); }

            // 現在選択中のタグの中から該当の$tagを除いたものを表示
            $rejects = $stack;
            unset($rejects[array_search($tag, $tags)]);

            $vars = array(
                'name'  => $tag,
                'url'   => acmsLink(array(
                    'bid'   => $this->bid,
                    'tag'   => $tags,
                )),
                'omitUrl'=> acmsLink(array(
                    'bid'   => $this->bid,
                    'tag'   => array_merge($rejects), // indexを振り直し（unsetで空いた分）
                )),
            );
            $Tpl->add('selected:loop', $vars);
        }

        $SQL    = SQL::newSelect('tag', 'tag0');
        $SQL->addSelect('tag_name', null, 'tag0', 'DISTINCT');
        $SQL->addWhereOpr('tag_blog_id', $this->bid, '=', 'AND', 'tag0');
        foreach ( $this->tags as $i => $tag ) {
            $SQL->addLeftJoin('tag', 'tag_entry_id', 'tag_entry_id', 'tag'.($i+1), 'tag'.$i);
            $SQL->addWhereOpr('tag_name', $tag, '=', 'AND', 'tag'.($i+1));
        }
        foreach ( $this->tags as $tag ) {
            $SQL->addWhereOpr('tag_name', $tag, '<>', 'AND', 'tag0'/*.$i*/);
        }
        $SQL->addLeftJoin('entry', 'entry_id', 'tag_entry_id', null, 'tag0');
        ACMS_Filter::entrySession($SQL);
        if ( !empty($this->Field) ) { ACMS_Filter::entryField($SQL, $this->Field); }
        ACMS_Filter::tagOrder($SQL, config('tag_filter_order'));
        $SQL->setLimit(config('tag_filter_limit'));
        $q  = $SQL->get(dsn());

        $DB = DB::singleton(dsn());


        $all = $DB->query($q, 'all');

        if ( !$cnt = count($all) ) { return $Tpl->get(); }

        $i      = 0;
        while ( $row = array_shift($all) ) {
            $tag    = $row['tag_name'];
            $tags   = $this->tags;
            $tags[] = $tag;
            if ( $cnt <> ++$i ) { $Tpl->add(array('glue', 'choice:loop')); }
            $Tpl->add('choice:loop', array(
                'name'  => $row['tag_name'],
                'url'   => acmsLink(array(
                    'bid'   => $this->bid,
                    'tag'   => $tags,
                )),
            ));
        }

        return $Tpl->get();
    }
}

