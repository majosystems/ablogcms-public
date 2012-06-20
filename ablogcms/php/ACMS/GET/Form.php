<?php
/**
 * ACMS_GET_Form
 *
 * This file is part of the a-blog cms package.
 * Please see LICENSE. Complete license information is there.
 *
 * (c) appleple inc. <info@appleple.com>
 */
class ACMS_GET_Form extends ACMS_GET
{
    function get()
    {
        $step   = $this->Get->get('step');
        if ( $this->Post->isValidAll() ) $step  = $this->Post->get('step', $step);

        $Tpl    = new Template($this->tpl, new ACMS_Corrector());
        $Block  = !(empty($step) or is_bool($step)) ? 'step#'.$step : 'step';
        $this->Post->delete('step');
        $Tpl->add($Block, $this->buildField($this->Post, $Tpl, $Block, ''));
        return $Tpl->get();
    }
}
