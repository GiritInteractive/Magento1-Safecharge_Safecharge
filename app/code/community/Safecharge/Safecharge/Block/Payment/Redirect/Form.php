<?php

/**
 * Safecharge Safecharge payment authenticate form block.
 *
 * @category Safecharge
 * @package  Safecharge_Safecharge
 */
class Safecharge_Safecharge_Block_Payment_Redirect_Form
    extends Mage_Core_Block_Template
{

    /**
     * @return string
     * @throws Varien_Exception
     */
    protected function _toHtml()
    {
        return parent::_toHtml();
    }



    public function getInputs(){
      $html = '';
      $queryParams = Mage::helper('safecharge_safecharge/urlBuilder')->getQueryParams();

      $entries = explode('&', http_build_query($queryParams));
      foreach ($entries as $entry) {
        list($key, $value) = explode('=', $entry);
        $html .= "<input type='hidden' id='".urldecode($key)."' name='".urldecode($key)."' value='".urldecode($value)."'>";
      }

      return $html;
    }

    public function getUrl(){
      return Mage::helper('safecharge_safecharge/urlBuilder')->getEndpoint();
    }
}
