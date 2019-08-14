<?php

class Mp_Mailplatformconnector_Adminhtml_MailplatformController extends Mage_Adminhtml_Controller_Action
{

    public function indexAction()
    {
        Mage::getModel('mailplatform/cron')->sync(false);

        $this->_redirect('adminhtml/newsletter_subscriber/');
    }

    public function syncCategoriesAction()
    {
        try {
            Mage::getModel('mailplatform/cron')->updateCategories(false);
            $this->getResponse()->setBody(Mage::helper('adminhtml')->__('Status: %s', 'OK'));
        } catch (Exception $e) {
            $this->getResponse()->setBody(Mage::helper('adminhtml')->__('Status: %s', 'FAILED'));
        }
    }

}