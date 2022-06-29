<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Model;

use InvalidArgumentException;
use Valantic\PimcoreFormsBundle\Constant\MessageConstants;

class Message extends AbstractMessage
{
    protected string $type;
    protected string $message;
    protected bool $expire;
    protected int $delay;

    /**
     * @var array<mixed>
     */
    protected array $source;
    protected string $field;

    public function setType(string $type): self
    {
        if (!in_array($type, MessageConstants::MESSAGE_TYPES, true)) {
            throw new InvalidArgumentException();
        }

        $this->type = $type;

        return $this;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function setExpire(bool $expire): self
    {
        $this->expire = $expire;

        return $this;
    }

    public function setDelay(int $seconds): self
    {
        $this->delay = $seconds;

        return $this;
    }

    /**
     * @param array<mixed> $source
     *
     * @return $this
     */
    public function setSource(array $source): self
    {
        $this->source = $source;

        return $this;
    }

    public function setField(string $field): self
    {
        $this->field = $field;

        return $this;
    }

    protected function requiredAttributes(): array
    {
        return ['type', 'message'];
    }

    protected function optionalAttributes(): array
    {
        return ['expire', 'delay', 'source', 'field'];
    }
}
