<?php
require_once (Mage::getModuleDir('controllers', 'Mage_Customer') . DS . 'AccountController.php');

class Mp_Mailplatform_AccountController extends Mage_Customer_AccountController
{

    public function editAction()
    {
        $this->loadLayout(array(
            'default',
            'customer_account_edit'
        ));

        $this->_initLayoutMessages('customer/session');
        $this->_initLayoutMessages('catalog/session');

        if ($block = $this->getLayout()->getBlock('customer_edit')) {
            $block->setRefererUrl($this->_getRefererUrl());
        }

        $data = $this->_getSession()->getCustomerFormData(true);
        $customer = $this->_getSession()->getCustomer();
        if (! empty($data)) {
            $customer->addData($data);
        }

        if ($this->getRequest()->getParam('changepass') == 1) {
            $customer->setChangePassword(1);
        }

        Mage::getSingleton('core/session', array('name' => 'frontend'))
            ->setData('customer_old_email', $customer->getEmail())
        ;

        $this->getLayout()
            ->getBlock('head')
            ->setTitle($this->__('Account Information'))
        ;

        $this->renderLayout();
    }

    public function confirmAction()
    {
        if ($this->_getSession()->isLoggedIn()) {
            $this->_redirect('*/*/');
            return;
        }

        try {
            $id = $this->getRequest()->getParam('id', false);
            $key = $this->getRequest()->getParam('key', false);
            $backUrl = $this->getRequest()->getParam('back_url', false);
            if (empty($id) || empty($key)) {
                throw new Exception($this->__('Bad request.'));
            }

            // load customer by id (try/catch in case if it throws exceptions)
            try {
                $customer = Mage::getModel('customer/customer')->load($id);
                if ((! $customer) || (! $customer->getId())) {
                    throw new Exception('Failed to load customer by id.');
                }
            } catch (Exception $e) {
                throw new Exception($this->__('Wrong customer account specified.'));
            }

            // check if it is inactive
            if ($customer->getConfirmation()) {
                if ($customer->getConfirmation() !== $key) {
                    throw new Exception($this->__('Wrong confirmation key.'));
                }

                // activate customer
                try {
                    $customer->setConfirmation(null);
                    $customer->save();

                    $subscriber = Mage::getModel('newsletter/subscriber')->loadByEmail($customer->getEmail());

                    if ($subscriber->isSubscribed() && Mage::getStoreConfig('mailplatform/general/active')) {
                        Mage::getSingleton('mailplatform/mailplatform')->subscribe($customer);
                    }
                } catch (Exception $e) {
                    throw new Exception($this->__('Failed to confirm customer account.'));
                }

                // log in and send greeting email, then die happy
                $this->_getSession()->setCustomerAsLoggedIn($customer);
                $successUrl = $this->_welcomeCustomer($customer, true);
                $this->_redirectSuccess($backUrl ? $backUrl : $successUrl);
                return;
            }

            // die happy
            $this->_redirectSuccess(Mage::getUrl('*/*/index', array('_secure' => true)));
            return;
        } catch (Exception $e) {
            // die unhappy
            $this->_getSession()->addError($e->getMessage());
            $this->_redirectError(Mage::getUrl('*/*/index', array('_secure' => true)));
            return;
        }
    }
}