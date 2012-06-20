<?php
/**
 * ACMS_GET_Ios_Summary
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Ios_Summary extends ACMS_GET_Entry
{
    function get()
    {
        $queryString  = $this->Get->get('qs');
        
        $limit  = 30;
        $from   = 0;
        $order  = 'datetime-desc';
        $cid    = null;
        $word   = null;

        if ( !empty($queryString) ) {
            $qs     = preg_split('/aa/', $queryString);
            foreach ( $qs as $v ) {
                list($fd, $ve)  = preg_split('/=/', $v);
                switch ( $fd ) {
                    case 'limit'    :
                        $limit  = intval($ve);
                        break;
                    case 'from'     :
                        $from   = intval($ve);
                        break;
                    case 'order'    :
                        $order  = $ve;
                        break;
                    case 'cid'      :
                        $cid    = $ve;
                        break;
                    case 'keyword'  :
                        $word   = $ve;
                        break;
                    default     :
                        break;
                }
            }
        }

        $DB     = DB::singleton(dsn());

        $SQL    = SQL::newSelect('entry');
        $SQL->addLeftJoin('category', 'category_id', 'entry_category_id');
        $SQL->addLeftJoin('blog', 'blog_id', 'entry_blog_id');

        ACMS_Filter::blogTree($SQL, $this->bid, 'self');

        if ( $uid = intval($this->uid) ) {
            $SQL->addWhereOpr('entry_user_id', $uid);
        }
        if ( !empty($cid) and null !== $cid ) {
            $SQL->addWhereOpr('entry_category_id', $cid);
        }
        if ( !empty($this->tags) ) {
            ACMS_Filter::entryTag($SQL, $this->tags);
        }
        if ( !empty($word) ) {
            ACMS_Filter::entryKeyword($SQL, $word);
        }
        if ( !empty($this->Field) ) {
            ACMS_Filter::entryField($SQL, $this->Field);
        }
        
        $Amount = new SQL_Select($SQL);
        $Amount->setSelect('*', 'entry_amount', null, 'count');
        if ( !$itemsAmount = intval($DB->query($Amount->get(dsn()), 'one')) ) {
            //error
        }

        ACMS_Filter::entryOrder($SQL, $order, $this->uid, $this->cid);
        
        // $from   = ($this->page - 1) * $this->limit + $this->offset;
        $limit  = ((($from + $limit) > $itemsAmount) ? ($itemsAmount - $from) : $limit);
        // if ( 1 > $limit ) return '';

        $SQL->setLimit($limit, ($from));
        $q  = $SQL->get(dsn());
        
        $Summary    = array();
        
        $DB->query($q, 'fetch');
        while ( $row = $DB->fetch($q) ) {
            $row['entry_permalink']    = acmsLink(array(
                'bid'   => $row['entry_blog_id'],
                'cid'   => $row['entry_category_id'],
                'eid'   => $row['entry_id'],
            ));
            $pid    = $row['entry_primary_image'];
            if ( !empty($pid) ) {
                $SQL    = SQL::newSelect('column');
                $SQL->setSelect('column_field_2');
                $SQL->addWhereOpr('column_id', $pid);
                $filename   = $DB->query($SQL->get(dsn()), 'one');
                $path       = BASE_URL.ARCHIVES_DIR.$filename;
            } else {
                $path   = null;
            }
            
            $row['entry_thumbnail'] = $path;
            
            switch ( $row['entry_status'] ) {
                case 'open' :
                    break;
                case 'draft' :
                    $row['entry_title']     = '【下書き】'.$row['entry_title'];
                    break;
                case 'close' :
                    $row['entry_title']     = '【非公開】'.$row['entry_title'];
                    break;
            }
            
            foreach ( $row as $key  => $value ) {
                if ( is_null($value) ) {
                    $row[$key]  = "-1";
                }
            }
            
            $Summary[] = $row;
        }

        return json_encode(array('entry_summary' => $Summary));
    }
}