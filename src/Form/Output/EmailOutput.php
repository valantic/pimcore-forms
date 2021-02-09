<?php

namespace Valantic\PimcoreFormsBundle\Form\Output;

use Pimcore\Mail;

class EmailOutput extends AbstractOutput
{
    public static function name(): string
    {
        return 'email';
    }

    public function handle(): bool
    {
        $mail = new Mail();
        $mail->addTo($this->config['to']);
        $mail->setDocument($this->config['document']);
        $mail->setParams($this->form->getData());
        $mail->send();

        return true;
    }
}
