<?php
/**
 * ACMS_GET_Js
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Js extends ACMS_GET
{
    function get()
    {
        $jquery = '';
        jsModule('offset', DIR_OFFSET);
        jsModule('jsDir', JS_DIR);
        jsModule('themesDir', '/'.DIR_OFFSET.THEMES_DIR);
        jsModule('bid', BID);
        jsModule('aid', AID);
        jsModule('uid', UID);
        jsModule('cid', CID);
        jsModule('eid', EID);
        jsModule('bcd', ACMS_RAM::blogCode(BID));
        jsModule('jQuery', config('jquery_version'));

        jsModule('umfs', ini_get('upload_max_filesize'));
        jsModule('pms',  ini_get('post_max_size'));
        jsModule('mfu',  ini_get('max_file_uploads'));

        //----------
        // category
        if ( $cid = CID ) {
            $ccds   = array(ACMS_RAM::categoryCode($cid));
            while ( $cid = ACMS_RAM::categoryParent($cid) ) {
                if ( 'on' == ACMS_RAM::categoryIndexing($cid) ) {
                    $ccds[] = ACMS_RAM::categoryCode($cid);
                }
            }
            jsModule('ccd', join('/', array_reverse($ccds)));
        }

        //---------
        // session
        jsModule('sid', SID);
        jsModule('admin', ADMIN);
        jsModule('rid', RID);
        jsModule('ecd', ACMS_RAM::entryCode(EID));
        jsModule('session', SID ? SESSION_NAME : null);
        jsModule('keyword', htmlspecialchars(str_replace('　', ' ', KEYWORD), ENT_QUOTES));
        jsModule('domains', !($domains = jsModule('domains')) ? '' : (is_array($domains) ? join(',', array_unique($domains)) : $domains));
        jsModule('scriptRoot', '/'.DIR_OFFSET.(REWRITE_ENABLE ? '' : SCRIPT_FILENAME.'/'));
        $jsModules  = array();
        foreach ( jsModule() as $key => $value ) {
            if ( empty($value) ) continue;
            $jsModules[]    = $key.(!is_bool($value) ? '='.$value : '');
        }
        $jquery = join('&', $jsModules);

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        if ( !empty($jquery) ) $Tpl->add(null, array(
            'arguments' => '?'.$jquery,
        ));

        return $Tpl->get();
    }
}
