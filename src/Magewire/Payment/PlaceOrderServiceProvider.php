<?php

namespace Snowdog\Hyva\Checkout\TPay\Magewire\Payment;

use Hyva\Checkout\Model\Magewire\Payment\AbstractPlaceOrderService;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Model\Quote;
use tpaycom\magento2basic\Model\Tpay;

class PlaceOrderServiceProvider extends AbstractPlaceOrderService
{
    /**
     * @var Tpay
     */
    private $tpay;

    public function __construct(CartManagementInterface $cartManagement, Tpay $tpay)
    {
        parent::__construct($cartManagement);
        $this->tpay = $tpay;
    }

    public function getRedirectUrl(Quote $quote, ?int $orderId = null): string
    {
        return $this->tpay->getPaymentRedirectUrl();
    }
}