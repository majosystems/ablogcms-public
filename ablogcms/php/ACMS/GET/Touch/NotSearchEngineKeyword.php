<?php
/**
 * ACMS_GET_Touch_NotSearchEngineKeyword
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Touch_NotSearchEngineKeyword extends ACMS_GET
{
    function get()
    {
        return !SEARCH_ENGINE_KEYWORD ? $this->tpl : '';
    }
}
