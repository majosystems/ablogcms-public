<?php
/**
 * ACMS_GET_Entry
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Entry extends ACMS_GET
{
    function buildColumn(& $Column, & $Tpl, $eid, $preAlign = null, $renderGroup = true)
    {
        $columnAmount   = count($Column) - 1;
        $currentGroup   = null;
        $squareImgSize  = config('image_size_square');
        $showInvisible  = (sessionWithContribution()
                          and 'on' == config('entry_edit_inplace_enable')
                          and 'on' == config('entry_edit_inplace'));
        $unitGroupEnable= (config('unit_group') === 'on');

        foreach ( $Column as $i => $data ) {
            $type   = $data['type'];
            $align  = $data['align'];
            $sort   = $data['sort'];
            $group  = $data['group'];
            $utid   = $data['clid'];

            if ( !$showInvisible && 'hidden' === $align ) {
                continue;
            }

            //-------
            // group
            if ( 1
                and $unitGroupEnable
                and $group !== ''
                and $renderGroup === true
            ) {
                $class = $group;

                // close rear
                if ( !!$currentGroup ) {
                    $Tpl->add(array('unitGroup#rear', 'column:loop'));
                }

                // open front
                $grVars = array('class' => $class);
                if ( $currentGroup === $class ) {
                    $count += 1;
                    $grVars['i'] = $count;
                } else {
                    $count = 1;
                    $grVars['i'] = $count;
                }

                $Tpl->add(array('unitGroup#front', 'column:loop'), $grVars);
                $currentGroup = $class;
            }

            //-------
            // clear
            if ( 'break' <> $type ) {
                do {
                    if ( empty($preAlign) ) break;
                    if ( 'left' == $align and 'left' == $preAlign ) break;
                    if ( 'rigth' == $align and 'right' == $preAlign ) break;
                    if ( 'auto' == $align ) {
                        if ( 'left' == $preAlign ) break;
                        if ( 'right' == $preAlign ) break;
                        if ( 'auto' == $preAlign and 'text' == $type ) break;
                    }
                    $Tpl->add(array('clear', 'column:loop'));
                } while ( false );

                if ( 'auto' == $align and 'text' <> $type ) {
                    $data['align']  = !empty($preAlign) ? $preAlign : 'auto';
                }
                $preAlign   = $align;
            }

            //------
            // text
            if ( 'text' == $type ) {
                if ( empty($data['text']) ) continue;
                $vars   = array(
                    'text'  => $data['text'],
                );
                if ( !empty($data['attr']) ) {
                    $vars['attr']   = $data['attr'];
                    $vars['class']  = $data['attr']; // legacy
                }
//                if ( !empty($data['class']) ) {
//                    $vars['class']  = ' class="'.$data['class'].'"';
//                }
                $vars['utid']       = $utid;
                $vars['unit_eid']   = $eid;
                $Tpl->add(array($data['tag'], 'column#text'), $vars);
                $Tpl->add('column#text', array(
                    'align' => $data['align'],
                ));

            //-------
            // image
            } else if ( 'image' == $type ) {
                if ( empty($data['path']) ) continue;
                $path   = ARCHIVES_DIR.$data['path'];
                $xy     = getimagesize($path);

                $vars   = array();
                $vars['path']   = $path;
                $vars['x']      = $xy[0];
                $vars['y']      = $xy[1];
                $vars['alt']    = $data['alt'];
                if ( !empty($data['caption']) ) $vars['caption'] = $data['caption'];

                $vars['align']  = $data['align'];
                if ( !empty($data['attr']) ) $vars['attr'] = $data['attr'];

                if ( !empty($data['link']) ) {
                    $Tpl->add(array('link#front', 'column#image'), array(
                        'url'   => $data['link'],
                    ));
                    $Tpl->add(array('link#rear', 'column#image'));
                } else {
                    $name   = basename($path);
                    $large  = substr($path, 0, strlen($path) - strlen($name)).'large-'.$name;
                    if ( $xy = @getimagesize($large) ) {
                        $Tpl->add(array('link#front', 'column#image'), array(
                            'url'   => BASE_URL.$large,
                            'viewer'=> str_replace('{unit_eid}', $eid, config('entry_body_image_viewer')),
                        ));
                        $Tpl->add(array('link#rear', 'column#image'));
                    }
                }

                $tiny   = otherSizeImagePath($path, 'tiny');
                if ( $xy = @getimagesize($tiny) ) {
                    $vars['tinyPath']   = $tiny;
                    $vars['tinyX']      = $xy[0];
                    $vars['tinyY']      = $xy[1];
                }
                
                $square = otherSizeImagePath($path, 'square');
                if ( @is_file($square) ) {
                    $vars['squarePath']   = $square;
                    $vars['squareX']      = $squareImgSize;
                    $vars['squareY']      = $squareImgSize;
                }

                $vars['utid']       = $utid;
                $vars['unit_eid']   = $eid;
                $Tpl->add('column#image', $vars);

            //------
            // file
            } else if ( 'file' == $type ) {
                if ( empty($data['path']) ) continue;
                $path   = ARCHIVES_DIR.$data['path'];
                $ext    = ite(pathinfo($path), 'extension');
                $icon   = pathIcon($ext);
                $xy     = getimagesize($icon);
                $vars   = array(
                    'path'  => $path,
                    'icon'  => $icon,
                    'x'     => $xy[0],
                    'y'     => $xy[1],
                );
                if ( !empty($data['caption']) ) $vars['caption'] = $data['caption'];
                $vars['align']  = $data['align'];
                if ( !empty($data['attr']) ) $vars['attr'] = $data['attr'];

                $vars['utid']       = $utid;
                $vars['unit_eid']   = $eid;
                $Tpl->add('column#file', $vars);

            //-----
            // map
            } else if ( 'map' == $type ) {
                if ( empty($data['lat']) ) continue;
                list($x, $y) = explode('x', $data['size']);
                $msg    = str_replace(array(
                    '"', '<', '>', '&'
                ), array(
                    '[[:quot:]]', '[[:lt:]]', '[[:gt:]]', '[[:amp:]]'
                ), $data['msg']);
                $vars   = array(
                    'lat'   => $data['lat'],
                    'lng'   => $data['lng'],
                    'zoom'  => $data['zoom'],
                    'msg'   => $msg,
                    'msgRaw'    => $data['msg'],
                    'x'     => $x,
                    'y'     => $y,
                    'align' => $data['align'],
                );
                if ( !empty($data['attr']) ) $vars['attr'] = $data['attr'];
                $vars['utid']       = $utid;
                $vars['unit_eid']   = $eid;
                $Tpl->add('column#map', $vars);

            //---------
            // youtube
            } else if ( 'youtube' == $type ) {
                if ( empty($data['youtube_id']) ) continue;
                list($x, $y) = explode('x', $data['size']);
                $vars   = array(
                    'youtubeId' => $data['youtube_id'],
                    'x' => $x,
                    'y' => $y,
                    'align' => $data['align'],
                );
                if ( !empty($data['attr']) ) $vars['attr'] = $data['attr'];
                $vars['utid']       = $utid;
                $vars['unit_eid']   = $eid;
                $Tpl->add('column#youtube', $vars);

            //---------
            // eximage
            } else if ( 'eximage' == $type ) {
                if ( empty($data['normal']) ) continue;
                list($x, $y) = explode('x', $data['size']);
                $url    = !empty($data['link']) ? $data['link'] : (!empty($data['large']) ? $data['large'] : null);
                if ( !empty($url) ) {
                    $vars   = array(
                        'url'   => $url,
                    );
                    if ( empty($data['link']) ) $vars['viewer'] = str_replace('{unit_eid}', $eid, config('entry_body_image_viewer'));
                    $Tpl->add(array('link#front', 'column#eximage'), $vars);
                    $Tpl->add(array('link#rear', 'column#eximage'));
                }

                $vars   = array(
                    'normal'    => $data['normal'],
                    'x'         => $x,
                    'y'         => $y,
                    'alt'       => $data['alt'],
                    'large'     => $data['large'],
                );
                if ( !empty($data['caption']) ) $vars['caption'] = $data['caption'];

                $vars['align']      = $data['align'];
                $vars['utid']       = $utid;
                $vars['unit_eid']   = $eid;
                if ( !empty($data['attr']) ) $vars['attr'] = $data['attr'];

                $Tpl->add(array('column#eximage'), $vars);
            
            //-------
            // break
            } else if ( 'break' == $type ) {

                if ( empty($data['label']) ) continue;
                $vars   = array(
                    'label'  => $data['label'],
                );

                if ( !empty($data['attr']) ) {
                    $vars['attr']   = $data['attr'];
                    $vars['class']  = $data['attr']; // legacy
                }

                $vars['utid']       = $utid;
                $vars['unit_eid']   = $eid;
                $vars['align']      = $data['align'];

                $Tpl->add(array('column#break'), $vars);

            } else {
                continue;
            }

            //--------------
            // edit inplace
            if ( 1
                and VIEW == 'entry'
                and 'on' == config('entry_edit_inplace_enable')
                and 'on' == config('entry_edit_inplace')
                and !ADMIN
                and ( 0
                    or sessionWithCompilation()
                    or ( 1
                        and sessionWithContribution()
                        and SUID == ACMS_RAM::entryUser($eid)
                    )
                )
            ) {
                $vars  = array();
                $vars['column:loop.type']     = $type;
                $vars['column:loop.utid']     = $utid;
                $vars['column:loop.unit_eid'] = $eid;
                $vars['column:loop.sort']     = $sort;
                $vars['column:loop.align']    = $align;
                $Tpl->add('inplace#front', $vars);
                $Tpl->add('inplace#rear');
            }

            //-------------
            // close group
            if ( $i === $columnAmount && $currentGroup !== null ) {
                $Tpl->add(array('unitGroup#last', 'column:loop'));
            }

            $Tpl->add('column:loop');
        }

        return true;
    }
}
