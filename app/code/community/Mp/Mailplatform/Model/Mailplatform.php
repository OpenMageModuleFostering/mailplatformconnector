<?php

class Mp_Mailplatform_Model_Mailplatform extends Varien_Object
{

    public function getXMLGeneralConfig($field)
    {
        return Mage::getStoreConfig('mailplatform/general/' . $field);
    }

    public function mailplatformAvailable()
    {
        $requestUri = Mage::app()->getRequest()->getRequestUri();
        if ($this->getXMLGeneralConfig('active') == true && $this->getXMLGeneralConfig('username') != '' && $this->getXMLGeneralConfig('token') != '' && $this->getXMLGeneralConfig('listid') != '' && (strstr($requestUri, 'newsletter/') || strstr($requestUri, 'newsletter_subscriber/') || strstr($requestUri, 'customer/') || strstr($requestUri, 'mailplatform/index') || strstr($requestUri, 'checkout/onepage/'))) {
            return true;
        }
        if (Mage::app()->getStore()->getId() == 0) {
            if ($this->getXMLGeneralConfig('active') != true)
                Mage::getSingleton('adminhtml/session')->addError('Mailplatform Configuration Error: Mailplatform is innactive');
            if ($this->getXMLGeneralConfig('username') == '')
                Mage::getSingleton('adminhtml/session')->addError('Mailplatform Configuration Error: API Username field is empty');
            if ($this->getXMLGeneralConfig('token') == '')
                Mage::getSingleton('adminhtml/session')->addError('Mailplatform Configuration Error: API Token field is empty');
            if ($this->getXMLGeneralConfig('listid') == '')
                Mage::getSingleton('adminhtml/session')->addError('Mailplatform Configuration Error: Mailplatform list field is empty');
        }
        return false;
    }

    private function getCustomerByEmail($email)
    {
        if ($email instanceof Mage_Customer_Model_Customer) {

            $customer = $email;

            return $customer;
        }

        $collection = Mage::getResourceModel('newsletter/subscriber_collection');
        $collection->showCustomerInfo(true)
            ->addSubscriberTypeField()
            ->showStoreInfo()
            ->addFieldToFilter('subscriber_email', $email)
        ;

        return $collection->getFirstItem();
    }

    private function getListIdByStoreId($storeId)
    {
        $store = Mage::getModel('core/store')->load($storeId);
        $list_id = $store->getConfig('mailplatform/general/listid');

        return $list_id;
    }

    public function subscribe($email)
    {
        if (! $this->mailplatformAvailable()) {
            return;
        }

        $customer = $this->getCustomerByEmail($email);
        $customerOldMail = $this->getCustomerOldEmail();

        $merge_vars = array();

        if ($email instanceof Mage_Customer_Model_Customer) {

            $email = $customer->getEmail();

            $merge_vars['FNAME'] = $customer->getFirstname();
            $merge_vars['LNAME'] = $customer->getLastname();
        } elseif ($customer->getCustomerId() != 0) {
            $merge_vars['FNAME'] = $customer->getCustomerFirstname();
            $merge_vars['LNAME'] = $customer->getCustomerLastname();
        } else {
            $merge_vars['FNAME'] = 'Guest';
            $merge_vars['LNAME'] = 'Guest';
        }

        try {

            $url2 = Mage::getStoreConfig('mailplatform/general/url');
            $username = Mage::getStoreConfig('mailplatform/general/username');
            $token = Mage::getStoreConfig('mailplatform/general/token');
            $listid = Mage::getStoreConfig('mailplatform/general/listid');

            $xml = '<xmlrequest>
						<username>' . $username . '</username>
						<usertoken>' . $token . '</usertoken>
						<requesttype>subscribers</requesttype>
						<requestmethod>AddSubscriberToList</requestmethod>
						<details>';
            $xml = $xml . '<emailaddress>' . $email . '</emailaddress>';
            $xml = $xml . '<mailinglist>' . $listid . '</mailinglist>
						<format>html</format>
						<confirmed>yes</confirmed>
						</details>
						</xmlrequest>';
            $ch = curl_init($url2);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
            $result = @curl_exec($ch);
            if ($result === false) {
                Mage::getSingleton('customer/session')->addError("Mailplatform - Error Performing the Request");
            } else {
                $xml_doc = simplexml_load_string($result);
                Mage::getSingleton('customer/session')->addSuccess("Mailplatform - Added - " . $email . " OK");
                if ($xml_doc->status != 'SUCCESS') {
                    Mage::getSingleton('customer/session')->addError("Mailplatform -" . $xml_doc->errormessage . '<br/>');
                }
            }

        } catch (exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
    }

    public function unsubscribe($email)
    {
        if (! $this->mailplatformAvailable()) {
            return;
        }

        try {

            $url2 = Mage::getStoreConfig('mailplatform/general/url');
            $username = Mage::getStoreConfig('mailplatform/general/username');
            $token = Mage::getStoreConfig('mailplatform/general/token');
            $listid = Mage::getStoreConfig('mailplatform/general/listid');

            $xml = '<xmlrequest>
			<username>' . $username . '</username>
			<usertoken>' . $token . '</usertoken>
			<requesttype>subscribers</requesttype>
			<requestmethod>DeleteSubscriber</requestmethod>
			<details>
			<emailaddress>' . $email->getEmail() . '</emailaddress>
			<list>' . $listid . '</list>
			</details>
			</xmlrequest>';

            $ch = curl_init($url2);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
            $result = @curl_exec($ch);
            if ($result === false) {
                Mage::getSingleton('customer/session')->addError("Mailplatform - Error Performing the Request");
            } else {
                $xml_doc = simplexml_load_string($result);

                Mage::getSingleton('customer/session')->addSuccess("Mailplatform - Removal - " . $email . " OK");

                if ($xml_doc->status != 'SUCCESS') {
                    Mage::getSingleton('customer/session')->addError("Mailplatform -" . $xml_doc->errormessage . '<br/>');
                }
            }

        /**
         * Submit to Mailplatform
         */
        } catch (exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
    }

    public function batchSubscribe($items)
    {
        if (! $this->mailplatformAvailable()) {
            return;
        }

        $batch = array();
        $customerInList = array();
        foreach ($items as $item) {

            $merge_vars = array();

            if ($item['customer_id'] != 0) {
                $merge_vars['FNAME'] = $item['customer_firstname'];
                $merge_vars['LNAME'] = $item['customer_lastname'];
                $merge_vars['StoreId'] = $item['store_id'];
            } else {
                $merge_vars['FNAME'] = 'Guest';
                $merge_vars['LNAME'] = 'Guest';
                $merge_vars['StoreId'] = $item['store_id'];
            }

            $merge_vars['EMAIL'] = $item['subscriber_email'];

            $customerInList[$item['store_id']][] = $merge_vars;
        }

        try {
            foreach ($customerInList as $store => $customers) {

                Mage::app()->setCurrentStore($store);
                $url2 = Mage::getStoreConfig('mailplatform/general/url');
                $username = Mage::getStoreConfig('mailplatform/general/username');
                $token = Mage::getStoreConfig('mailplatform/general/token');
                $listid = Mage::getStoreConfig('mailplatform/general/listid');

                if (count($customers) == 1) {

                    $xml = '<xmlrequest>
						<username>' . $username . '</username>
						<usertoken>' . $token . '</usertoken>
						<requesttype>subscribers</requesttype>
						<requestmethod>AddSubscriberToList</requestmethod>
						<details>';
                    $xml = $xml . '<emailaddress>' . $customers[0]["EMAIL"] . '</emailaddress>';
                    $xml = $xml . '<mailinglist>' . $listid . '</mailinglist>
						<format>html</format>
						<confirmed>yes</confirmed>
						</details>
						</xmlrequest>';
                    $ch = curl_init($url2);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
                    $result = @curl_exec($ch);
                    if ($result === false) {
                        Mage::getSingleton('adminhtml/session')->addError("Mailplatform - Error Performing the Request");
                    } else {
                        $xml_doc = simplexml_load_string($result);
                        Mage::getSingleton('adminhtml/session')->addSuccess("Mailplatform - " . $customers[0]["EMAIL"] . " OK");

                        if ($xml_doc->status != 'SUCCESS') {
                            Mage::getSingleton('adminhtml/session')->addError("Mailplatform -" . $xml_doc->errormessage . '<br/>');
                        }
                    }

                } else {
                    foreach ($customers as $custom) {

                        $xml = '<xmlrequest>
							<username>' . $username . '</username>
							<usertoken>' . $token . '</usertoken>
							<requesttype>subscribers</requesttype>
							<requestmethod>AddSubscriberToList</requestmethod>
							<details>';
                        $xml = $xml . '<emailaddress>' . $custom["EMAIL"] . '</emailaddress>';
                        $xml = $xml . '<mailinglist>' . $listid . '</mailinglist>
							<format>html</format>
							<confirmed>yes</confirmed>
							</details>
							</xmlrequest>';

                        $ch = curl_init($url2);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($ch, CURLOPT_POST, 1);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
                        $result = @curl_exec($ch);
                        if ($result === false) {
                            Mage::getSingleton('adminhtml/session')->addError("Mailplatform - Error Performing the Request");
                        } else {
                            $xml_doc = simplexml_load_string($result);

                            Mage::getSingleton('adminhtml/session')->addSuccess("Mailplatform - " . $custom["EMAIL"] . " OK");
                            if ($xml_doc->status != 'SUCCESS') {
                                Mage::getSingleton('adminhtml/session')->addError("Mailplatform -" . $xml_doc->errormessage . '<br/>');
                            }
                        }
                    }
                }
            }

            Mage::app()->setCurrentStore(0);

        } catch (exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
    }

    public function getCustomerOldEmail()
    {
        return Mage::getSingleton('core/session',  array('name' => 'frontend'))->getData('customer_old_email');
    }

}