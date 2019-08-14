<?php

class Mp_Mailplatformconnector_Helper_Data extends Mage_Core_Helper_Abstract
{

    public function isEnabled()
    {
        $requestUri = Mage::app()->getRequest()->getRequestUri();

        if (
            Mage::getStoreConfig('mailplatform/general/active') == true
            && $this->getUsername() != ''
            && $this->getToken() != ''
            && $this->getListId() != ''
            && (strstr($requestUri, 'newsletter/')
            || strstr($requestUri, 'newsletter_subscriber/')
            || strstr($requestUri, 'customer/')
            || strstr($requestUri, 'mailplatform/index')
            || strstr($requestUri, 'checkout/onepage/'))
            )
        {
            return true;
        }

        if (Mage::app()->getStore()->getId() == 0) {
            if (Mage::getStoreConfig('mailplatform/general/active') != true)
                Mage::getSingleton('adminhtml/session')->addError('Mailplatform Configuration Error: Mailplatform is innactive');
            if ($this->getUsername() == '')
                Mage::getSingleton('adminhtml/session')->addError('Mailplatform Configuration Error: API Username field is empty');
            if ($this->getToken() == '')
                Mage::getSingleton('adminhtml/session')->addError('Mailplatform Configuration Error: API Token field is empty');
            if ($this->getListId() == '')
                Mage::getSingleton('adminhtml/session')->addError('Mailplatform Configuration Error: Mailplatform list field is empty');
        }

        return false;
    }

    public function logError()
    {
        return Mage::getStoreConfig('mailplatform/log/error');
    }

    public function logSuccess()
    {
        return Mage::getStoreConfig('mailplatform/log/success');
    }

    public function getLogFileName()
    {
        return Mage::getStoreConfig('mailplatform/log/file_name');
    }

    public function getListId()
    {
        return trim(Mage::getStoreConfig('mailplatform/general/listid'));
    }

    public function getToken()
    {
        return trim(Mage::getStoreConfig('mailplatform/general/token'));
    }

    public function getUsername()
    {
        return trim(Mage::getStoreConfig('mailplatform/general/username'));
    }

    public function getFormat()
    {
        return trim(Mage::getStoreConfig('mailplatform/general/format'));
    }

    public function getUrl()
    {
        return trim(Mage::getStoreConfig('mailplatform/general/url'));
    }

    public function getCronTab($crontab)
    {
        return (boolean) Mage::getStoreConfig('mailplatform/crontab/' . $crontab);
    }

    public function sendConfirmation()
    {
        return Mage::getStoreConfigFlag('mailplatform/subscribe/double_optin');
    }

}