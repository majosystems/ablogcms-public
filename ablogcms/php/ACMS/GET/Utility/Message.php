<?php
/**
 * ACMS_GET_Utility_Message
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Utility_Message extends ACMS_GET
{
    function get()
    {
        $Tpl = new Template($this->tpl, new ACMS_Corrector());
        
        $import_mess = $this->Post->get('import_message');
        $export_mess = $this->Post->get('export_message');
        $archive_mess = $this->Post->get('archive_import_message');
        
        $vars = array(
            'importMessage' => $import_mess,
            'exportMessage' => $export_mess,
            'archiveMessage'=> $archive_mess,
        );
        
        $Tpl->add(null, $vars);
        
        return $Tpl->get();
    }
}
