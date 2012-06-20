<?php
/**
 * ACMS_GET_Entry_Body
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Entry_Body extends ACMS_GET_Entry
{
    var $_axis = array(
        'bid'   => 'descendant-or-self',
        'cid'   => 'descendant-or-self',
    );

    var $_scope = array(
        'uid'       => 'global',
        'cid'       => 'global',
        'eid'       => 'global',
        'keyword'   => 'global',
        'tag'       => 'global',
        'field'     => 'global',
        'date'      => 'global',
        'start'     => 'global',
        'end'       => 'global',
        'page'      => 'global',
        'order'     => 'global',
    );
    
    function initConfig()
    {
        // entry
        $this->order                = $this->order ? $this->order : config('entry_body_order');
        $this->limit                = config('entry_body_limit');
        $this->offset               = config('entry_body_offset');
        $this->image_viewer         = config('entry_body_image_viewer');
        $this->indexing             = config('entry_body_indexing');
        $this->newtime              = config('entry_body_newtime');
        $this->serial_navi_ignore_category_on = config('entry_body_serial_navi_ignore_category');
        $this->tag_on               = config('entry_body_tag_on');
        $this->summary_on           = config('entry_body_summary_on');
        $this->detail_date_on       = config('entry_body_detail_date_on');
        $this->comment_on           = config('entry_body_comment_on');
        $this->trackback_on         = config('entry_body_trackback_on');
        $this->serial_navi_on       = config('entry_body_serial_navi_on');
        $this->category_order       = config('entry_body_category_order');
        $this->notfoundStatus404    = config('entry_body_notfound_status_404');
        // micropage
        $this->micropager_on        = config('entry_body_micropage');
        $this->micropager_delta     = config('entry_body_micropager_delta');
        $this->micropager_cur_attr  = config('entry_body_micropager_cur_attr');
        // pager
        $this->pager_on             = config('entry_body_pager_on');
        $this->pager_delta          = config('entry_body_pager_delta');
        $this->pager_cur_attr       = config('entry_body_pager_cur_attr');
        // field
        $this->entry_field_on       = config('entry_body_entry_field_on');
        $this->user_field_on        = config('entry_body_user_field_on');
        $this->category_field_on    = config('entry_body_category_field_on');
        $this->blog_field_on        = config('entry_body_blog_field_on');
        // base field
        $this->user_info_on         = config('entry_body_user_info_on');
        $this->category_info_on     = config('entry_body_category_info_on');
        $this->blog_info_on         = config('entry_body_blog_info_on');
    }

    function buildCategory(& $Tpl, $cid, $bid)
    {
        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('category');
        $SQL->addSelect('category_id');
        $SQL->addSelect('category_name');
        $SQL->addSelect('category_code');
        $SQL->addWhereOpr('category_indexing', 'on');
        ACMS_Filter::categoryTree($SQL, $cid, 'ancestor-or-self');
        $SQL->addOrder('category_left', 'DESC');
        $q  = $SQL->get(dsn());
        $DB->query($q, 'fetch');

        $_all    = array();
        
        while ( $row = $DB->fetch($q) ) {
            array_push($_all, $row);
        }

        switch ( $this->category_order ) {
            case 'child_order' :
                break;
            case 'parent_order' :
                $_all = array_reverse($_all);
                break;
            case 'current_order' :
                $_all = array(array_shift($_all));
                break;
            default :
                break;
        }
        
        while ( $_row = array_shift($_all) ) {
            if ( !empty($_all[0]) ) {
                $Tpl->add(array('glue', 'category:loop'));
            }
            
            $Tpl->add('category:loop', array(
                'name'  => $_row['category_name'],
                'code'  => $_row['category_code'],
                'url'   => acmsLink(array(
                    'bid'   => $bid,
                    'cid'   => $_row['category_id'],
                )),
            ));
            array_push($_all, $DB->fetch($q));
        }

        return true;
    }

    function buildCommentAmount($eid)
    {
        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('comment');
        $SQL->addSelect('*', 'comment_amount', null, 'COUNT');
        $SQL->addWhereOpr('comment_entry_id', intval($eid));

        if ( 1
            and !sessionWithCompilation()
            and SUID <> ACMS_RAM::entryUser($eid)
        ) {
            $SQL->addWhereOpr('comment_status', 'close', '<>');
        }

        return array(
            'commentAmount' => intval($DB->query($SQL->get(dsn()), 'one')),
            'commentUrl'    => acmsLink(array('eid' => intval($eid),)),
        );
    }

    function buildTrackbackAmount($eid)
    {
        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('trackback');
        $SQL->setSelect('*', 'trackback_amount', null, 'COUNT');
        $SQL->addWhereOpr('trackback_entry_id', intval($eid));

        if ( 1
            and !sessionWithCompilation()
            and SUID <> ACMS_RAM::entryUser($eid)
        ) {
            $SQL->addWhereOpr('trackback_status', 'close', '<>');
        }

        return array(
            'trackbackAmount'   => intval($DB->query($SQL->get(dsn()), 'one')),
            'trackbackUrl'      => acmsLink(array('eid' => intval($eid))),
        );
    }

    function buildTag(& $Tpl, $eid)
    {
        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('tag');
        $SQL->addSelect('tag_name');
        $SQL->addSelect('tag_blog_id');
        $SQL->addWhereOpr('tag_entry_id', $eid);
        $SQL->addOrder('tag_sort');

        $q  = $SQL->get(dsn());

        do {
            if ( !$DB->query($q, 'fetch') ) break;
            if ( !$row = $DB->fetch($q) ) break;
            $stack  = array();
            array_push($stack, $row);
            array_push($stack, $DB->fetch($q));
            while ( $row = array_shift($stack) ) {
                if ( !empty($stack[0]) ) $Tpl->add(array('glue', 'tag:loop'));
                $Tpl->add('tag:loop', array(
                    'name'  => $row['tag_name'],
                    'url'   => acmsLink(array(
                        'bid'   => $row['tag_blog_id'],
                        'tag'   => $row['tag_name'],
                    )),
                ));
                array_push($stack,$DB->fetch($q));
            }
        } while ( false );

        return true;
    }

    function buildAdminEntryEdit($bid, $uid, $cid, $eid, & $Tpl, $block=null)
    {
        $block  = empty($block) ? array() : (is_array($block) ? $block : array($block));

        if ( ADMIN ) {
            if ( 'entry-add' == substr(ADMIN, 0, 9) ) {
                $Tpl->add(array_merge(array('adminEntryEdit'), $block));
            }
        } else if ( sessionWithCompilation() or (sessionWithContribution() and $uid == SUID) ) {
            $val    = array(
                'bid'   => $bid,
                'cid'   => $cid,
                'eid'   => $eid,
                'status.title'   => ACMS_RAM::entryTitle($eid),
                'status.category'=> ACMS_RAM::categoryName($cid),
                'status.url'     => acmsLink(array('bid'=>$bid, 'cid'=>$cid, 'eid'=>$eid, 'sid'=>null, '_protocol'=>'http')),
            );

            if ( IS_LICENSED ) {
                $Tpl->add(array_merge(array('edit'), $block), $val);
                if ( BID == $bid ) {
                    $types  = configArray('column_add_type');
                    if ( is_array($types) ) {
                        $cnt    = count($types);
                        $labels = configArray('column_add_type_label');
                        for ( $i=0; $i<$cnt; $i++ ) {
                            if ( !$type = $types[$i] ) continue;
                            if ( !$label = $labels[$i] ) continue;
                            $Tpl->add(array_merge(array('add:loop'), $block), $val    + array(
                                'label' => $label,
                                'type'  => $type,
                            ));
                        }
                    }
                    $statusBlock    = ( 'open' == ACMS_RAM::entryStatus($eid) ) ? 'close' : 'open';
                    $Tpl->add(array_merge(array($statusBlock), $block), $val);
                }
            }
            $Tpl->add(array_merge(array('delete'), $block), $val);

            if ( 1
                and 'on' == config('entry_edit_inplace_enable')
                and 'on' == config('entry_edit_inplace')
                and VIEW == 'entry'
            ) {
                $Tpl->add(array_merge(array('adminDetailEdit'), $block), $val);
            }
        }
        return true;
    }
    
    function buildBodyField(&$Tpl, &$vars, $row, $serial = 0)
    {
        $bid    = $row['entry_blog_id'];
        $uid    = $row['entry_user_id'];
        $cid    = $row['entry_category_id'];
        $eid    = $row['entry_id'];
        $inheritUrl = acmsLink(array(
                'eid'       => $eid,
        ));
        
        if ( $serial != 0 ) {
            if ( $serial % 2 == 0 ) {
                $oddOrEven  = 'even';
            } else {
                $oddOrEven  = 'odd';
            }
            
            $vars['iNum']       = $serial;
            $vars['sNum']       = (($this->page - 1) * $this->limit) + $serial;
            $vars['oddOrEven']  = $oddOrEven;
        }

        //-----------
        // build tag
        if ( $this->tag_on === 'on' ) {
            $this->buildTag($Tpl, $eid);
        }

        //---------------------
        // build category loop
        if ( !empty($cid) and $this->category_info_on === 'on' ) {
            $this->buildCategory($Tpl, $cid, $bid);
        }
        
        //------------------------
        // build comment/trackbak
        if ( 'on' == config('comment') and $this->comment_on === 'on' ) {
            $vars += $this->buildCommentAmount($eid);
        }
        if ( 'on' == config('trackback') and $this->trackback_on === 'on' ) {
            $vars += $this->buildTrackbackAmount($eid);
        }

        //----------------
        // build summary
        if ( $this->summary_on === 'on' ) {
            $vars['summary'] = loadFulltext($eid);
        }

        //-------
        // admin
        $this->buildAdminEntryEdit($bid, $uid, $cid, $eid, $Tpl, 'entry:loop');

        //-------------------
        // build entry field
        if ( $this->entry_field_on === 'on' ) {
            $vars += $this->buildField(loadEntryField($eid), $Tpl, 'entry:loop', 'entry');
        }
        
        //-------------------
        // build user field
        if ( $this->user_info_on === 'on' ) {
            $Field = ($this->user_field_on === 'on') ? loadUserField($uid) : new Field();
            
            $Field->setField('fieldUserName', ACMS_RAM::userName($uid));
            $Field->setField('fieldUserCode', ACMS_RAM::userCode($uid));
            $Field->setField('fieldUserStatus', ACMS_RAM::userStatus($uid));
            $Field->setField('fieldUserMail', ACMS_RAM::userMail($uid));
            $Field->setField('fieldUserMailMobile', ACMS_RAM::userMailMobile($uid));
            $Field->setField('fieldUserUrl', ACMS_RAM::userUrl($uid));
            $Tpl->add('userField', $this->buildField($Field, $Tpl));
        }
        
        //----------------------
        // build category field
        if ( $this->category_info_on === 'on' ) {
            $Field = ($this->category_field_on === 'on') ? loadCategoryField($cid) : new Field();
            $Field->setField('fieldCategoryName', ACMS_RAM::categoryName($cid));
            $Field->setField('fieldCategoryCode', ACMS_RAM::categoryCode($cid));
            $Field->setField('fieldCategoryUrl', acmsLink(array(
                'bid'   => $bid,
                'cid'   => $cid,
            )));
            $Field->setField('fieldCategoryId', $cid);
            $Tpl->add('categoryField', $this->buildField($Field, $Tpl));
        }
        
        //------------------
        // build blog field
        if ( $this->blog_info_on === 'on' ) {
            $Field = ($this->blog_field_on === 'on') ? loadBlogField($bid) : new Field();
            
            $Field->setField('fieldBlogName', ACMS_RAM::blogName($bid));
            $Field->setField('fieldBlogCode', ACMS_RAM::blogCode($bid));
            $Field->setField('fieldBlogUrl', acmsLink(array('bid' => $bid)));
            $Tpl->add('blogField', $this->buildField($Field, $Tpl));
        }

        $vars   += array(
            'status'    => $row['entry_status'],
            'titleUrl'  => !empty($link) ? $link : $inheritUrl,
            'title'     => addPrefixEntryTitle($row['entry_title']
                , $row['entry_status']
                , $row['entry_start_datetime']
                , $row['entry_end_datetime']
            ),
            'inheritUrl'        => $inheritUrl,
            'posterName'        => ACMS_RAM::userName($uid),
            'entry:loop.bid'    => $bid,
            'entry:loop.uid'    => $uid,
            'entry:loop.cid'    => $cid,
            'entry:loop.eid'    => $eid,
            'entry:loop.bcd'    => ACMS_RAM::blogCode($bid),
            'entry:loop.ucd'    => ACMS_RAM::userCode($uid),
            'entry:loop.ccd'    => ACMS_RAM::categoryCode($cid),
            'entry:loop.ecd'    => ACMS_RAM::entryCode($eid),
        );
        
        //------------
        // build date
        $vars   += $this->buildDate($row['entry_datetime'], $Tpl, 'entry:loop');
        if($this->detail_date_on === 'on'){
            $vars   += $this->buildDate($row['entry_updated_datetime'], $Tpl, 'entry:loop', 'udate#');
            $vars   += $this->buildDate($row['entry_posted_datetime'], $Tpl, 'entry:loop', 'pdate#');
        }

        //-----------
        // build new
        if ( strtotime($row['entry_datetime']) + $this->newtime > REQUEST_TIME ) {
            $Tpl->add(array('new:touch', 'entry:loop'));    // 後方互換
            $Tpl->add(array('new', 'entry:loop'));
        }
        
        $vars['permalink']  = acmsLink(array(
            'bid'   => $bid,
            'eid'   => $eid,
        ));
        
        return true;
    }

    function get()
    {
        $this->initConfig();
        $DB     = DB::singleton(dsn());
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $entryOrder = $this->order;

        if ( 'entry-edit' == ADMIN ) {
            $vars   = array();
            $step   = $this->Post->get('step', 'apply');
            $action = !EID ? 'insert' : 'update';

            switch ( $step ) {
                case 'confirm':
                case 'result':
                    $Entry  =& $this->Post->getChild('entry');
                    $Field  =& $this->Post->getChild('field');
                    $Column = array();
                    $_Column    = acmsUnserialize($this->Post->get('column'));

                    foreach ( $_Column as $data ) {
                        $Column[intval($data['sort'])]  = $data;
                    }
                    ksort($Column);
                    $this->buildColumn($Column, $Tpl, $this->eid);
                    $vars   = $this->buildField($Field, $Tpl, 'entry:loop', 'entry');

                    $Tpl->add(array('header#'.$step, 'adminEntryTitle'), array(
                        'adminTitle' => $Entry->get('title'),
                    ));

                    break;
                case 'reapply':
                default:
                    $Tpl->add(array('header#'.$action, 'adminEntryTitle'));
                    $Tpl->add(array('description#'.$action, 'adminEntryTitle'));
            }
            $Tpl->add('adminEntryEdit');
            $Tpl->add('entry:loop', $vars);

        } else if ( $this->eid ) {

            $SQL    = SQL::newSelect('entry');
            $SQL->addWhereOpr('entry_id', $this->eid);

            $q      = $SQL->get(dsn());
            if ( !$row = $DB->query($q, 'row') ) {
                return $this->resultsNotFound($Tpl);
            }
            if ( !IS_LICENSED ) {
                $row['entry_title'] = '[test]'.$row['entry_title'];
            }

            $eid    = $row['entry_id'];

            $vars   = array();

            //---------
            // column
            $break      = null;
            $micropage  = null;
            $micropageLabel = null;
            if ( $Column = loadColumn($eid) ) {
                if ( $this->micropager_on === 'on' ) {
                    $break      = 1;
                    $micropage  = $this->page;
                    $_Column    = $Column;
                    $Column     = array();
                    foreach ( $_Column as $col ) {
                        if ( 'break' == $col['type'] ) {
                            if ( $micropage == $break ) { $micropageLabel = $col['label']; }
                            $break++;
                        }
                        if ( $micropage == $break ) {
                            $Column[]   = $col;
                        }
                    }
                }
                $this->buildColumn($Column, $Tpl, $eid);
            }

            $this->buildBodyField($Tpl, $vars, $row);

            $Tpl->add('entry:loop', $vars);

            //-----------------
            // build serialNavi
            if($this->serial_navi_on === 'on'){
                $SQLCommon  = SQL::newSelect('entry');
                $SQLCommon->addSelect('entry_id');
                $SQLCommon->addSelect('entry_title');
                $SQLCommon->addSelect('entry_status');
                $SQLCommon->addSelect('entry_start_datetime');
                $SQLCommon->addSelect('entry_end_datetime');
                $SQLCommon->setLimit(1);
                $SQLCommon->addWhereOpr('entry_blog_id', $this->bid);
                if ($this->serial_navi_ignore_category_on !== 'on') {
                    $SQLCommon->addWhereOpr('entry_category_id', $this->cid);
                }
    
                ACMS_Filter::entrySession($SQL);
                ACMS_Filter::entrySpan($SQLCommon, $this->start, $this->end);
                if ( !empty($this->tags) ) {
                    ACMS_Filter::entryTag($SQLCommon, $this->tags);
                }
                if ( !empty($this->keyword) ) {
                    ACMS_Filter::entryKeyword($SQLCommon, $this->keyword);
                }
                if ( !empty($this->Field) ) {
                    ACMS_Filter::entryField($SQLCommon, $this->Field);
                }
    
                $SQLCommon->addWhereOpr('entry_indexing', 'on');
                $aryOrder   = explode('-', $entryOrder);
                $fd         = isset($aryOrder[0]) ? $aryOrder[0] : null;
                $seq        = isset($aryOrder[1]) ? $aryOrder[1] : null;
                $isDesc     = ('DESC' == strtoupper($seq)) ? true : false;
    
                if ( 'random' <> $fd ) {
                    switch ( $fd ) {
                        case 'datetime':
                            $field  = 'entry_datetime';
                            $value  = ACMS_RAM::entryDatetime($this->eid);
                            break;
                        case 'code':
                            $field  = 'entry_code';
                            $value  = ACMS_RAM::entryCode($this->eid);
                            break;
                        case 'sort':
                            if ( $this->uid ) {
                                $field  = 'entry_user_sort';
                                $value  = ACMS_RAM::entryUserSort($this->eid);
                            } else if ( $this->cid ) {
                                $field  = 'entry_category_sort';
                                $value  = ACMS_RAM::entryCategorySort($this->eid);
                            } else {
                                $field  = 'entry_sort';
                                $value  = ACMS_RAM::entrySort($this->eid);
                            }
                            break;
                        case 'id':
                        default:
                            $field  = 'entry_id';
                            $value  = $this->eid;
                    }
    
                    //----------------
                    // build prevLink
                    $SQL    = new SQL_Select($SQLCommon);
                    $W1  = SQL::newWhere();
                    $W1->addWhereOpr($field, $value, '=');
                    $W1->addWhereOpr('entry_id', $this->eid, '<');
                    $W2 = SQL::newWhere();
                    $W2->addWhere($W1);
                    $W2->addWhereOpr($field, $value, '<', 'OR');
                    $SQL->addWhere($W2);
                    ACMS_Filter::entryOrder($SQL, $fd.'-desc', $this->uid, $this->cid);
                    ACMS_Filter::entrySession($SQL);
                    $q  = $SQL->get(dsn());
    
                    if ( $row = $DB->query($q, 'row') ) {
                        $Tpl->add('prevLink', array(
                           'name'   => addPrefixEntryTitle($row['entry_title']
                                , $row['entry_status']
                                , $row['entry_start_datetime']
                                , $row['entry_end_datetime']
                            ),
                           'url'    => acmsLink(array(
                                '_inherit'  => true,
                                'eid'       => $row['entry_id'],
                            )),
                        ));
                    } else {
                        $Tpl->add('prevNotFound');
                    }

                    //----------------
                    // build nextLink
                    $SQL    = new SQL_Select($SQLCommon);
                    $W1  = SQL::newWhere();
                    $W1->addWhereOpr($field, $value, '=');
                    $W1->addWhereOpr('entry_id', $this->eid, '>');
                    $W2 = SQL::newWhere();
                    $W2->addWhere($W1);
                    $W2->addWhereOpr($field, $value, '>', 'OR');
                    $SQL->addWhere($W2);
                    ACMS_Filter::entryOrder($SQL, $fd.'-asc', $this->uid, $this->cid);
                    ACMS_Filter::entrySession($SQL);
                    $q  = $SQL->get(dsn());
    
                    if ( $row = $DB->query($q, 'row') ) {
                        $Tpl->add('nextLink', array(
                           'name'   => addPrefixEntryTitle($row['entry_title']
                                , $row['entry_status']
                                , $row['entry_start_datetime']
                                , $row['entry_end_datetime']
                            ),
                           'url'    => acmsLink(array(
                                '_inherit'  => true,
                                'eid'       => $row['entry_id'],
                            )),
                        ));
                    } else {
                        $Tpl->add('nextNotFound');
                    }
                }
                
                if($this->micropager_on){
                    //-----------
                    // micropage
                    if ( !empty($micropageLabel) ) {
                        $Tpl->add('micropageLink', array(
                            'label' => $micropageLabel,
                            'url'   => acmsLink(array(
                                '_inherit'  => true,
                                'eid'       => $this->eid,
                                'page'      => $micropage + 1,
                            )),
                        ));
                    }
    
                    //------------
                    // micropager
                    if ( !empty($micropage) ) {
                        $vars       = array();
                        $delta      = $this->micropager_delta;
                        $curAttr    = $this->micropager_cur_attr;
                        $vars       += $this->buildPager($micropage, 1, $break, $delta, $curAttr, $Tpl, 'micropager');
                        $Tpl->add('micropager', $vars);
                    }
                }
                
                $Tpl->add(null, array('upperUrl' => acmsLink(array(
                    'eid'   => false,
                ))));
            }
        } else {
            $limit  = idval($this->limit);
            $from   = ($this->page - 1) * $limit;
            $SQL    = SQL::newSelect('entry');
            $SQL->addLeftJoin('blog', 'blog_id', 'entry_blog_id');
            ACMS_Filter::blogTree($SQL, $this->bid, $this->blogAxis());
            ACMS_Filter::blogStatus($SQL);
            $SQL->addLeftJoin('category', 'category_id', 'entry_category_id');
            ACMS_Filter::categoryTree($SQL, $this->cid, $this->categoryAxis());
            ACMS_Filter::categoryStatus($SQL);
            ACMS_Filter::entrySession($SQL, $this->eid);
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
            if ( 'on' === $this->indexing ) {
                $SQL->addWhereOpr('entry_indexing', 'on');
            }
            if ( $uid = intval($this->uid) ) {
                $SQL->addWhereOpr('entry_user_id', $uid);
            }

            $Amount = new SQL_Select($SQL);

            $Amount->setSelect('*', 'entry_amount', null, 'COUNT');
            if ( !$itemsAmount = intval($DB->query($Amount->get(dsn()), 'one')) ) {
                return $this->resultsNotFound($Tpl);
            }

            $offset = intval($this->offset);
            $_limit = ($from + $limit > $itemsAmount) ? ($itemsAmount - $from) : $limit;
            if ( 1 > $_limit ) {
                return $this->resultsNotFound($Tpl);
            }

            $SQL->setLimit($_limit, $from + $offset);

            ACMS_Filter::entryOrder($SQL, $entryOrder, $this->uid, $this->cid);
            $q  = $SQL->get(dsn());
            $DB->query($q, 'fetch');

            $serial = 0;
            while ( $row = $DB->fetch($q) ) {
                $serial++;
                if ( !IS_LICENSED ) $row['entry_title'] = '[test]'.$row['entry_title'];
                
                $eid    = $row['entry_id'];

                $continueName   = $row['entry_title'];
                $summaryRange   = strval(config('entry_body_fix_summary_range'));
                if ( !strlen($summaryRange) ) $summaryRange = strval($row['entry_summary_range']);
                $summaryRange   = !!strlen($summaryRange) ? intval($summaryRange) : null;
                $inheritUrl = acmsLink(array(
                    'eid'       => $eid,
                ));

                $vars   = array();

                //---------
                // column
                if ( $Column = loadColumn($eid, $summaryRange) ) {
                    $this->buildColumn($Column, $Tpl, $eid);
                    if ( !empty($summaryRange) ) {
                        $SQL    = SQL::newSelect('column');
                        $SQL->addSelect('*', 'column_amount', null, 'COUNT');
                        $SQL->addWhereOpr('column_entry_id', $eid);
                        $amount = $DB->query($SQL->get(dsn()), 'one');

                        if ( $summaryRange < $amount ) {
                            $vars['continueUrl']    = $inheritUrl;
                            $vars['continueName']   = $continueName;
                        }
                    }
                }

                $this->buildBodyField($Tpl, $vars, $row, $serial);
                
                $Tpl->add('entry:loop', $vars);
            }

            if ( 'random' <> strtolower($entryOrder) and ($this->pager_on === 'on')) {
                $vars       = array();
                $delta      = intval($this->pager_delta);
                $curAttr    = $this->pager_cur_attr;
                $vars       += $this->buildPager($this->page, $limit, $itemsAmount, $delta, $curAttr, $Tpl);
            }

            $Tpl->add(null, $vars);
        }

        return $Tpl->get();
    }

    function resultsNotFound($Tpl)
    {
        $Tpl->add('notFound');
        if ( 'on' == $this->notfoundStatus404 ) {
            httpStatusCode('404 Not Found');
        }
        return $Tpl->get();
    }
}
