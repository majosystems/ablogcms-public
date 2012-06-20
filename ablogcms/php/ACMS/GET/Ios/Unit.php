<?php
/**
 * ACMS_GET_Ios_Unit
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Ios_Unit extends ACMS_GET_Admin_Entry
{
    function get ()
    {
        if ( !sessionWithContribution() ) return false;
 
        $Entry  = array();
        $Column = array();
        
        $eid    = $this->Get->get('eid');
        
        if ( $eid ) {
            $Field      = loadEntryField($eid);
            $Entry      = ACMS_RAM::entry($eid);
            $Entry['field'] = $Field;
            $DB = DB::singleton(dsn());
            
            //-----
            // tag
            $tag    = '';
            $SQL    = SQL::newSelect('tag');
            $SQL->setSelect('tag_name');
            $SQL->addWhereOpr('tag_entry_id', $eid);
            $q  = $SQL->get(dsn());
            if ( $DB->query($q, 'fetch') and ($row = $DB->fetch($q)) ) { 
                do {
                    $tag    .= !empty($tag) ? ', ' : '';
                    $tag    .= $row['tag_name'];
                } while ( $row = $DB->fetch($q) ); 
                $Entry['tag']  = $tag;
            }
 
            //--------
            // column
            if ( $Column = loadColumn($eid) ) {
                $cnt    = count($Column);
                for ( $i=0; $i<$cnt; $i++ ) {
                    $Column[$i]['id']   = uniqid('');
                    $Column[$i]['sort'] = $i + 1;
                    
                    switch ( $Column[$i]['type'] ) {
                        case 'text' :
                            break;
                        case 'image' :
                            $Column[$i]['old_path']  = $Column[$i]['path'];
                            $parse      = preg_split('@/@', $Column[$i]['path']);
                            $filename   = end($parse);
                            array_pop($parse);
                            $dir        = BASE_URL.ARCHIVES_DIR.implode('/', $parse);
                            $ldir       = ARCHIVES_DIR.implode('/', $parse);
                            if ( file_exists($ldir.'/large-'.$filename) ) {
                                $Column[$i]['largePath']    = $dir.'/large-'.$filename;
                            } else {
                                $Column[$i]['largePath']    = $dir.'/'.$filename;
                            }
                            if ( file_exists($ldir.'/tiny-'.$filename) ) {
                                $Column[$i]['tinyPath']    = $dir.'/tiny-'.$filename;
                            } else {
                                $Column[$i]['tinyPath']    = $dir.'/'.$filename;
                            }
                            $Column[$i]['path'] = $dir.'/'.$filename;
                            break;
                        case 'file' :
                            break;
                        case 'map' :
                            break;
                        case 'youtube' :
                            break;
                        default :
                            break;
                    }
                }
            }
            
            foreach ( $Column as $key  => $value ) {
                if ( is_null($value) ) {
                    $Column[$key]  = "-1";
                }
            }
            $Entry['column']    = $Column;
            foreach ( $Entry as $key  => $value ) {
                if ( is_null($value) ) {
                    $Entry[$key]  = "-1";
                }
            }
            
        } else {
            //error
        }
        return json_encode($Entry);
    }
}
