<?php
/**
 * ACMS_GET_Ios_Login
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Ios_Login extends ACMS_GET
{
    function get()
    {
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
            $insert_group   = configArray('column_def_insert_group');
            $insert_size    = configArray('column_def_insert_size');
            $insert_field_1 = configArray('column_def_insert_field_1');
            $insert_field_2 = configArray('column_def_insert_field_2');
            $insert_field_3 = configArray('column_def_insert_field_3');
            $insert_field_4 = configArray('column_def_insert_field_4');
            
            $max_size = count($insert_type);
            $insert_size    = array_pad($insert_size, $max_size, "");
            $insert_group   = array_pad($insert_group, $max_size, "");
            $insert_field_1 = array_pad($insert_field_1, $max_size, "");
            $insert_field_2 = array_pad($insert_field_2, $max_size, "");
            $insert_field_3 = array_pad($insert_field_3, $max_size, "");
            $insert_field_4 = array_pad($insert_field_4, $max_size, "");
            
            for($count = 0; $count < count($insert_type); $count++){
                $unit = array();
                $unit['unit_type']      = strval($insert_type[$count]);
                $unit['unit_align']     = strval($insert_align[$count]);
                $unit['unit_size']      = strval($insert_size[$count]);
                $unit['unit_group']     = strval($insert_group[$count]);
                $unit['unit_value1']    = strval($insert_field_1[$count]);
                $unit['unit_value2']    = strval($insert_field_2[$count]);
                $unit['unit_value3']    = strval($insert_field_3[$count]);
                $unit['unit_value4']    = strval($insert_field_4[$count]);
                
                $insert_order[] = $unit;
            }
            
            $unit_group = array();
            
            $unit_group_label   = configArray('unit_group_label');
            $unit_group_class   = configArray('unit_group_class');
            
            for($count = 0; $count < count($unit_group_label); $count++){
                $group = array();
                $group['label'] = strval($unit_group_label[$count]);
                $group['class'] = strval($unit_group_class[$count]);
                
                $unit_group[] = $group;
            }
            
            $lsize = strval(config('image_size_large'));
            
            if ( preg_match('/^(w|width|h|height)(\d+)/', $lsize, $matches) ) {
                $largeSize      = intval($matches[2]);
                $aspect         = $matches[1];
                if ( substr($matches[1], 0, 1) == 'w' ) {
                    $aspect     = 'w';
                } else if ( substr($matches[1], 0, 1) == 'h' ) {
                    $aspect     = 'h';
                }
            } else {
                $largeSize      = intval($lsize);
                $aspect         = '';
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
                "MAX_IMAGE_ASPECT"      => $aspect,
                "CATEGORY_LIST"         => $category,
                "TAG_LIST"              => $tag,
                "TEXT_DEFAULT_TEXT"     => strval(config('column_def_add_text_field_1')),
                "TEXT_DEFAULT_PLACE"    => strval(config('column_def_add_text_align')),
                "TEXT_DEFAULT_TYPE"     => strval(config('column_def_add_text_field_2')),
                "TEXT_DEFAULT_GROUP"    => strval(config('column_def_add_text_group')),
                "IMG_DEFAULT_SIZE"      => strval(config('column_def_add_image_size')),
                "IMG_DEFAULT_PLACE"     => strval(config('column_def_add_image_align')),
                "IMG_DEFAULT_CAPTION"   => strval(config('column_def_add_image_field_1')),
                "IMG_DEFAULT_LINK"      => strval(config('column_def_add_image_field_3')),
                "IMG_DEFAULT_ALT"       => strval(config('column_def_add_image_field_4')),
                "IMG_DEFAULT_GROUP"     => strval(config('column_def_add_image_group')),
                "MAP_DEFAULT_SIZE"      => strval(config('column_def_add_map_size')),
                "MAP_DEFAULT_PLACE"     => strval(config('column_def_add_map_align')),
                "MAP_DEFAULT_LAT"       => strval(config('column_def_add_map_field_2')),
                "MAP_DEFAULT_LNG"       => strval(config('column_def_add_map_field_3')),
                "MAP_DEFAULT_ZOOM"      => strval(config('column_def_add_map_field_4')),
                "MAP_DEFAULT_HTML"      => strval(config('column_def_add_map_field_1')),
                "MAP_DEFAULT_GROUP"     => strval(config('column_def_add_map_group')),
                "UNIT_INSERT_ORDER"     => $insert_order,
                "UNIT_GROUP_LIST"       => $unit_group
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
