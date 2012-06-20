<?php
/**
 * ACMS_GET_Filter_BuildModule
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Filter_BuildModule extends ACMS_GET_Filter
{
    function get()
    {
        // 無限ループを作れるので，全体の実行回数を3回に制限
        static $filteredBuildModule = 0;

        if ( $filteredBuildModule < 3 ) {
            $filteredBuildModule++;
            $this->tpl = build($this->tpl, $this->Post);
        }
        return $this->tpl;
    }
}
