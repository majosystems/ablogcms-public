<?php
/**
 * ACMS_GET_Ajax_Unit
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Ajax_Unit extends ACMS_GET
{
    function get()
    {
        if ( !($column = $this->Get->get('column')) ) { return false; }
        list($pfx, $type)   = explode('-', $column, 2);

        $Config = new Field(Field::singleton('config'));
        if ( $rid = intval($this->Get->get('rid')) ) {
            $Config->overload(loadConfig(BID, $rid));
        }

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());

        $Column = new Field();
        $Column->setField('pfx', $pfx);
        switch ( $type ) {
            case 'text':
                foreach ( $Config->getArray('column_text_tag') as $i => $tag ) {
                    $Tpl->add(array('textTag:loop', $type), array(
                        'value' => $tag,
                        'label' => $Config->get('column_text_tag_label', '', $i),
                    ));
                }
                break;
            case 'image':
                foreach ( $Config->getArray('column_image_size') as $j => $size ) {
                    $Tpl->add(array('size:loop', $type), array(
                        'value' => $size,
                        'label' => $Config->get('column_image_size_label', '', $j),
                    ));
                }
                break;
            case 'file':
                break;
            case 'map':
                foreach ( $Config->getArray('column_map_size') as $j => $size ) {
                    $Tpl->add(array('size:loop', $type), array(
                        'value' => $size,
                        'label' => $Config->get('column_map_size_label', '', $j),
                    ));
                }
                break;
            case 'youtube':
                foreach ( $Config->getArray('column_youtube_size') as $j => $size ) {
                    $Tpl->add(array('size:loop', $type), array(
                        'value' => $size,
                        'label' => $Config->get('column_youtube_size_label', '', $j),
                    ));
                }
                break;
            case 'eximage':
                foreach ( $Config->getArray('column_eximage_size') as $j => $size ) {
                    $Tpl->add(array('size:loop', $type), array(
                        'value' => $size,
                        'label' => $Config->get('column_eximage_size_label', '', $j),
                    ));
                }
                break;
            case 'break':
                break;
            default:
                return '';
        }

        if ( 'on' === config('unit_group') ) {
            $classes = configArray('unit_group_class');
            $labels  = configArray('unit_group_label');
            foreach ( $labels as $i => $label ) {
                $Tpl->add(array('group:loop', 'group:veil', $type), array(
                     'group.value'     => $classes[$i],
                     'group.label'     => $label,
                     'group.selected'  => ($classes[$i] === $Config->get('group')) ? config('attr_selected') : '',
                ));
            }
            $Tpl->add(array('group:veil', $type), array(
                'group.pfx' => $Column->get('pfx'),
            ));
        }

        $vars   = $this->buildField($Column, $Tpl, $type, 'column');

        $Tpl->add($type, $vars);
        return $Tpl->get();
    }
}
