<?php

class Mp_Mailplatformconnector_Model_Request extends Varien_Object
{

    protected $_xml;

    protected $_mailingList;

    protected $_format;

    protected $_responseXml;

    protected $_requestType;

    const STATUS_SUCCESS = 'SUCCESS';

    const STATUS_FAILED = 'FAILED';

    protected function _construct()
    {
        parent::_construct();

        $this->_initXml();
    }

    protected function _initXml()
    {
        $this->_xml = new SimpleXMLElement('<xmlrequest/>');

        $this->_xml->addChild('username', $this->_getUsername());
        $this->_xml->addChild('usertoken', $this->_getToken());

        $this->setFormat('html');

        $this->setUrl(Mage::helper('mailplatform')->getUrl());

        if ($this->_requestType) {
            $this->_setRequestType($this->_requestType);
        }
    }

    protected function _getUsername()
    {
        return Mage::helper('mailplatform')->getUsername();
    }

    protected function _getToken()
    {
        return Mage::helper('mailplatform')->getToken();
    }

    protected function _getMailingList()
    {
        if ($this->_mailingList) {
            return $this->_mailingList;
        }

        return Mage::helper('mailplatform')->getListId();
    }

    protected function _getFormat()
    {
        if ($this->_format) {
            return $this->_format;
        }

        return Mage::helper('mailplatform')->getFormat();
    }

    public function setFormat($format = null)
    {
        if ($format) {
            $this->_format = $format;
        } else {
            $this->_format = $this->_getFormat();
        }

        return $this;
    }

    public function setMailingList($mailingList = null)
    {
        if ($mailingList) {
            $this->_mailingList = $mailingList;
        } else {
            $this->_mailingList = $this->_getMailingList();
        }

        return $this;
    }

    public function setRequestType($type)
    {
        $this->_requestType = $type;

        $this->_setRequestType($type);

        return $this;
    }

    protected function _setRequestType($type)
    {
        $requestType = $this->_xml->requesttype;

        if (! $requestType) {
            $this->_xml->addChild('requesttype', $type);
        } else {
            $requestType = $type;
        }

        return $this;
    }

    public function setRequestMethod($method)
    {
        $this->_xml->addChild('requestmethod', $method);

        return $this;
    }

    public function addDetails(array $detailsArray = array())
    {
        $details = $this->_xml->details;

        if (! $details) {
            $details = $this->_xml->addChild('details');
        }

        foreach ($detailsArray as $label => $detail) {
            $details->addChild($label, $detail);
        }

        return $this;
    }

    public function addCustomfields(array $data = array())
    {
        if (empty($data)) {
            return $this;
        }

        $details = $this->_xml->details;

        if (! $details) {
            Mage::throwException('XML tree don\'t have details branch.');
        }

        $customfields = $details->customfields;

        if (! $customfields) {
            $customfields = $this->_xml->details->addChild('customfields');
        }

        foreach ($data as $field) {
            $item = $customfields->addChild('item');
            $item->addChild('fieldid', $field['fieldid']);

            if (is_array($field['value'])) {
                $value = $item->addChild('value');
                foreach ($field['value'] as $key => $val) {
                    $value->addChild($key, self::xmlEscape($val));
                }
            } else {
                $item->addChild('value', self::xmlEscape($field['value']));
            }

            if (isset($field['strategy'])) {
                $item->addChild('strategy', $field['strategy']);
            }
        }

        return $this;
    }

    public function getReponseXml()
    {
        return $this->_responseXml;
    }

    public function send()
    {
        $ch = curl_init($this->getUrl());

        $xml = $this->_xml->asXml();

        $this->_initXml();

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);

        $result = @curl_exec($ch);

        if ($result === false) {
            $this->logError('Curl return null: ' . (string) $xml);
            return null;
        }

        $this->_responseXml = new SimpleXMLElement($result);

        if ((string) $this->_responseXml->status === self::STATUS_SUCCESS) {
            $this->setResponseData($this->_responseXml->data);
            $this->logSuccess((string) $this->_responseXml->data);
            return true;
        } elseif ((string) $this->_responseXml->status === self::STATUS_FAILED) {
            $this->setErrorMessage((string) $this->_responseXml->errormessage);
            $this->logError((string) $this->_responseXml->errormessage);
            return false;
        }

        $this->logError('Unknown status: ' . $this->_responseXml->status);

        return null;
    }

    protected function logError($text)
    {
        $helper = Mage::helper('mailplatform');

        if (! $helper->logError()) {
            return;
        }

        $text = $helper->__($text);

        Mage::log($text, Zend_Log::ERR, $helper->getLogFileName(), true);

        return $this;
    }

    protected function logSuccess($text)
    {
        $helper = Mage::helper('mailplatform');

        if (! $helper->logSuccess()) {
            return;
        }

        $text = $helper->__($text);

        Mage::log($text, Zend_Log::INFO, $helper->getLogFileName(), true);

        return $this;
    }

    static public function xmlEscape($string)
    {
        return str_replace(array('&', '<', '>', '\'', '"'), array('&amp;', '&lt;', '&gt;', '&apos;', '&quot;'), $string);
    }

}