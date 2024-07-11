<?php

declare(strict_types=1);


namespace BitBag\SyliusAdyenPlugin\Resolver\Currency;


use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Currency\Context\CurrencyNotFoundException;

class PaymentCurrencyResolver
{
    private ?string $configCurrencyCode;

    public function __construct(private ChannelContextInterface $channelContext, string|null $configCurrencyCode = null)
    {
        $this->configCurrencyCode = $configCurrencyCode;
    }

    public function resolve(string $currencyCode): string
    {
        if (null === $this->configCurrencyCode) {
            return $currencyCode;
        }

        $currencies = $this->channelContext->getChannel()->getCurrencies()->toArray();
        if (!in_array($this->configCurrencyCode, $currencies)) {
            throw CurrencyNotFoundException::notAvailable($this->configCurrencyCode, $currencies);
        }

        return $this->configCurrencyCode;
    }
}
