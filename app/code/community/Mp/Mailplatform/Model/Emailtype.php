<?php

class Mp_Mailplatform_Model_Emailtype
{

    public function toOptionArray()
    {
        return array(
            array(
                'value' => 'html',
                'label' => Mage::helper('adminhtml')->__('HTML')
            ),
            array(
                'value' => 'text',
                'label' => Mage::helper('adminhtml')->__('Text')
            )
        );
    }
}