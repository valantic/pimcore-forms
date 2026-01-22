<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Tests\Support\Traits;

use PHPUnit\Framework\MockObject\MockObject;
use Pimcore\Mail;

trait MocksPimcoreMail
{
    /**
     * Creates a mock Pimcore Mail object with common methods stubbed.
     */
    protected function createMockPimcoreMail(): MockObject
    {
        $mail = $this->createMock(Mail::class);
        $mail->method('addTo')->willReturnSelf();
        $mail->method('setDocument')->willReturnSelf();
        $mail->method('setParams')->willReturnSelf();
        $mail->method('subject')->willReturnSelf();
        $mail->method('addFrom')->willReturnSelf();
        $mail->method('send')->willReturnSelf();

        return $mail;
    }

    /**
     * Creates a mock Pimcore Mail object that fails to send.
     */
    protected function createFailingMockPimcoreMail(): MockObject
    {
        $mail = $this->createMock(Mail::class);
        $mail->method('addTo')->willReturnSelf();
        $mail->method('setDocument')->willReturnSelf();
        $mail->method('setParams')->willReturnSelf();
        $mail->method('send')->willThrowException(new \Exception('Failed to send mail'));

        return $mail;
    }
}
