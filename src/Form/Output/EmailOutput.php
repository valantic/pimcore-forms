<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Form\Output;

use Pimcore\Mail;
use Pimcore\Model\Document;

class EmailOutput extends AbstractOutput
{
    public static function name(): string
    {
        return 'email';
    }

    public function handle(): bool
    {
        $mail = new Mail();
        $mail->addTo($this->getTo());
        $mail->setDocument($this->getDocument());
        $mail->setParams(array_merge($this->form->getData(), $this->getAdditionalParams()));

        $subject = $this->getSubject();

        if ($subject !== null) {
            $mail->setSubject($subject);
        }

        $from = $this->getFrom();

        if ($from !== null) {
            $mail->setFrom($from);
        }

        $mail->send();

        return true;
    }

    protected function getTo(): string
    {
        return $this->config['to'];
    }

    /**
     * @return Document|int|string
     */
    protected function getDocument()
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
}
