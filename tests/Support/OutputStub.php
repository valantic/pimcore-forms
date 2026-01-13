<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Support;

use Symfony\Component\Form\FormInterface;
use Valantic\PimcoreFormsBundle\Constant\MessageConstants;
use Valantic\PimcoreFormsBundle\Form\Output\OutputInterface;
use Valantic\PimcoreFormsBundle\Model\Message;
use Valantic\PimcoreFormsBundle\Model\OutputResponse;

class OutputStub implements OutputInterface
{
    public static string $staticName = 'test_output';
    private bool $shouldFail = false;
    private string $key = '';
    private array $outputHandlers = [];

    public function __construct(?string $outputName = null)
    {
        if ($outputName !== null) {
            self::$staticName = $outputName;
        }
    }

    public static function name(): string
    {
        return self::$staticName;
    }

    public function initialize(string $key, FormInterface $form, array $config): void
    {
        $this->key = $key;
    }

    public function setOutputHandlers(array $handlers): void
    {
        $this->outputHandlers = $handlers;
    }

    public function getOutputHandlers(): array
    {
        return $this->outputHandlers;
    }

    public function setShouldFail(bool $shouldFail): void
    {
        $this->shouldFail = $shouldFail;
    }

    public function handle(OutputResponse $outputResponse): OutputResponse
    {
        if ($this->shouldFail) {
            $message = (new Message())
                ->setType(MessageConstants::MESSAGE_TYPE_ERROR)
                ->setMessage($this->key . ' - failure')
            ;
            $outputResponse->addMessage($message);
            $outputResponse->addStatus(false);
        } else {
            $message = (new Message())
                ->setType(MessageConstants::MESSAGE_TYPE_SUCCESS)
                ->setMessage($this->key . ' - success')
            ;
            $outputResponse->addMessage($message);
            $outputResponse->addStatus(true);
        }

        return $outputResponse;
    }
}
