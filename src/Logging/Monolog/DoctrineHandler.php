<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\SyliusAdyenPlugin\Logging\Monolog;

use BitBag\SyliusAdyenPlugin\Factory\LogFactoryInterface;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;

final class DoctrineHandler extends AbstractProcessingHandler
{
    /** @var LogFactoryInterface */
    private $logFactory;

    /** @var RepositoryInterface */
    private $repository;

    /** @var RequestStack  */
    private $requestStack;

    public function __construct(
        LogFactoryInterface $logFactory,
        RepositoryInterface $repository,
        RequestStack $requestStack,
    ) {
        $this->logFactory = $logFactory;
        $this->repository = $repository;
        $this->requestStack = $requestStack;

        parent::__construct();
    }

    protected function write(array|LogRecord $record): void
    {
        $log = $this->logFactory->create($record['message'], $record['level'], 0, $this->addSessionToken());

        $this->repository->add($log);
    }

    private function addSessionToken(): string
    {
        try {
            $session = $this->requestStack->getSession();
        } catch (SessionNotFoundException $e) {
            return '';
        }
        if (!$session->isStarted()) {
            return '';
        }

        $sessionId = substr($session->getId(), 0, 8) ?: '????????';
        $sessionId = $sessionId . '-' . substr(uniqid('', true), -8);

        return $sessionId;
    }

}
