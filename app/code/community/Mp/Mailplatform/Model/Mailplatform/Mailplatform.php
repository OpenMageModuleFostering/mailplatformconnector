<?php
class Mp_Mailplatform_Model_Mailplatform_Mailplatform extends Varien_Object
{
	
	public function getXMLGeneralConfig($field) {
		return Mage::getStoreConfig('mailplatform/general/'.$field);
	}
	
	public function mailplatformAvailable() {
		if (  	$this->getXMLGeneralConfig('active') == true &&
				$this->getXMLGeneralConfig('username') != '' &&
				$this->getXMLGeneralConfig('tocken') != '' &&
				$this->getXMLGeneralConfig('listid') != ''
				&&
				    (
				    strstr($_SERVER['REQUEST_URI'], 'newsletter/') ||
				    strstr($_SERVER['REQUEST_URI'], 'newsletter_subscriber/') ||
				    strstr($_SERVER['REQUEST_URI'], 'customer/') ||
				    strstr($_SERVER['REQUEST_URI'], 'mailplatform/index') ||
					strstr($_SERVER['REQUEST_URI'], 'checkout/onepage/')
				    )
				) {
			return true;
		}
		if (Mage::app()->getStore()->getId() == 0){
			if($this->getXMLGeneralConfig('active') != true) Mage::getSingleton('adminhtml/session')->addError('Mailplatform Configuration Error: Mailplatform is innactive');
			if($this->getXMLGeneralConfig('username') == '' ) Mage::getSingleton('adminhtml/session')->addError('Mailplatform Configuration Error: API Username field is empty');
			if($this->getXMLGeneralConfig('tocken') == '' ) Mage::getSingleton('adminhtml/session')->addError('Mailplatform Configuration Error: API Tocken field is empty');
			if($this->getXMLGeneralConfig('listid') == '' ) Mage::getSingleton('adminhtml/session')->addError('Mailplatform Configuration Error: Mailplatform list field is empty');
		}
		return false;
	}

    private function getCustomerByEmail($email)
    {
			if (($email instanceof Mage_Customer_Model_Customer)) {

           		 $customer = $email;

            	return $customer;
        	}

			$collection = Mage::getResourceModel('newsletter/subscriber_collection');
            $collection
	            ->showCustomerInfo(true)
	            ->addSubscriberTypeField()
	            ->showStoreInfo()
	            ->addFieldToFilter('subscriber_email',$email);

			return $collection->getFirstItem();
    }

	private function getListIdByStoreId($storeId)
	{
		$store = Mage::getModel('core/store')->load($storeId);
		$list_id = $store->getConfig('mailplatform/general/listid');

		return $list_id;

	}

	public function subscribe($email) {
		if ($this->mailplatformAvailable()) {
			
			$customer = $this->getCustomerByEmail($email);
			$customerOldMail = $this->getCustomerOldEmail();

			$merge_vars = array();

			if (($email instanceof Mage_Customer_Model_Customer)) {

					$email = $customer->getEmail();

					$merge_vars['FNAME'] = $customer->getFirstname();
					$merge_vars['LNAME'] = $customer->getLastname();

			}elseif ($customer->getCustomerId() !=0 ) {
				$merge_vars['FNAME'] = $customer->getCustomerFirstname();
				$merge_vars['LNAME'] = $customer->getCustomerLastname();
			} else {
				$merge_vars['FNAME'] = 'Guest';
				$merge_vars['LNAME'] = 'Guest';
			}
			try {
				
				
				$url2 = Mage::getStoreConfig('mailplatform/general/url');
						//$url = $this->getMailplatform()->getXMLGeneralConfig("url");
				$username = Mage::getStoreConfig('mailplatform/general/username');
				$tocken = Mage::getStoreConfig('mailplatform/general/tocken');
				$listid = Mage::getStoreConfig('mailplatform/general/listid');
				
				//Zend_Debug::dump($url2);
				//Zend_Debug::dump($username);
				//Zend_Debug::dump($tocken);						
				//Zend_Debug::dump($listid);
				//Zend_Debug::dump($email);
						//Zend_Debug::dump($customers);
				
				$xml = '<xmlrequest>
							<username>' . $username . '</username>
							<usertoken>' . $tocken . '</usertoken>
							<requesttype>subscribers</requesttype>
							<requestmethod>AddSubscriberToList</requestmethod>
							<details>';
							$xml = $xml . '<emailaddress>' . $email . '</emailaddress>';
							$xml = $xml . '<mailinglist>'. $listid .'</mailinglist>
							<format>html</format>
							<confirmed>yes</confirmed>
							</details>
							</xmlrequest>';	
				$ch = curl_init($url2);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
				$result = @curl_exec($ch);
				if($result === false) {
					Mage::getSingleton('customer/session')->addError("Mailplatform - Error Performing the Request");
				} else {
					$xml_doc = simplexml_load_string($result);
				//Zend_Debug::dump($result);
					Mage::getSingleton('customer/session')->addSuccess("Mailplatform - Addet - " . $email . " OK");
				if ($xml_doc->status == 'SUCCESS') {
				//	echo 'Data is ', $xml_doc->data, '<br/>';
				} else {
					Mage::getSingleton('customer/session')->addError("Mailplatform -" . $xml_doc->errormessage . '<br/>');
					}
				}
				
				//Zend_Debug::dump($result);
				//echo "AAAAAAAAA";
				//die("AAAAAA");

			} catch ( exception $e ) {
				Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
			}

		}
	}

	public function unsubscribe($email) {
		//Zend_Debug::dump($email->getEmail());
		if ( $this->mailplatformAvailable() ) {

			try {

				$url2 = Mage::getStoreConfig('mailplatform/general/url');
						//$url = $this->getMailplatform()->getXMLGeneralConfig("url");
				$username = Mage::getStoreConfig('mailplatform/general/username');
				$tocken = Mage::getStoreConfig('mailplatform/general/tocken');
				$listid = Mage::getStoreConfig('mailplatform/general/listid');
				
				//Zend_Debug::dump($url2);
				//Zend_Debug::dump($username);
				//Zend_Debug::dump($tocken);						
				//Zend_Debug::dump($listid);
						//Zend_Debug::dump($store);
						//Zend_Debug::dump($customers);
				$xml = '<xmlrequest>
				<username>' . $username . '</username>
				<usertoken>' . $tocken . '</usertoken>
				<requesttype>subscribers</requesttype>
				<requestmethod>DeleteSubscriber</requestmethod>
				<details>
				<emailaddress>' . $email->getEmail() . '</emailaddress>
				<list>' .$listid. '</list>
				</details>
				</xmlrequest>';
	
				$ch = curl_init($url2);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
				$result = @curl_exec($ch);
				if($result === false) {
					Mage::getSingleton('customer/session')->addError("Mailplatform - Error Performing the Request");
				} else {
					$xml_doc = simplexml_load_string($result);
				//Zend_Debug::dump($result);
					Mage::getSingleton('customer/session')->addSuccess("Mailplatform - Removal - " . $email . " OK");
				if ($xml_doc->status == 'SUCCESS') {
				//	echo 'Data is ', $xml_doc->data, '<br/>';
				} else {
					Mage::getSingleton('customer/session')->addError("Mailplatform -" . $xml_doc->errormessage . '<br/>');
					}
				}
				
				//Zend_Debug::dump($result);
				//echo "AAAAAAAAAAAA";
				//die($email);
				/**
				 * Submit to Mailplatform
				 * */
				
			} catch ( exception $e ) {
				Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
			}
		}
	}
	public function batchSubscribe($items) {
		//echo "In batch";
		
		if ( $this->mailplatformAvailable() ) {
			
		//	echo "ENABLED";
		$batch = array();
			$customerInList = array();
			foreach($items as $item) {

				$merge_vars = array();

				if($item['customer_id'] !=0) {
					$merge_vars['FNAME'] = $item['customer_firstname'];
					$merge_vars['LNAME'] = $item['customer_lastname'];
					$merge_vars['StoreId'] = $item['store_id'];

				} else {
					$merge_vars['FNAME'] = 'Guest';
					$merge_vars['LNAME'] = 'Guest';
					$merge_vars['StoreId'] = $item['store_id'];
				}

				$merge_vars['EMAIL'] = $item['subscriber_email'];

				//$batch[] = $merge_vars;
				$customerInList[$item['store_id']][]= $merge_vars;

			}
			
			//Zend_Debug::dump($customerInList);
			
	 			try {
				foreach ($customerInList as $store => $customers)
				{
					
						Mage::app()->setCurrentStore($store);
						$url2 = Mage::getStoreConfig('mailplatform/general/url');
						//$url = $this->getMailplatform()->getXMLGeneralConfig("url");
						$username = Mage::getStoreConfig('mailplatform/general/username');
						$tocken = Mage::getStoreConfig('mailplatform/general/tocken');
						$listid = Mage::getStoreConfig('mailplatform/general/listid');
						//echo "<hr>";
						//Zend_Debug::dump($url2);
						//Zend_Debug::dump($username);
						//Zend_Debug::dump($tocken);						
						//Zend_Debug::dump($listid);
						//Zend_Debug::dump($store);
						//Zend_Debug::dump($customers);
						if(count($customers) == 1)
						{
					
							$xml = '<xmlrequest>
							<username>' . $username . '</username>
							<usertoken>' . $tocken . '</usertoken>
							<requesttype>subscribers</requesttype>
							<requestmethod>AddSubscriberToList</requestmethod>
							<details>';
							$xml = $xml . '<emailaddress>' . $customers[0]["EMAIL"] . '</emailaddress>';
							$xml = $xml . '<mailinglist>'. $listid .'</mailinglist>
							<format>html</format>
							<confirmed>yes</confirmed>
							</details>
							</xmlrequest>';	
								$ch = curl_init($url2);
								curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
								curl_setopt($ch, CURLOPT_POST, 1);
								curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
								$result = @curl_exec($ch);
								if($result === false) {
									Mage::getSingleton('adminhtml/session')->addError("Mailplatform - Error Performing the Request");
								}
								else {
									$xml_doc = simplexml_load_string($result);
									//Zend_Debug::dump($result);
									Mage::getSingleton('adminhtml/session')->addSuccess("Mailplatform - " . $customers[0]["EMAIL"] . " OK");
									if ($xml_doc->status == 'SUCCESS') {
									//	echo 'Data is ', $xml_doc->data, '<br/>';
									} else {
										Mage::getSingleton('adminhtml/session')->addError("Mailplatform -" . $xml_doc->errormessage . '<br/>');
									}
								}
						//	Zend_Debug::dump($xml);
						}else{
							foreach($customers as $custom)
							{
								
								$xml = '<xmlrequest>
								<username>' . $username . '</username>
								<usertoken>' . $tocken . '</usertoken>
								<requesttype>subscribers</requesttype>
								<requestmethod>AddSubscriberToList</requestmethod>
								<details>';
								$xml = $xml . '<emailaddress>' . $custom["EMAIL"] . '</emailaddress>';
								$xml = $xml . '<mailinglist>'. $listid .'</mailinglist>
								<format>html</format>
								<confirmed>yes</confirmed>
								</details>
								</xmlrequest>';	
								//Zend_Debug::dump($xml);
								$ch = curl_init($url2);
								curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
								curl_setopt($ch, CURLOPT_POST, 1);
								curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
								$result = @curl_exec($ch);
								if($result === false) {
									Mage::getSingleton('adminhtml/session')->addError("Mailplatform - Error Performing the Request");
								}
								else {
									$xml_doc = simplexml_load_string($result);
									//Zend_Debug::dump($result);
									Mage::getSingleton('adminhtml/session')->addSuccess("Mailplatform - " . $custom["EMAIL"] . " OK");
									if ($xml_doc->status == 'SUCCESS') {
										//echo 'Data is ', $xml_doc->data, '<br/>';
									} else {
										Mage::getSingleton('adminhtml/session')->addError("Mailplatform -" . $xml_doc->errormessage . '<br/>');
									}
								}
							}
							//echo "more then 1";
						}

					}
				Mage::app()->setCurrentStore(0);
			} catch ( exception $e ) {
				Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
			}
	 	}
	}

	public function getCustomerOldEmail()
    {
        if(isset($_SESSION['customer_old_email']))
   		 {
            $customer_old_email = $_SESSION['customer_old_email'];
            return $customer_old_email;
	    }else
	    {
            return '';

	    }

    }
}
?>
