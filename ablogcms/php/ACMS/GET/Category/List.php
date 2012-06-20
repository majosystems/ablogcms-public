<?php
/**
 * ACMS_GET_Category_List
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Category_List extends ACMS_GET
{
    var $_axis  = array(
        'cid'   => 'descendant-or-self',
    );

    function get()
    {
        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('category');
        $SQL->addSelect('category_id');
        $SQL->addSelect('category_code');
        $SQL->addSelect('category_name');
        $SQL->addSelect('category_parent');
        $SQL->addSelect('category_left');
        $SQL->addSelect('category_indexing');
        $SQL->addLeftJoin('entry', 'entry_category_id', 'category_id');
        $SQL->addWhereOpr('category_blog_id', $this->bid);
        ACMS_Filter::categoryTree($SQL, $this->cid, $this->categoryAxis());
        ACMS_Filter::categoryStatus($SQL);

        $Where  = SQL::newWhere();
        ACMS_Filter::entrySession($Where);
        ACMS_Filter::entrySpan($Where, $this->start, $this->end);
        if ( !empty($this->Field) ) { ACMS_Filter::entryField($SQL, $this->Field); }
        $Case   = SQL::newCase();
        $Case->add($Where, 1);
        $Case->setElse('NULL');
        $SQL->addSelect($Case, 'category_entry_amount', null, 'count');
        $SQL->setGroup('category_id');
        if ( !($all = $DB->query($SQL->get(dsn()), 'all')) ) return '';

        //-------------
        // restructure
        foreach ( $all as $row ) {
            $cid    = intval($row['category_id']);
            foreach ( $row as $key => $val ) {
                $All[$key][$cid]    = $val;
            }
            $All['all_amount'][$cid]    = intval($All['category_entry_amount'][$cid]);
        }
        $All['all_amount'][0]   = 0;

        //--------------------------
        // indexing ( swap parent )
        while ( !!($cid = intval(array_search('off', $All['category_indexing']))) ) {
            while ( !!($_cid = intval(array_search($cid, $All['category_parent']))) ) {
                $All['category_parent'][$_cid]  = $All['category_parent'][$cid];
            }
            foreach ( $All as $key => $val ) {
                unset($val[$cid]);
                $All[$key]  = $val;
            }
        }

        //------
        // sort
        foreach ( $All as $key => $val ) {
            ksort($val);
            asort($val);
            $All[$key]  = $val;
        }

        //------------
        // all amount
        arsort($All['category_left']);
        foreach ( $All['category_left'] as $cid => $kipple ) {
            $pid    = intval($All['category_parent'][$cid]);
            if ( !isset($All['all_amount'][$pid]) ) $pid = 0;
            $All['all_amount'][$pid] += intval($All['all_amount'][$cid]);
        }
        unset($All['all_amount'][0]);
        asort($All['all_amount']);
        asort($All['category_left']);

        //-----------------------------
        // amount zero ( swap parent )
        if ( 'on' <> config('category_list_amount_zero') ) {
            while ( !!($cid = array_search(0, $All['all_amount'])) ) {
                while ( !!($_cid = intval(array_search($cid, $All['category_parent']))) ) {
                    $All['category_parent'][$_cid]  = $All['category_parent'][$cid];
                }
                foreach ( $All as $key => $val ) {
                    unset($val[$cid]);
                    $All[$key]  = $val;
                }
            }
        }

        //-------
        // order
        $s      = explode('-', config('category_list_order'));
        $order  = isset($s[0]) ? $s[0] : 'id';
        $isDesc = isset($s[1]) ? ('desc' == $s[1]) : false;
        switch ( $order ) {
            case 'amount':
                $key    = 'all_amount';
                break;
            case 'sort':
                $key    = 'category_left';
                break;
            case 'code':
                $key    = 'category_code';
                break;
            default:
                $key    = 'category_id';
        }
        if ( $isDesc ) arsort($All[$key]);
        foreach ( $All[$key] as $cid => $kipple ) {
            $Map[$cid]  = intval($All['category_parent'][$cid]);
        }

        if ( empty($Map) ) return '';

        //-------
        // tpl
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $Tpl->add('ul#front');
        $Tpl->add('category:loop');

        //-------
        // stack
        $aryTemp    = array();
        foreach ( array_unique($All['category_parent']) as $pid ) {
            $aryTemp[intval(isset($All['category_left'][$pid]) ? $All['category_left'][$pid] : 0)]  = $pid;
        }
        ksort($aryTemp);
        $pid    = array_shift($aryTemp);
        $stack  = array($pid);

        //-------
        // level
        $level  = intval(config('category_list_level'));
        if ( empty($level) ) $level = 1000;
        $level--;

        $i = 0;
        $j = $DB->affected_rows();
        while ( count($stack) ) {
            $pid    = array_pop($stack);
            while ( !!($cid = array_search($pid, $Map)) ) {
                unset($Map[$cid]);
                $Tpl->add('li#front');

                $depth  = count($stack) + 1;
                $vars   = array(
                    'bid'       => $this->bid,
                    'cid'       => $cid,
                    'ccd'       => $All['category_code'][$cid],
                    'name'      => $All['category_name'][$cid],
                    'amount'    => $All['all_amount'][$cid],
                    'singleAmount'  => $All['category_entry_amount'][$cid],
                    'level'     => $depth,
                    'url'       => acmsLink(array(
                        'bid'   => $this->bid,
                        'cid'   => $cid,
                    )),
                );
                if ( 'on' <> config('category_list_amount') ) unset($vars['amount']);
                if ( $this->cid == $cid ) {
                    $vars['selected']   = config('attr_selected');
                }

                //-------
                // field
                $vars   += $this->buildField(loadCategoryField($cid), $Tpl);

                $i++;
                //------
                // glue
                if ( $i !== $j ) {
                    $Tpl->add('glue');
                }

                $Tpl->add('category:loop', $vars);

                if ( $level > $depth ) {
                    if ( !!array_search($cid, $Map) ) {
                        $Tpl->add('ul#front');
                        $Tpl->add('category:loop');
                        array_push($stack, $pid, $cid);
                        continue 2;
                    }
                }

                $Tpl->add('li#rear');
                $Tpl->add('category:loop');
            }

            $Tpl->add('ul#rear');
            $Tpl->add('category:loop');
            if ( !empty($stack) ) {
                $Tpl->add('li#rear');
                $Tpl->add('category:loop');
            }
        }

        return $Tpl->get();
    }
}
