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
   * @return PaymentApm
   */
  public function process()
  {
      parent::process();

      $body = $this->curl->getBody();
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
      return array(
          'redirectURL',
          'status',
      );
  }
}
