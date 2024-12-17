<?php

namespace Snowdog\Hyva\Checkout\TPay\Payment\Method;

use Hyva\Checkout\Model\Magewire\Component\EvaluationInterface;
use Hyva\Checkout\Model\Magewire\Component\EvaluationResultFactory;
use Hyva\Checkout\Model\Magewire\Component\EvaluationResultInterface;
use Magento\Checkout\Model\Session as SessionCheckout;
use Magento\Framework\App\Cache;
use Magento\Quote\Api\CartRepositoryInterface;
use Magewirephp\Magewire\Component;
use Tpay\Magento2\Provider\ConfigurationProvider;

class TPay extends Component implements EvaluationInterface
{
    private const CACHE_KEY = 'hyva_tpay_channels';

    public int $group = 0;

    public string $blikCode = '';

    public bool $acceptTos = false;

    public function __construct(
        private readonly SessionCheckout         $sessionCheckout,
        private readonly CartRepositoryInterface $quoteRepository,
        private readonly ConfigurationProvider   $tPayConfigProvider,
        private readonly Cache                   $cache
    ) {
    }

    public function mount(): void
    {
        $data = $this->sessionCheckout->getQuote()->getPayment()->getAdditionalInformation();
        $this->group = (int) ($data['group'] ?? 0);
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

    public function getTerms()
    {
        return $this->tPayConfigProvider->getTermsURL();
    }

    public function getChannels()
    {
        $cached = $this->cache->load(self::CACHE_KEY);
        if ($cached) {
            $cached = json_decode($cached, true);
            if ($cached) {
                return $cached;
            }
        }

        $merchantId = $this->tPayConfigProvider->getMerchantId();
        $online = $this->tPayConfigProvider->onlyOnlineChannels();
        $isSandbox = $this->tPayConfigProvider->useSandboxMode();
        $host = $isSandbox ? 'https://secure.sandbox.tpay.com/' : 'https://secure.tpay.com/';
        $url = $host . 'groups-' . $merchantId . ($online ? '1' : '0') . '.js?json';
        $json = @file_get_contents($url);
        $data = json_decode($json, true);
        if (empty($data)) {
            return [];
        }
        $this->cache->save($json, self::CACHE_KEY);

        return $data;
    }

    public function evaluateCompletion(EvaluationResultFactory $resultFactory): EvaluationResultInterface
    {
        if ($this->sessionCheckout->getQuote()->getPayment()->getMethod() != 'Tpay_Magento2') {
            return $resultFactory->createSuccess();
        }

        if (!empty($this->blikCode) && strlen($this->blikCode) != 6) {
            $errorMessageEvent = $resultFactory->createErrorMessageEvent(__('Invalid BLIK code'))
                ->withCustomEvent('payment:method:error');
            return $resultFactory->createValidation('validateTPayBlikCode')->withFailureResult($errorMessageEvent);
        }

        if (empty($this->blikCode) && $this->group < 1) {
            $errorMessageEvent = $resultFactory->createErrorMessageEvent(__('Payment method not selected'))
                ->withCustomEvent('payment:method:error');
            return $resultFactory->createValidation('validateTPayMethodSelection')->withFailureResult($errorMessageEvent);
        }

        if (!$this->acceptTos) {
            $errorMessageEvent = $resultFactory->createErrorMessageEvent(__('TOS not accepted'))
                ->withCustomEvent('payment:method:error');
            return $resultFactory->createValidation('validateTPayTOS')->withFailureResult($errorMessageEvent);
        }

        return $resultFactory->createSuccess();
    }
}
