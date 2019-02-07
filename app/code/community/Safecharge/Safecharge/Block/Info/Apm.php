<?php

/**
 * Safecharge Safecharge info block.
 *
 * @category Safecharge
 * @package  Safecharge_Safecharge
 */
class Safecharge_Safecharge_Block_Info_Apm extends Mage_Payment_Block_Info
{

  protected function _prepareSpecificInformation($transport = null)
  {
    if (null !== $this->_paymentSpecificInformation)
    {
      return $this->_paymentSpecificInformation;
    }


    $data = array();
    $additionalInformation = $this->getInfo()->getAdditionalInformation();

      foreach ($additionalInformation as $field => $value) {
          if (in_array($field, $this->hiddenFields, true)) {
              unset($data[$field]);
              continue;
          }
          $data['Payment Method Code'] = $value;
      }
    $transport = parent::_prepareSpecificInformation($transport);

    return $transport->setData(array_merge($data, $transport->getData()));
  }

}
