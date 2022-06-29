<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Form\Output;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Valantic\PimcoreFormsBundle\Model\OutputResponse;

class LogOutput extends AbstractOutput implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public static function name(): string
    {
        return 'log';
    }

    public function handle(OutputResponse $outputResponse): OutputResponse
    {
        if (!$this->logger instanceof LoggerInterface) {
            return $outputResponse->addStatus(false);
        }

        $this->logger->log($this->config['level'] ?? 'debug', $this->form->getName(), $this->form->getData());

        return $outputResponse->addStatus(true);
    }
}
