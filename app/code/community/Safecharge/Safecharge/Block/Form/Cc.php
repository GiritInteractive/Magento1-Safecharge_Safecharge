<?php

/**
 * Safecharge Safecharge payment block.
 *
 * @category Safecharge
 * @package  Safecharge_Safecharge
 */
class Safecharge_Safecharge_Block_Form_Cc extends Mage_Payment_Block_Form_Cc
{
    /**
     * @var array
     */
    protected $supportedCcTypes = array(
        'VI' => 'Visa',
        'MC' => 'MasterCard',
        'DI' => 'Discover',
        'AE' => 'Amex',
        'SM' => 'Maestro',
        'SO' => 'Solo',
    );

    /**
     *
     */
    protected function _construct()
    {
        parent::_construct();

        $this->setTemplate('safecharge/safecharge/form/cc.phtml');
    }

    /**
     * @return string
     */
    protected function _toHtml()
    {
        Mage::dispatchEvent(
            'payment_form_block_to_html_before',
            array('block' => $this)
        );

        return parent::_toHtml();
    }

    /**
     * @return array
     * @throws Varien_Exception
     */
    public function getCcTokens()
    {
        $customerId = Mage::getSingleton('customer/session')->getCustomerId();
        if (!$customerId) {
            return array();
        }

        $collection = Mage::getModel('safecharge_safecharge/resource_vault_collection')
            ->getCustomerVault((int)$customerId);

        $tokens = array();
        foreach ($collection as $token) {
            $cardDetails = json_decode($token->getTokenDetails(), 1);

            $cardTypeName = isset($this->supportedCcTypes[$cardDetails['cc_type']])
                ? $this->supportedCcTypes[$cardDetails['cc_type']]
                : $cardDetails['cc_type'];

            $cardLabel = sprintf(
                '%s xxxx-%s %s/%s',
                $cardTypeName,
                $cardDetails['cc_last_4'],
                str_pad($cardDetails['cc_exp_month'], 2, 0, STR_PAD_LEFT),
                substr($cardDetails['cc_exp_year'], -2)
            );

            $tokens[$token->getPublicHash()] = array(
                'label' => $cardLabel,
                'type' => $cardDetails['cc_type'],
                'exp_month' => $cardDetails['cc_exp_month'],
                'exp_year' => $cardDetails['cc_exp_year'],
            );
        }

        return $tokens;
    }

    /**
     * @return bool
     */
    public function canUseVault()
    {
        return Mage::helper('safecharge_safecharge/config')->getUseVault();
    }

    /**
     * @return bool
     * @throws Varien_Exception
     */
    protected function isCustomerLoggedIn()
    {
        return Mage::getSingleton('customer/session')->isLoggedIn();
    }

    /**
     * @return bool
     * @throws Varien_Exception
     */
    public function canSaveCard()
    {
        return $this->canUseVault() && $this->isCustomerLoggedIn();
    }

    /**
     * @return bool
     */
    public function getUseCcDetection()
    {
        return Mage::helper('safecharge_safecharge/config')->getUseCcDetection();
    }

    /**
     * @param string $cardType
     *
     * @return string
     */
    public function getCcImage($cardType)
    {
        return $this->getSkinUrl('images/safecharge/' . $cardType . '.png');
    }

    /**
     * @return bool
     */
    public function is3dSecureEnabled()
    {
        return Mage::helper('safecharge_safecharge/config')->is3dSecureEnabled();
    }

    /**
     * @return bool
     */
    public function useExternalSolution()
    {
        $paymentSolution = Mage::helper('safecharge_safecharge/config')->getPaymentSolution();
        $useExternal = $paymentSolution === Safecharge_Safecharge_Model_Safecharge::PAYMENT_SOLUTION_REDIRECT;

        return $useExternal;
    }
}
