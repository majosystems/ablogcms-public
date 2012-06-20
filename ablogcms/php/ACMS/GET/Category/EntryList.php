<?php
/**
 * ACMS_GET_Category_EntryList
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Category_EntryList extends ACMS_GET
{
    var $_scope  = array(
        'cid'   => 'global',
    );
    
    var $_config = array();
    
    function initVars()
    {
        return array(
            'categoryOrder'                 => config('category_entry_list_category_order'),
            'categoryEntryListLevel'        => config('category_entry_list_level'),
            'categoryIndexing'              => config('category_entry_list_category_indexing'),
            'entryAmountZero'               => config('category_entry_list_entry_amount_zero'),
            'order'                         => config('category_entry_list_entry_order'),
            'limit'                         => config('category_entry_list_entry_limit'),
            'indexing'                      => config('category_entry_list_entry_indexing'),
        );
    }

    function get()
    {
        $this->_config = $this->initVars();
        
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $DB     = DB::singleton(dsn());

        $aryStack   = array(intval($this->cid));
        $aryCount   = array();
        $aryHidden  = array();

        while ( array_key_exists(0, $aryStack) ) {
            $pid    = $aryStack[0];

            $SQL    = SQL::newSelect('category');
            $SQL->addWhereOpr('category_blog_id', $this->bid);
            $SQL->addWhereOpr('category_parent', $pid);
            ACMS_Filter::categoryStatus($SQL);
            ACMS_Filter::categoryOrder($SQL, $this->_config['categoryOrder']);

            $cQ     = $SQL->get(dsn());
            if ( !$DB->isFetched($cQ) and !$DB->query($cQ, 'fetch') ) {
                array_shift($aryStack);
                continue;
            }

            $level  = 0;
            foreach ( $aryStack as $cid ) {
                if ( empty($aryHidden[$cid]) ) $level++;
            }
            $cid    = null;

            if ( intval($this->_config['categoryEntryListLevel']) >= $level ) { while ( !!($cRow = $DB->fetch($cQ)) ) {
                $cid    = intval($cRow['category_id']);

                //--------------------
                // entry build query
                if ( $cRow['category_indexing'] == 'on' ) {
                    if ( $eQ = $this->buildQuery($cid, $Tpl) ) {
                        if ( !!$DB->query($eQ, 'fetch') and !!($eRow = $DB->fetch($eQ)) );
                    }
                }

                if ( 1
                    and !('on' == $this->_config['categoryIndexing'] and 'on' <> $cRow['category_indexing'])
                    and !('on' <> $this->_config['entryAmountZero'] and empty($eRow))
                ) {
                    //-------
                    // entry
                    $i = 0;
                    if ( !empty($eRow) ) { do {
                        $i++;
                        $this->buildUnit($eRow, $Tpl, $cid, $level, $i);
                    } while ( !!($eRow = $DB->fetch($eQ)) ); }

                    //----------
                    // category
                    $vars   = array();
                    $vars   += array(
                        'categoryUrl'   => acmsLink(array(
                            'bid'   => $this->bid,
                            'cid'   => $cid,
                        )),
                        'categoryName'  => $cRow['category_name'],
                        'categoryLevel' => $level,
                        'categoryCode'  => $cRow['category_code'],
                        'categoryId'    => $cid,
                        'categoryPid'   => $pid,
                    );
                    
                    if ( !isset($this->_config['categoryFieldOn']) or $this->_config['categoryFieldOn'] === 'on' ) {
                        $vars   += $this->buildField(loadCategoryField($cid), $Tpl);
                    }

                    if ( empty($aryCount[$pid]) ) {
                        $Tpl->add('categoryUl#front');
                        $aryCount[$pid] = 0;
                    }
                    $aryCount[$pid]++;

                    $Tpl->add('category:loop', $vars);
                    $Tpl->add('categoryEntryList:loop', array('debug' => 'bug'));
                } else {
                    $aryHidden[$cid]    = true;
                }

                if ( intval($this->_config['categoryEntryListLevel']) >= $level ) array_unshift($aryStack, $cid);
                break;
            } }

            if ( is_null($cid) ) {
                array_shift($aryStack);
                if ( empty($aryHidden[$pid]) ) {
                    if ( !empty($aryCount[$pid]) ) {
                        $Tpl->add('categoryUl#rear');
                        $Tpl->add('categoryEntryList:loop');
                    }
                    if ( !empty($aryStack) ) {
                        $Tpl->add('categoryLi#rear');
                        $Tpl->add('categoryEntryList:loop');
                    }
                }
            }
        }

        return $Tpl->get();
    }
    
    function buildQuery($cid, &$Tpl)
    {
        $SQL = SQL::newSelect('entry');
        $SQL->addWhereOpr('entry_category_id', $cid);
        $SQL->addWhereOpr('entry_blog_id', $this->bid);
        ACMS_Filter::entrySpan($SQL, $this->start, $this->end);
        ACMS_Filter::entrySession($SQL);

        if ( !empty($this->tags) ) {
            ACMS_Filter::entryTag($SQL, $this->tags);
        }
        if ( !empty($this->keyword) ) {
            ACMS_Filter::entryKeyword($SQL, $this->keyword);
        }
        if ( !empty($this->Field) ) {
            ACMS_Filter::entryField($SQL, $this->Field);
        }

        if ( 'on' == $this->_config['indexing'] ) {
            $SQL->addWhereOpr('entry_indexing', 'on');
        }
        ACMS_Filter::entryOrder($SQL, $this->_config['order'], $this->uid, $cid);
        $SQL->setLimit($this->_config['limit']);
        $eQ = $SQL->get(dsn());
        
        return $eQ;
    }
    
    function buildUnit($eRow, &$Tpl, $cid, $level, $count = 0)
    {
        $eid  = intval($eRow['entry_id']);
        if ( !empty($eRow['entry_link']) ) {
            $entryUrl   = $eRow['entry_link'];
        } else {
            $entryUrl   = acmsLink(array(
                'bid'   => $this->bid,
                'cid'   => $cid,
                'eid'   => $eid,
            ));
        }
        $vars   = array();
        $vars   += array(
            'entryUrl'      => $entryUrl,
            'entryTitle'    => addPrefixEntryTitle($eRow['entry_title']
                , $eRow['entry_status']
                , $eRow['entry_start_datetime']
                , $eRow['entry_end_datetime']
            ),
            'entryLevel'    => $level,
            'entryCode'     => $eRow['entry_code'],
            'entryId'       => $eid,
        );
        $vars   += $this->buildField(loadEntryField($eid), $Tpl);
        $Tpl->add('entry:loop', $vars);
    }
}
