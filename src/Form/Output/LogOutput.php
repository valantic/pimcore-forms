<?php

namespace Valantic\PimcoreFormsBundle\Form\Output;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class LogOutput extends AbstractOutput implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public static function name(): string
    {
        return 'log';
    }

    public function handle(): bool
    {
        $this->logger->log($this->config['level'] ?? 'debug', $this->form->getName(), $this->form->getData());

        return true;
    }
}
