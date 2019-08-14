<?php

class Mp_Mailplatformconnector_Model_Observer
{

    public function onCheckoutSubmit(Varien_Event_Observer $observer)
    {
        if (! Mage::helper('mailplatform')->isEnabled()) {
            return $this;
        }

        $order = $observer->getOrder();
        $quote = $observer->getQuote();

        $confirmed = false;

        $isSubscribed = Mage::app()->getRequest()->getParam('is_subscribed');

        if ($isSubscribed && ! Mage::helper('mailplatform')->sendConfirmation()) {
            $confirmed = true;
        }

        $billingAddress = $order->getBillingAddress();

        $email = $billingAddress->getEmail();

        $subscribers = Mage::getModel('mailplatform/subscribers');

        $details = array(
            'confirmed' => $confirmed
        );

        $customFields = array(
            array( // First Name
                'fieldid' => $subscribers->getCustomFieldId('firstname'),
                'value' => $billingAddress->getFirstname(),
            ),
            array( // Last Name
                'fieldid' => $subscribers->getCustomFieldId('lastname'),
                'value' => $billingAddress->getLastname(),
            ),
            array( //City
                'fieldid' => $subscribers->getCustomFieldId('city'),
                'value' => $billingAddress->getCity(),
            )
        );

        if ($subscribers->getCustomFieldId('purchase_date')) {
            $date = new Zend_Date(strtotime($order->getCreatedAt()), null, Mage::app()->getLocale()->getLocaleCode());
            $customFields[] = array( //Purchase Date
                'fieldid' => $subscribers->getCustomFieldId('purchase_date'),
                'value' => array('dd' => $date->getDay()->get('dd'), 'mm' => $date->getMonth()->get('MM'), 'yy' => $date->getYear()->get('yyyy')),
            );
        }

        if ($subscribers->getCustomFieldId('product_categories')) {
            $categories = array();

            foreach ($order->getAllItems() as $item) {
                if ($item->getParentItem()) {
                    continue;
                }

                $categoryIds = Mage::getModel('catalog/product')->load($item->getProductId())->getCategoryIds();
                $categories = array_merge($categories, $categoryIds);
            }

            $categories = array_unique($categories);

            foreach ($categories as $id => $category) {
                $productCategories['key' . $id] = $category;
            }

            $customFields[] = array(//Product Categories
                'fieldid' => $subscribers->getCustomFieldId('product_categories'),
                'value' => $productCategories,
                'strategy' => 'add',
            );
        }

        $isSubscriber = $subscribers->isSubscriberOnList($email);

        if ($isSubscriber === true) {
            $subscribers->update($email, null, $customFields);
        } else {
            $subscribers->addSubscriberToList($email, null, $details, $customFields);
        }

        return $this;
    }

}