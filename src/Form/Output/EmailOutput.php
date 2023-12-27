<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Form\Output;

use Pimcore\Mail;
use Pimcore\Model\Document;
use Valantic\PimcoreFormsBundle\Model\OutputResponse;

class EmailOutput extends AbstractOutput
{
    public static function name(): string
    {
        return 'email';
    }

    public function handle(OutputResponse $outputResponse): OutputResponse
    {
        $mail = new Mail();
        $mail->addTo($this->getTo());
        $mail->setDocument($this->getDocument());
        $mail->setParams(array_merge($this->form->getData(), $this->getAdditionalParams()));

        $subject = $this->getSubject();

        if ($subject !== null) {
            $mail->subject($subject);
        }

        $from = $this->getFrom();

        if ($from !== null) {
            $mail->addFrom($from);
        }

        $mail = $this->preSend($mail);

        $mail->send();

        return $outputResponse->addStatus(true);
    }

    protected function getTo(): string
    {
        return $this->config['to'];
    }

    /**
     * @return Document|int|string
     */
    protected function getDocument(): int|Document|string
    {
        return $this->config['document'];
    }

    protected function getSubject(): ?string
    {
        return null;
    }

    protected function getFrom(): ?string
    {
        return null;
    }

    /**
     * @return array<string,mixed>
     */
    protected function getAdditionalParams(): array
    {
        return [];
    }

    protected function preSend(Mail $mail): Mail
    {
        return $mail;
    }
}
