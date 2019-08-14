<?php

class Mp_Mailplatform_IndexController extends Mage_Adminhtml_Controller_Action
{

    public function indexAction()
    {
        // collect all subscribers users
        $collectionarray = Mage::getResourceModel('newsletter/subscriber_collection')->showStoreInfo()
            ->showCustomerInfo()
            ->useOnlySubscribed()
            ->toArray()
        ;

        if ($collectionarray['totalRecords'] > 0) {
            Mage::getSingleton('mailplatform/mailplatform')->batchSubscribe($collectionarray['items']);
        }

        $this->_redirect('adminhtml/newsletter_subscriber/');
    }
}