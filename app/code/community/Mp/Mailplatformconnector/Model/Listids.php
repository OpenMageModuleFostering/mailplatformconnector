<?php

class Mp_Mailplatformconnector_Model_Listids extends Varien_Object
{

    protected $_options;

    public function toOptionArray($isMultiselect = false)
    {
        if (! $this->_options) {
            $lists = Mage::getModel('mailplatform/lists');

            if ($lists->getLists() === false) {
                Mage::getSingleton('adminhtml/session')->addError('Mailplatform Configuration Error!');
                return $this->_options;
            }

            $data = $lists->getResponseData();

            foreach ($data->item as $item) {
                $this->_options[] = array(
                    'value' => $item->listid,
                    'label' => $item->name
                );
            }
        }

        $options = $this->_options;

        if (! $isMultiselect) {
            array_unshift($options, array(
                'value' => '',
                'label' => Mage::helper('adminhtml')->__('--Please Select--')
            ));
        }

        return $options;
    }
}