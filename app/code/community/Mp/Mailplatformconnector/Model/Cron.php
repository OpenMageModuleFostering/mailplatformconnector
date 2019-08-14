<?php

class Mp_Mailplatformconnector_Model_Cron {

    public function sync($crontab = true) {
        if (! Mage::helper('mailplatform')->getCronTab('sync_subscribers') && $crontab) {
            return $this;
        }

        // collect all subscribers users
        $collectionarray = Mage::getResourceModel('newsletter/subscriber_collection')->showStoreInfo()
            ->showCustomerInfo()
            ->useOnlySubscribed()
            ->toArray()
        ;

        if ($collectionarray['totalRecords'] == 0) {
            return $this;
        }

        $items = $collectionarray['items'];

        $customerInList = array();

        foreach ($items as $item) {
            $customerInList[$item['store_id']][] = $item['subscriber_email'];
        }

        try {
            foreach ($customerInList as $storeId => $emails) {
                $this->_bulkCustomers($storeId, $emails, $crontab);
            }

            Mage::app()->setCurrentStore(0);
        } catch (Exception $e) {
            if (! $crontab) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }

        return $this;
    }

    protected function _bulkCustomers($storeId, $emails, $crontab) {
        Mage::app()->setCurrentStore($storeId);

        $subscribers = Mage::getModel('mailplatform/subscribers');

        foreach ($emails as $email) {
            $isSubscriber = $subscribers->isSubscriberOnList($email);

            if ($isSubscriber === true) {
                $result = $subscribers->activateSubscriber($email);
                if ($result && ! $crontab) {
                    Mage::getSingleton('adminhtml/session')->addSuccess("Mailplatform - " . $email . " updated.");
                } elseif (! $crontab) {
                    Mage::getSingleton('adminhtml/session')->addError("Mailplatform - " . $subscribers->getErrorMessage() . '<br/>');
                }
            } else {
                // If added as not-confirmed, a confirmation mail will be sent
                $details['confirmed'] = ! Mage::helper('mailplatform')->sendConfirmation();
                $result = $subscribers->addSubscriberToList($email, null, $details);

                if ($result && ! $crontab) {
                    Mage::getSingleton('adminhtml/session')->addSuccess("Mailplatform - " . $email . " added to list.");
                } elseif (! $crontab) {
                    Mage::getSingleton('adminhtml/session')->addError("Mailplatform - " . $subscribers->getErrorMessage() . '<br/>');
                }
            }
        }

        return $this;
    }

    public function updateCategories($crontab = true)
    {
        if (! Mage::helper('mailplatform')->getCronTab('sync_categories') && $crontab) {
            return $this;
        }

        $customField = Mp_Mailplatformconnector_Model_Subscribers::getCustomFieldId('product_categories');

        if (! $customField) {
            return $this;
        }

        $categories = Mage::getModel('catalog/category')
            ->getCollection()
            ->addAttributeToSelect(array('entity_id', 'name'))
            ->addIsActiveFilter()
        ;

        if (! $categories->count()) {
            return $this;
        }

        $data = array();

        foreach ($categories as $id => $category) {
            $data['keys'][] = $category->getId();
            $data['values'][] = $category->getName();
        }

        $customfields = Mage::getModel('mailplatform/customfields');

        $customfields->update('checkbox', $customField, $data);

        return $this;
    }

}