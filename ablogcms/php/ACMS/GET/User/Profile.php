<?php
/**
 * ACMS_GET_User_Profile
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_User_Profile extends ACMS_GET
{
    function get()
    {
        $DB     = DB::singleton(dsn());
        $SQL    = SQL::newSelect('user');
        $SQL->addWhereOpr('user_pass', '', '<>');

        $SQL->addSelect('user_id');
        $SQL->addSelect('user_code');
        $SQL->addSelect('user_status');
        $SQL->addSelect('user_name');
        $SQL->addSelect('user_mail');
        $SQL->addSelect('user_mail_mobile');
        $SQL->addSelect('user_url');
        $SQL->addSelect('user_auth');
        $SQL->addWhereOpr('user_blog_id', $this->bid);
        $SQL->addLeftJoin('entry', 'entry_user_id', 'user_id');
        $SQL->setGroup('user_id');

        // indexing
        if ( 'on' === config('user_profile_indexing') ) {
            $SQL->addWhereOpr('user_indexing', 'on');
        }

        $aryAuth    = array();
        foreach ( array(
            'administrator', 'editor', 'contributor', 'subscriber'
        ) as $auth ) {
            if ( 'on' == config('user_profile_'.$auth) ) $aryAuth[] = $auth;
        }
        $SQL->addWhereIn('user_auth', $aryAuth);

        if ( !!($uid = intval($this->uid)) ) {
            $SQL->addWhereOpr('user_id', $uid);
            $SQL->setLimit(1);
        } else {
            ACMS_Filter::userOrder($SQL, config('user_profile_order'));
            $SQL->setLimit(intval(config('user_profile_limit')));
        }

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());

        if ( !($all = $DB->query($SQL->get(dsn()), 'all')) ) {
            $Tpl->add('notFound');
            return $Tpl->get();
        }

        foreach ( $all as $row ) {
            $vars   = $this->buildField(loadUserField(intval($row['user_id'])), $Tpl);
            foreach ( $row as $key => $val ) {
                $vars[substr($key, strlen('user_'))]    = $val;
            }
            $Tpl->add('user:loop', $vars);
        }

        return $Tpl->get();
    }
}
