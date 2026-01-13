<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Form\Output;

use Valantic\PimcoreFormsBundle\Model\OutputResponse;

class HttpOutput extends AbstractOutput
{
    public static function name(): string
    {
        return 'http';
    }

    public function handle(OutputResponse $outputResponse): OutputResponse
    {
        $ch = curl_init($this->config['url']);

        if ($ch === false) {
            throw new \RuntimeException(sprintf('Failed to initialize curl for %s', $this->config['url']));
        }

        curl_setopt($ch, \CURLOPT_POSTFIELDS, json_encode($this->form->getData(), \JSON_THROW_ON_ERROR));
        curl_setopt($ch, \CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
        curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
        $status = curl_exec($ch);
        curl_close($ch);

        return $outputResponse->addStatus($status !== false);
    }
}
