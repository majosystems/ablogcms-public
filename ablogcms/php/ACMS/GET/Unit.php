<?php
/**
 * ACMS_GET_Unit
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Unit extends ACMS_GET
{
    function buildUnit(& $Column, & $Tpl, $rootBlock = array(), $preAlign = null, $renderGroup = true)
    {
        return ACMS_GET_Entry::buildColumn($Column, $Tpl, $rootBlock, $preAlign, $renderGroup);
    }
}
