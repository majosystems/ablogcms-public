<?php
/**
 * ACMS_Services
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
abstract class ACMS_Services
{
    abstract static public function establish($bid);

    static public function loadOAuthToken($bid, $type = 'all', $service)
    {
        $DB     = DB::singleton(dsn());

        switch ( $type ) {
            case 'request'  :
                $target  = array(
                    "{$service}_oauth_request_token",
                    "{$service}_oauth_request_token_secret",
                );
                break;
            case 'access'   :
                $target  = array(
                    "{$service}_oauth_access_token",
                    "{$service}_oauth_access_token_secret",
                );
                break;
            default         :
                $target  = array(
                    "{$service}_oauth_request_token",
                    "{$service}_oauth_request_token_secret",
                    "{$service}_oauth_access_token",
                    "{$service}_oauth_access_token_secret",
                );
                break;
        }

        $SQL    = SQL::newSelect('config');
        $SQL->addSelect('config_key');
        $SQL->addSelect('config_value');
        $SQL->addWhereIn('config_key', $target);
        $SQL->addWhereOpr('config_blog_id', $bid);
        $all    = $DB->query($SQL->get(dsn()), 'all');

        if ( empty($all) ) return false;

        $tokens = array();
        foreach ( $all as $row ) {
            $tokens[str_replace("@$bid", '', $row['config_key'])]    = $row['config_value'];
        }

        return $tokens;
    }

    static public function insertOAuthToken($bid, $token, $secret, $type, $service)
    {
        $DB     = DB::singleton(dsn());

        $SQL    = SQL::newInsert('config');
        $SQL->addInsert('config_key', "{$service}_oauth_{$type}_token");
        $SQL->addInsert('config_value', $token);
        $SQL->addInsert('config_blog_id', $bid);
        if ( !$DB->query($SQL->get(dsn()), 'exec') ) return false;

        $SQL    = SQL::newInsert('config');
        $SQL->addInsert('config_key', "{$service}_oauth_{$type}_token_secret");
        $SQL->addInsert('config_value', $secret);
        $SQL->addInsert('config_blog_id', $bid);
        if ( !$DB->query($SQL->get(dsn()), 'exec') ) return false;

        return true;
    }

    static public function deleteOAuthToken($bid, $type = 'all', $service)
    {
        $DB     = DB::singleton(dsn());

        switch ( $type ) {
            case 'request'  :
                $target  = array(
                    "{$service}_oauth_request_token",
                    "{$service}_oauth_request_token_secret",
                );
                break;
            case 'access'   :
                $target  = array(
                    "{$service}_oauth_access_token",
                    "{$service}_oauth_access_token_secret",
                );
                break;
            default         :
                $target  = array(
                    "{$service}_oauth_request_token",
                    "{$service}_oauth_request_token_secret",
                    "{$service}_oauth_access_token",
                    "{$service}_oauth_access_token_secret",
                );
                break;
        }

        $SQL    = SQL::newDelete('config');
        $SQL->addWhereIn('config_key', $target);
        $SQL->addWhereOpr('config_blog_id', $bid);
        return $DB->query($SQL->get(dsn()), 'exec');
    }
}
