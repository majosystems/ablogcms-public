<?php
/**
 * ACMS_GET_Ios_Config
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Ios_Config extends ACMS_GET
{
    function get(){

        function getGlobalVarsFromHash($array) {
            return json_decode(setGlobalVars(json_encode($array)), true);
        };
        
        function convertStr(&$item, $key){
            $item = strval($item);
        }
        
        function config_json(){
            //GET Config
            $column_image_size_criterion = configArray('column_image_size_criterion');
            $culumn_image_size = configArray('column_image_size');
            
            for($count = 0; $count < count($column_image_size_criterion); $count++){
                if(isset($culumn_image_size_criterion[$count])){
                    $culumn_image_size_criterion[$count] .= $culumn_image_size[$count];
                }
            }
            
            $tag_select_label = configArray('column_text_tag_label');
            $tag_select_tag   = configArray('column_text_tag');
            $tag_select_list_array = array();

            for($count = 0; $count < count($tag_select_label); $count++){
                $tag_obj = array();
                $tag_obj['tag'] = strval($tag_select_tag[$count]);
                $tag_obj['tag_label'] = strval($tag_select_label[$count]);
                
                $tag_select_list_array[] = $tag_obj;
            }
            
            $map_size       = configArray('column_map_size');
            $map_size_label = configArray('column_map_size_label');
            $map_size_list_array = array();
            
            for($count = 0; $count < count($map_size); $count++){
                $map_obj = array();
                $map_obj['map_size'] = strval($map_size[$count]);
                $map_obj['map_size_label'] = strval($map_size_label[$count]);
                $map_size_list_array[] = $map_obj;
            }
            
            $global = getGlobalVarsFromHash(array(
                'bid'               => '%{BID}',
                'uid'               => '%{UID}',
                'blog_name'         => '%{BLOG_NAME}',
                'suid'              => '%{SUID}',
                'user_name'         => '%{SESSION_USER_NAME}',
                'version'           => '%{VERSION}',
                'session_user_auth' => '%{SESSION_USER_AUTH}'
            ));

            //DB Connect
            $DB = DB::singleton(dsn());
            
            $SQL = SQL::newSelect('category');
            $SQL->addSelect('category_id');
            $SQL->addSelect('category_name');
            $SQL->addSelect('category_code');
            $SQL->addWhereOpr('category_blog_id', $global['bid'], '=');
            $query = $SQL->get(dsn());
            $category = $DB->query($query, 'all');
            
            array_walk_recursive($category, 'convertStr');
            
            $SQL = SQL::newSelect('tag');
            $SQL->addSelect('tag_name', null, null, 'DISTINCT');
            $SQL->addWhereOpr('tag_blog_id', $global['bid'], '=');
            $query = $SQL->get(dsn());
            $tag = $DB->query($query, 'all');
            
            array_walk_recursive($tag, 'convertStr');
            
            $image_size_list_array = array();
            $image_size_label = configArray('column_image_size_label');

            for($count = 0; $count < count($image_size_label); $count++){
                $image_obj = array();
                $image_obj['image_size'] = strval($culumn_image_size[$count]);
                $image_obj['image_label'] = $image_size_label[$count];
                $image_size_list_array[] = $image_obj;
            }
            
            $insert_order = array();
            
            $insert_type    = configArray('column_def_insert_type');
            $insert_align   = configArray('column_def_insert_align');
            $insert_size    = configArray('column_def_insert_size');
            $insert_field_1 = configArray('column_def_insert_field_1');
            $insert_field_2 = configArray('column_def_insert_field_2');
            
            $max_size = count($insert_type);
            $insert_size    = array_pad($insert_size, $max_size, "");
            $insert_field_1 = array_pad($insert_field_1, $max_size, "");
            $insert_field_2 = array_pad($insert_field_2, $max_size, ""); 
            
            for($count = 0; $count < count($insert_type); $count++){
                $unit = array();
                $unit['unit_type']            = strval($insert_type[$count]);
                $unit['unit_place']           = strval($insert_align[$count]);
                $unit['unit_size']            = strval($insert_size[$count]);
                $unit['unit_caption_or_text'] = strval($insert_field_1[$count]);
                $unit['unit_tag_select']      = strval($insert_field_2[$count]);
                
                $insert_order[] = $unit;
            }
            
            $lsize = strval(config('image_size_large'));
            
            if ( preg_match('/^(w|width|h|height)(\d+)/', $lsize, $matches) ) {
                $largeSize      = intval($matches[2]);
            } else {
                $largeSize      = intval($lsize);
            }
            
            //JSON
            $account = array(
                "SUID"                  => strval($global['suid']),
                "SESSION_USER_AUTH"     => strval($global['session_user_auth']),
                "BLOG_NAME"             => strval($global['blog_name']),
                "BLOG_ID"               => strval($global['bid']),
                "VERSION"               => strval($global['version']),
                "USER_ID"               => strval($global['uid']),
                "USER_NAME"             => strval($global['user_name']),
                "IMAGE_SIZE_LIST"       => $image_size_list_array,
                "TAG_SELECT_LIST"       => $tag_select_list_array,
                "MAP_SIZE_LIST"         => $map_size_list_array,
                "MAX_IMAGE_SIZE"        => $largeSize,
                "CATEGORY_LIST"         => $category,
                "TAG_LIST"              => $tag,
                "TEXT_DEFAULT_TEXT"     => strval(config('column_def_add_text_field_1')),
                "TEXT_DEFAULT_PLACE"    => strval(config('column_def_add_text_align')),
                "TEXT_DEFAULT_TYPE"     => strval(config('column_def_add_text_field_2')),
                "IMG_DEFAULT_SIZE"      => strval(config('column_def_add_image_size')),
                "IMG_DEFAULT_PLACE"     => strval(config('column_def_add_image_align')),
                "IMG_DEFAULT_CAPTION"   => strval(config('column_def_add_image_field_1')),
                "IMG_DEFAULT_LINK"      => strval(config('column_def_add_image_field_3')),
                "IMG_DEFAULT_SUBSTITUTE"=> strval(config('column_def_add_image_field_4')),
                "MAP_DEFAULT_SIZE"      => strval(config('column_def_add_map_size')),
                "MAP_DEFAULT_PLACE"     => strval(config('column_def_add_map_align')),
                "MAP_DEFAULT_HTML"      => strval(config('column_def_add_map_field_1')),
                "UNIT_INSERT_ORDER"     => $insert_order
            );
            
            return json_encode($account);
        }
        
        if(sessionWithCompilation()){
            return config_json();
        }else{
            return 'error';
        }
    }
}
