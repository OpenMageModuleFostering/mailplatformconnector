<?php

class Mp_Mailplatformconnector_Block_Adminhtml_Newsletter_Subscriber extends Mage_Adminhtml_Block_Newsletter_Subscriber
{

    public function __construct()
    {
        $this->setTemplate('newsletter/subscriber/list_mailplatform.phtml');
    }

    public function getmailplatformSyn()
    {
        return $this->getUrl('adminhtml/mailplatform/index');
    }
}