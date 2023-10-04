<?php

namespace Snowdog\Hyva\Checkout\TPay\Magewire\Payment;

use Hyva\Checkout\Model\Magewire\Payment\AbstractPlaceOrderService;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Model\Quote;
use tpaycom\magento2basic\Model\Tpay;

class PlaceOrderServiceProvider extends AbstractPlaceOrderService
{
    public function __construct(
        CartManagementInterface          $cartManagement,
        private readonly Tpay            $tPay,
    ) {
        parent::__construct($cartManagement);
    }


    public function getRedirectUrl(Quote $quote, ?int $orderId = null): string
    {
        return $this->tPay->getPaymentRedirectUrl();
    }
}