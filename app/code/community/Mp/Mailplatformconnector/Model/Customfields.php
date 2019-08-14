<?php

class Mp_Mailplatformconnector_Model_Customfields extends Mp_Mailplatformconnector_Model_Request
{
    protected function _construct()
    {
        parent::_construct();

        $this->setRequestType('Customfields');
    }

    public function update($type, $fieldId, $values)
    {
        $this->setRequestMethod('Update');

        switch ($type) {
            case 'checkbox':
                $response = $this->_updateCheckbox($fieldId, $values);
                break;
            default:
                $response = false;
        }

        return $response;
    }

    protected function _updateCheckbox($fieldId, array $data)
    {
        if (empty($data)) {
            return $this;
        }

        if (! isset($data['keys']) || ! isset($data['values'])) {
            Mage::throwException('Custom field type checkbox need keys and values.');
        }

        $details['fieldid'] = $fieldId;

        $this->addDetails($details);

        $details = $this->_xml->details;

        if (! $details) {
            Mage::throwException('XML tree don\'t have details branch.');
        }

        $settings = $details->addChild('Settings');

        $keys = implode(',', $data['keys']);
        $values = implode(',', $data['values']);

        $settings->addChild('Key', self::xmlEscape($keys));
        $settings->addChild('Value', self::xmlEscape($values));//Can see

        return $this->send();
    }

}