<?php

class Mp_Mailplatformconnector_Model_Lists extends Mp_Mailplatformconnector_Model_Request
{

    protected function _construct()
    {
        parent::_construct();

        $this->setRequestType('lists');
    }

    public function getLists()
    {
        $this->setRequestMethod('GetLists');

        return $this->send();
    }

}