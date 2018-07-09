<?php

/**
 * Safecharge Safecharge payment authenticate form block.
 *
 * @category Safecharge
 * @package  Safecharge_Safecharge
 */
class Safecharge_Safecharge_Block_Payment_Authenticate_Form
    extends Mage_Core_Block_Template
{
    /**
     * @return string|null
     * @throws Varien_Exception
     */
    public function getAscUrl()
    {
        return Mage::getSingleton('checkout/session')->getAscUrl();
    }

    /**
     * @return string|null
     * @throws Varien_Exception
     */
    public function getPaReq()
    {
        return Mage::getSingleton('checkout/session')->getPaReq();
    }

    /**
     * @return string
     * @throws Varien_Exception
     */
    public function getTermUrl()
    {
        $orderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();

        return $this->getUrl(
            'safecharge/payment/update',
            array(
                'order' => $orderId,
                '_secure' => true
            )
        );
    }

    /**
     * @return string
     * @throws Varien_Exception
     */
    protected function _toHtml()
    {
        if ($this->getAscUrl() === null) {
            return '';
        }

        return parent::_toHtml();
    }
}
