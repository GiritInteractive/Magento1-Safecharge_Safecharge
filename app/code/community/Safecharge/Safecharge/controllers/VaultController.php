<?php

/**
 * Safecharge Safecharge vault controller.
 *
 * @category Safecharge
 * @package  Safecharge_Safecharge
 */
class Safecharge_Safecharge_VaultController extends Mage_Core_Controller_Front_Action
{
    /**
     * @return void
     * @throws Varien_Exception
     */
    public function preDispatch()
    {
        parent::preDispatch();

        $loginUrl = Mage::helper('customer')->getLoginUrl();

        $authenticateResult = Mage::getSingleton('customer/session')
            ->authenticate($this, $loginUrl);

        if (!$authenticateResult) {
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);
        }
    }

    /**
     * @return void
     */
    public function cardsAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * @return void
     * @throws Varien_Exception
     */
    public function deleteCardAction()
    {
        $hash = $this->getRequest()->getParam('id');

        $paymentToken = Mage::getModel('safecharge_safecharge/vault')
            ->load($hash, 'public_hash');

        $customerId = (int)Mage::getSingleton('customer/session')->getCustomerId();

        if ($paymentToken->getId() && (int)$paymentToken->getCustomerId() === $customerId) {
            $paymentToken->delete();

            Mage::getSingleton('core/session')
                ->addSuccess(__('Credit card has been removed.'));
        }


        $this->_redirect('safecharge/vault/cards');
    }
}
