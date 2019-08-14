<?php

class Mp_Mailplatformconnector_Block_Adminhtml_System_Config_Form_Button_Synccategories extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    /*
     * Set template
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('mailplatform/system/config/button.phtml');
    }

    /**
     * Return element html
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->_toHtml();
    }

    /**
     * Return ajax url for button
     *
     * @return string
     */
    public function getAjaxCheckUrl()
    {
        return Mage::helper('adminhtml')->getUrl('adminhtml/mailplatform/syncCategories');
    }

    /**
     * Generate button html
     *
     * @return string
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()
            ->createBlock('adminhtml/widget_button')
            ->setData(
                array(
                    'id' => $this->getButtonId(),
                    'label' => $this->helper('adminhtml')->__('Sync'),
                )
            );

        return $button->toHtml();
    }

    public function getButtonId()
    {
        return 'sync_categories';
    }

    public function getResponseFieldId()
    {
        return 'sync_categories_response';
    }
}