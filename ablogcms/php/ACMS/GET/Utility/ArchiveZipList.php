<?php
/**
 * ACMS_GET_Utility_ArchiveZipList
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
define('ARCHIVES_BACKUP_DIR', SCRIPT_DIR.ARCHIVES_DIR.'backup/');
define('DB_FULL_BACKUP_DIR', SCRIPT_DIR.'private/backup/');

class ACMS_GET_Utility_ArchiveZipList extends ACMS_GET
{
    function get()
    {
        $Tpl = new Template($this->tpl, new ACMS_Corrector());
        
        $zip_list = array();
        $sql_list = array();
        
        if(is_dir(ARCHIVES_BACKUP_DIR)){
            if ($dir = opendir(ARCHIVES_BACKUP_DIR)) {
                while (($file = readdir($dir)) !== false) {
                    if ($file != "." && $file != ".." && substr($file,0,1) != '.') {
                        $zip_list[] = array(
                            'zipfile' => $file
                        );
                    }
                }
                closedir($dir);
            }
        }
        
        if(is_dir(DB_FULL_BACKUP_DIR)){
            if ($dir = opendir(DB_FULL_BACKUP_DIR)) {
                while (($file = readdir($dir)) !== false) {
                    if ($file != "." && $file != ".." && substr($file,0,1) != '.') {
                        $sql_list[] = array(
                            'sqlfile' => $file
                        );
                    }
                }
                closedir($dir);
            }
        }
        
        foreach($zip_list as $loop){
            $Tpl->add('zip:loop', $loop);
        }
        
        foreach($sql_list as $loop){
            $Tpl->add('sql:loop', $loop);
        }

        return $Tpl->get();
    }
}
