<?php
/**
 * Safecharge Safecharge api token request model.
 *
 * @category Safecharge
 * @package  Safecharge_Safecharge
 */
class Safecharge_Safecharge_Model_Api_Response_GetMerchantPaymentMethods
    extends Safecharge_Safecharge_Model_Api_Response_Abstract
{
   /**
    * @var array
    */
  protected $paymentMethods = array();
    /**
     * @return string
     */
  protected function getResponseMethod()
  {
      return self::GET_MERCHANT_PAYMENT_METHODS_METHOD;
  }
  /**
   * @return Safecharge_Safecharge_Model_Api_Response_Payment_GetMerchantPaymentMethods
   */
  protected function process()
  {
      parent::process();

      $body = $this->curl->getBody();
      $this->paymentMethods = (array) $body['paymentMethods'];
      $langCode = $this->getStoreLocale(true);
      foreach ($this->paymentMethods as $k => &$method) {
        if ($this->config->getPaymentAction() === Safecharge_Safecharge_Model_Safecharge::ACTION_AUTHORIZE_CAPTURE && isset($method["paymentMethod"]) && $method["paymentMethod"] !== 'cc_card'){
          unset($this->paymentMethods[$k]);
          continue;
        }
        if (isset($method["paymentMethodDisplayName"]) && is_array($method["paymentMethodDisplayName"])) {
          foreach ($method["paymentMethodDisplayName"] as $kk => $dname) {
            if ($dname["language"] === $langCode) {
              $method["paymentMethodDisplayName"] = $dname;
              break;
            }
          }
          if (!isset($method["paymentMethodDisplayName"]["language"])) {
            unset($this->paymentMethods[$k]);
          }
        }
        if (isset($method["logoURL"]) && $method["logoURL"]) {
          $method["logoURL"] = preg_replace('/\.svg\.svg$/', '.svg', $method["logoURL"]);
        }
      }
      $this->paymentMethods = array_values($this->paymentMethods);
      return $this;
  }
  /**
   * @return string
   */
  public function getPaymentMethods()
  {
      return $this->paymentMethods;
  }
  /**
   * Return store locale.
   *
   * @return string
   */
  protected function getStoreLocale($twoLetters = true)
  {
      $locale = Mage::app()->getLocale()->getLocaleCode();
      return ($twoLetters) ? substr($locale, 0, 2) : $locale;
  }
  /**
   * @return array
   */
  protected function getRequiredResponseDataKeys()
  {
      return array_merge_recursive(
          parent::getRequiredResponseDataKeys(),
          array(
            'paymentMethods',
          )
      );
  }
}
