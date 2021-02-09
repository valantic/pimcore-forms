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
        $mail->addTo($this->getTo());
        $mail->setDocument($this->getDocument());
        $mail->setParams($this->form->getData());
        $mail->send();

        return true;
    }

    protected function getTo():string
    {
        return $this->config['to'];
    }

    /**
     * @return Model\Document|int|string
     */
    protected function getDocument()
    {
        return $this->config['document'];
    }
}
