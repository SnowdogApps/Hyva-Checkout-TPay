<?php

namespace Snowdog\Hyva\Checkout\TPay\Override\Payment\Method;

use Magento\Framework\DataObject;
use tpaycom\magento2basic\Model\Tpay as BaseMethod;

class TPay extends BaseMethod
{
    public function assignData(DataObject $data)
    {
        $additionalData = $data->getData('additional_data');
        if (isset($additionalData['additional_information'])) {
            $additionalData = $additionalData['additional_information'];
        }

        $info = $this->getInfoInstance();

        $info->setAdditionalInformation(
            static::CHANNEL,
            isset($additionalData[static::CHANNEL]) ? $additionalData[static::CHANNEL] : ''
        );

        $info->setAdditionalInformation(
            static::BLIK_CODE,
            isset($additionalData[static::BLIK_CODE]) ? $additionalData[static::BLIK_CODE] : ''
        );

        if (isset($additionalData[static::TERMS_ACCEPT]) && $additionalData[static::TERMS_ACCEPT] === 1) {
            $info->setAdditionalInformation(
                static::TERMS_ACCEPT,
                1
            );
        }

        return $this;
    }
}
