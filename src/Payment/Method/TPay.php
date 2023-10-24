<?php

namespace Snowdog\Hyva\Checkout\TPay\Payment\Method;

use Hyva\Checkout\Model\Magewire\Component\EvaluationInterface;
use Hyva\Checkout\Model\Magewire\Component\EvaluationResultFactory;
use Hyva\Checkout\Model\Magewire\Component\EvaluationResultInterface;
use Magento\Checkout\Model\Session as SessionCheckout;
use Magento\Framework\App\Cache;
use Magento\Quote\Api\CartRepositoryInterface;
use Magewirephp\Magewire\Component;
use tpaycom\magento2basic\Model\TpayConfigProvider;

class TPay extends Component implements EvaluationInterface
{
    private const CACHE_KEY = 'tpay_channels';

    public int $group = 0;

    public string $blikCode = '';

    public bool $acceptTos = false;

    public function __construct(
        private readonly SessionCheckout         $sessionCheckout,
        private readonly CartRepositoryInterface $quoteRepository,
        private readonly TpayConfigProvider      $tPayConfigProvider,
        private readonly Cache                   $cache
    ) {
    }

    public function mount(): void
    {
        $data = $this->sessionCheckout->getQuote()->getPayment()->getAdditionalInformation();
        $this->group = (int) $data['group'] ?? 0;
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
        return $this->tPayConfigProvider->getTerms();
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
        $config = $this->tPayConfigProvider->getConfig();
        $merchantId = $config['tpay']['payment']['merchantId'];
        $online = $config['tpay']['payment']['onlyOnlineChannels'];
        $url = 'https://secure.tpay.com/groups-' . $merchantId . ($online ? '1' : '0') . '.js?json';
        $data = file_get_contents($url);
        $this->cache->save($data, self::CACHE_KEY);

        return json_decode($data, true);
    }

    public function evaluateCompletion(EvaluationResultFactory $resultFactory): EvaluationResultInterface
    {
        if ($this->sessionCheckout->getQuote()->getPayment()->getMethod() != 'tpaycom_magento2basic') {
            return $resultFactory->createSuccess();
        }

        if (!empty($this->blikCode) && strlen($this->blikCode) != 6) {
            return $resultFactory->createBlocking(__('Invalid BLIK code'));
        }

        if (empty($this->blikCode) && $this->group < 1) {
            return $resultFactory->createBlocking(__('Payment method not selected'));
        }

        if (!$this->acceptTos) {
            return $resultFactory->createBlocking(__('TOS not accepted'));
        }

        return $resultFactory->createSuccess();
    }
}
