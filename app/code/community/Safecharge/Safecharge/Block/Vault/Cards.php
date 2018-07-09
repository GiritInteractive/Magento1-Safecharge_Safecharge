<?php

/**
 * Safecharge Safecharge vault cards block.
 *
 * @category Safecharge
 * @package  Safecharge_Safecharge
 */
class Safecharge_Safecharge_Block_Vault_Cards
    extends Mage_Core_Block_Template
{
    /**
     * @return array
     */
    public function getCcTokens()
    {
        $formBlock = Mage::getBlockSingleton('safecharge_safecharge/form_cc');
        $tokens = $formBlock->getCcTokens();

        foreach ($tokens as &$token) {
            $label = $token['label'];
            $label = explode(' ', $label);
            $token['cc_number'] = $label[1];
            unset($token['label']);

            $token['type_label'] = $label[0];

            $token['type_image'] = $formBlock->getCcImage($token['type']);
        }

        return $tokens;
    }

    /**
     * @param string $tokenHash
     *
     * @return string
     */
    public function getDeleteUrl($tokenHash)
    {
        return $this->getUrl(
            'safecharge/vault/deleteCard',
            array('id' => $tokenHash)
        );
    }
}
