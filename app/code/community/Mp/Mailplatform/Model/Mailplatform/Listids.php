<?php
class Mp_Mailplatform_Model_Mailplatform_Listids extends Varien_Object
{
    protected $_options;
    protected $_lists = array();

	protected function getmodeloptions()
	{
		return Mage::getModel('mailplatform/mailplatform');
	}

    public function toOptionArray($isMultiselect=false)
    {
        if (!$this->_options) {
        	$arrresult = explode("/", $_SERVER["REQUEST_URI"]);
        	//$arrresult;
        	//Zend_Debug::dump($arrresult);
        	$i = 0;
        	$store = "";
        	$storetoload = 0;
        	foreach($arrresult as $arr)
        	{
        	    if($arr == "store")
        		{
        			$store = $arrresult[$i+1];
        		}
        		$i++;
        	}
        	
        	$allstores = Mage::getModel('core/store')->getCollection();
        	
        	foreach($allstores as $actualstore)
        	{
        		if($actualstore->getCode() == $store)
        		{
        			$storetoload = (int)$actualstore->getId();
        		}
        	}
        	
        	Mage::app()->setCurrentStore($storetoload);
			$url2 = Mage::getStoreConfig('mailplatform/general/url');
		
			$url = $this->getmodeloptions()->getXMLGeneralConfig("url" ,1);
			//Zend_Debug::dump($url);
				
			$username = $this->getmodeloptions()->getXMLGeneralConfig("username");
			$tocken = $this->getmodeloptions()->getXMLGeneralConfig("tocken");
			
			
 
				$xml = '<xmlrequest>
				<username>'. trim($username) .'</username>
				<usertoken>'. trim($tocken) .'</usertoken>
				<requesttype>lists</requesttype>
				<requestmethod>GetLists</requestmethod>
				<details>all</details></xmlrequest>';
				//Zend_Debug::dump($xml);
				
				$ch = curl_init($url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
				$result = @curl_exec($ch);
				$xml_doc = simplexml_load_string($result);
				//Zend_Debug::dump($result);
				if($result === false) {
					Mage::getSingleton('adminhtml/session')->addError('Mailplatform Configuration Error!');
				}else {
					if ($xml_doc->status == 'SUCCESS') 
					{
						foreach($xml_doc->data->item as $item)
						{
							if($item->username == $username){
							$this->_lists[] = array('value'=>$item->listid,
												'label'=>$item->name);
							}
						}
					}
				}
				
				$this->_options = $this->_lists;
        }
       

        $options = $this->_options;
        if(!$isMultiselect){
            array_unshift($options, array('value'=>'', 'label'=> Mage::helper('adminhtml')->__('--Please Select--')));
        }
        Mage::app()->setCurrentStore(0);
		return $options;
    }
}