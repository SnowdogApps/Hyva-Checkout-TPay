<?php

namespace Snowdog\Hyva\Checkout\TPay\Payment\Method;

use Hyva\Checkout\Model\Magewire\Component\EvaluationInterface;
use Hyva\Checkout\Model\Magewire\Component\EvaluationResultFactory;
use Hyva\Checkout\Model\Magewire\Component\EvaluationResultInterface;
use Magento\Checkout\Model\Session as SessionCheckout;
use Magento\Quote\Api\CartRepositoryInterface;
use Magewirephp\Magewire\Component;
use Tpay\Magento2\Provider\ConfigurationProvider;

class TPayGeneric extends Component implements EvaluationInterface
{
    public bool $acceptTos = false;

    public function __construct(
        private readonly SessionCheckout         $sessionCheckout,
        private readonly CartRepositoryInterface $quoteRepository,
        private readonly ConfigurationProvider   $tPayConfigProvider,
    ) {
    }

    public function mount(): void
    {
        $data = $this->sessionCheckout->getQuote()->getPayment()->getAdditionalInformation();
        $this->acceptTos = $data['accept_tos'] ?? false;
    }

    public function updated($value, $name)
    {
        $quote = $this->sessionCheckout->getQuote();
        $quote->getPayment()->setAdditionalInformation('group', null);
        $quote->getPayment()->setAdditionalInformation('accept_tos', $this->acceptTos);
        $this->quoteRepository->save($quote);

        return $value;
    }

    public function getTerms()
    {
        return $this->tPayConfigProvider->getTermsURL();
    }

    public function evaluateCompletion(EvaluationResultFactory $resultFactory): EvaluationResultInterface
    {
        if (!preg_match('/^generic-[0-9]+$/', $this->sessionCheckout->getQuote()->getPayment()->getMethod())) {
            return $resultFactory->createSuccess();
        }

        if (!$this->acceptTos) {
            return $resultFactory->createErrorMessageEvent(__('TOS not accepted'))
                ->withCustomEvent('payment:method:error');
        }

        return $resultFactory->createSuccess();
    }
}
