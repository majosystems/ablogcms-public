<?php
/**
 * ACMS_GET_Field_ValueList
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Field_ValueList extends ACMS_GET
{
    var $_scope = array(
        'bid'   => 'global',
        'field' => 'global',
    );

    var $_axis = array(
        'bid'   => 'self',
    );

    function get()
    {
        $Tpl    = new Template($this->tpl, new ACMS_Corrector());

        $DB = DB::singleton(dsn());
        $SQL = SQL::newSelect('field');
        $SQL->addSelect('field_value');
        $SQL->addLeftJoin('blog', 'blog_id', 'field_blog_id');

        ACMS_Filter::blogTree($SQL, $this->bid, $this->blogAxis());
        ACMS_Filter::blogStatus($SQL);
        ACMS_Filter::fieldList($SQL, $this->Field);

        $SQL->setLimit(config('field_value-list_limit'));
        $SQL->setGroup('field_value');
        $SQL->setOrder('field_value', strtoupper(config('field_value-list_order')));

        $q  = $SQL->get(dsn());

        if ( $DB->query($q, 'fetch') and ($row = $DB->fetch($q)) ) {
            $i      = 0;
            $j      = $DB->affected_rows();
            do {
                $i++;

                $value = $row['field_value'];

                //------
                // glue
                if ( $i !== $j ) {
                    $Tpl->add('glue');
                }

                $Tpl->add('value:loop', array('value' => $value));
            } while ( !!($row = $DB->fetch($q)) );
        }

        return $Tpl->get();
    }
}
