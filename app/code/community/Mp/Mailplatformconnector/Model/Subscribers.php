<?php

class Mp_Mailplatformconnector_Model_Subscribers extends Mp_Mailplatformconnector_Model_Request
{

    protected function _construct()
    {
        parent::_construct();

        $this->setRequestType('subscribers');
    }

    public function addSubscriberToList($email, $mailingList = null, $details = null, $customFields = null)
    {
        $this->setRequestMethod('AddSubscriberToList');

        $this->setMailingList($mailingList);

        if (isset($details['format'])) {
            $this->setFormat($details['format']);
        }

        //Custom details
        $allowed = array('confirmed', 'confirm_language', 'add_to_autoresponders');

        if ($details) {
            $details = array_intersect_key($details, array_flip($allowed));
        }

        // Check is set confirmation param or add value from config
        if (! isset($details['confirmed'])) {
            // If added as not-confirmed, a confirmation mail will be sent
            $details['confirmed'] = ! Mage::helper('mailplatform')->sendConfirmation();
        }

        $details['format'] = $this->_getFormat();

        //Required
        $details['mailinglist'] = $this->_getMailingList();

        $details['emailaddress'] = $email;

        $this->addDetails($details);

        if (! empty($customFields)) {
            $this->addCustomfields($customFields);
        }

        return $this->send();
    }

    public function isSubscriberOnList($email, $mailingList = null)
    {
        $this->setRequestMethod('IsSubscriberOnList');

        $this->setMailingList($mailingList);

        //Required
        $details['mailinglist'] = $this->_getMailingList();

        $details['emailaddress'] = $email;

        $this->addDetails($details);

        if ($this->send() && (string) $this->getResponseData() != '') {
            return true;
        }

        return false;
    }

    public function update($email, $mailingList = null, $customFields = null)
    {
        $this->setRequestMethod('Update');

        $this->setMailingList($mailingList);

        //Required
        $details['listid'] = $this->_getMailingList();

        $details['emailaddress'] = $email;

        $this->addDetails($details);

        if (! empty($customFields)) {
            $this->addCustomfields($customFields);
        }

        if ($this->send() && $this->getResponseData() == 'TRUE') {
            return true;
        }

        return false;
    }

    static public function getCustomFieldId($customField)
    {
        $id = trim(Mage::getStoreConfig('mailplatform/subscribe/' . $customField));

        if (! $id || $id == '') {
            return;
        }

        return (int) $id;
    }

    public function getSendThankYou()
    {
        return (boolean) Mage::getStoreConfig('mailplatform/unsubscribe/sendthankyou');
    }

    public function unsubscribeSubscriber($email, $mailingList = null)
    {
        $this->setRequestMethod('UnsubscribeSubscriber');

        $this->setMailingList($mailingList);

        //Required
        $details['listid'] = $this->_getMailingList();

        $details['emailaddress'] = $email;

        $details['sendthankyou'] = $this->getSendThankYou();

        $this->addDetails($details);

        return $this->send();
    }

    public function activateSubscriber($email, $mailingList = null)
    {
        $this->setRequestMethod('ActivateSubscriber');

        $this->setMailingList($mailingList);

        //Required
        $details['listid'] = $this->_getMailingList();

        $details['emailaddress'] = $email;

        $this->addDetails($details);

        return $this->send();
    }
}