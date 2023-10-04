<?php

namespace Snowdog\Hyva\Checkout\TPay\Payment\Method;

use Magento\Checkout\Model\Session as SessionCheckout;
use Magento\Quote\Api\CartRepositoryInterface;
use Magewirephp\Magewire\Component;
use tpaycom\magento2basic\Model\TpayConfigProvider;

class TPay extends Component
{
    public ?string $group = '';

    public ?string $blikCode = '';

    public ?bool $acceptTos = false;

    public function __construct(
        private readonly SessionCheckout         $sessionCheckout,
        private readonly CartRepositoryInterface $quoteRepository,
        private readonly TpayConfigProvider      $tpayConfigProvider,
    ) {
    }

    public function mount(): void
    {
        $data = $this->sessionCheckout->getQuote()->getPayment()->getAdditionalInformation();
        $this->group = $data['group'] ?? '';
        $this->blikCode = $data['blik_code'] ?? '';
        $this->acceptTos = $data['accept_tos'] ?? false;
    }

    public function updated($value, $name)
    {
        $quote = $this->sessionCheckout->getQuote();
        $quote->getPayment()->setAdditionalInformation('group', $this->group);
        $quote->getPayment()->setAdditionalInformation('blik_code', $this->blikCode);
        $quote->getPayment()->setAdditionalInformation('accept_tos', $this->acceptTos);
        $this->quoteRepository->save($quote);

        return $value;
    }

    public function addCss()
    {
        return $this->tpayConfigProvider->createCSS('tpaycom_magento2basic::css/tpay.css');
    }

    public function getTerms()
    {
        return $this->tpayConfigProvider->getTerms();
    }


}