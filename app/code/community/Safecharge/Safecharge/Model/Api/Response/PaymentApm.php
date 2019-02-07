<?php
/**
 * Safecharge Safecharge api token request model.
 *
 * @category Safecharge
 * @package  Safecharge_Safecharge
 */
class Safecharge_Safecharge_Model_Api_Response_PaymentApm
    extends Safecharge_Safecharge_Model_Api_Response_Abstract
{
  /**
   * @var string
   */
  protected $redirectUrl = "";

  /**
   * @var string
   */
  protected $responseStatus = "";

  /**
    * @return string
    */
  protected function getResponseMethod()
  {
      return self::PAYMENT_APM_METHOD;
  }

  /**
   * @return PaymentApm
   */
  public function process()
  {
      $body = $this->curl->getBody();
      var_dump($body);
      $this->redirectUrl = (string) $body['redirectURL'];
      $this->responseStatus = (string) $body['status'];

      return $this;
  }

  /**
   * @return string
   */
  public function getRedirectUrl()
  {
      return $this->redirectUrl;
  }

  /**
   * @return string
   */
  public function getResponseStatus()
  {
      return $this->responseStatus;
  }

  /**
   * @return array
   */
  protected function getRequiredResponseDataKeys()
  {
    return array_merge_recursive(
        parent::getRequiredResponseDataKeys(),
        array(
          'redirectURL',
          'status',
        )
    );
  }
}
