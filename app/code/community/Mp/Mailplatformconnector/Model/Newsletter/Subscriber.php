<?php

class Mp_Mailplatformconnector_Model_Newsletter_Subscriber extends Mage_Newsletter_Model_Subscriber
{

    public function subscribe($email)
    {
        try {
            $status = parent::subscribe($email);

            if (! Mage::helper('mailplatform')->isEnabled()) {
                return $status;
            }

            $subscribers = Mage::getModel('mailplatform/subscribers');

            $isSubscriber = $subscribers->isSubscriberOnList($email);

            if ($isSubscriber === true) {
                return $status;
            }

            $subscribers->addSubscriberToList($email);

            return $status;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function unsubscribe()
    {
        parent::unsubscribe();

        Mage::getModel('mailplatform/subscribers')->unsubscribeSubscriber($this->getEmail());

        return $this;
    }

    /**
     * Saving customer cubscription status
     *
     * @param Mage_Customer_Model_Customer $customer
     * @return Mage_Newsletter_Model_Subscriber
     */
    public function subscribeCustomer($customer)
    {
        parent::subscribeCustomer($customer);

        if ($customer->isConfirmationRequired() && $customer->getConfirmation()) {
            return $this;
        }

        $email = $customer->getEmail();

        if ($this->getStatus() == self::STATUS_SUBSCRIBED) {
            $subscribers = Mage::getModel('mailplatform/subscribers');

            $isSubscriber = $subscribers->isSubscriberOnList($email);

            if ($isSubscriber === true) {
                return $this;
            }

            $subscribers->addSubscriberToList($email);
        } else {
            Mage::getModel('mailplatform/subscribers')->unsubscribeSubscriber($email);
        }

        return $this;
    }
}