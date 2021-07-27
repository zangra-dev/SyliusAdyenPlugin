<?php

declare(strict_types=1);

namespace BitBag\SyliusAdyenPlugin\Provider;

use BitBag\SyliusAdyenPlugin\Client\AdyenClient;
use BitBag\SyliusAdyenPlugin\Client\AdyenClientInterface;
use BitBag\SyliusAdyenPlugin\Client\AdyenTransportFactory;
use BitBag\SyliusAdyenPlugin\Repository\PaymentMethodRepositoryInterface;
use BitBag\SyliusAdyenPlugin\Resolver\Version\VersionResolver;
use BitBag\SyliusAdyenPlugin\Traits\GatewayConfigFromPaymentTrait;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Resource\Exception\UpdateHandlingException;

class AdyenClientProvider
{
    public const FACTORY_NAME = 'adyen';

    use GatewayConfigFromPaymentTrait;

    /** @var PaymentMethodRepositoryInterface */
    private $paymentMethodRepository;

    /** @var ChannelContextInterface */
    private $channelContext;

    /** @var AdyenTransportFactory */
    private $adyenTransportFactory;

    /** @var VersionResolver */
    private $versionResolver;

    public function __construct(
        PaymentMethodRepositoryInterface $paymentMethodRepository,
        ChannelContextInterface $channelContext,
        AdyenTransportFactory $adyenTransportFactory,
        VersionResolver $versionResolver
    ) {
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->channelContext = $channelContext;
        $this->adyenTransportFactory = $adyenTransportFactory;
        $this->versionResolver = $versionResolver;
    }

    public function getDefaultClient(): AdyenClient
    {
        $paymentMethod = $this->paymentMethodRepository->findOneByChannel(
            $this->channelContext->getChannel()
        );

        if (null === $paymentMethod) {
            throw new UpdateHandlingException(sprintf('No Adyen provider is configured'));
        }

        $config = $this->getGatewayConfig($paymentMethod)->getConfig();

        return new AdyenClient($config, $this->adyenTransportFactory, $this->versionResolver);
    }

    public function getForPaymentMethod(PaymentMethodInterface $paymentMethod): AdyenClientInterface
    {
        $gatewayConfig = $this->getGatewayConfig($paymentMethod);
        $isAdyen = isset($gatewayConfig->getConfig()[self::FACTORY_NAME]);
        if (!$isAdyen) {
            throw new \InvalidArgumentException(sprintf(
                'Provided PaymentMethod #%d is not an Adyen instance',
                (int) $paymentMethod->getId()
            ));
        }

        return new AdyenClient($gatewayConfig->getConfig(), $this->adyenTransportFactory, $this->versionResolver);
    }

    public function getClientForCode(string $code): AdyenClientInterface
    {
        $paymentMethod = $this->paymentMethodRepository->findOneForAdyenAndCode($code);

        if ($paymentMethod === null) {
            throw new \InvalidArgumentException(sprintf('Adyen for "%s" code is not configured', $code));
        }

        return $this->getForPaymentMethod($paymentMethod);
    }
}
