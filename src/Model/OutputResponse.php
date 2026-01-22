<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Model;

class OutputResponse
{
    /**
     * @var array<AbstractMessage>
     */
    private array $messages = [];

    /**
     * @var array<bool>
     */
    private array $statuses = [];

    /**
     * @return array<AbstractMessage>
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * @param array<AbstractMessage> $messages
     *
     * @return $this
     */
    public function setMessages(array $messages): self
    {
        $this->messages = $messages;

        return $this;
    }

    /**
     * @return $this
     */
    public function addMessage(AbstractMessage $message): self
    {
        $this->messages[] = $message;

        return $this;
    }

    public function getOverallStatus(): bool
    {
        return array_reduce(
            $this->statuses,
            fn ($previous, $current) => $previous && $current,
            true,
        );
    }

    /**
     * @return $this
     */
    public function addStatus(bool $status): self
    {
        $this->statuses[] = $status;

        return $this;
    }
}
