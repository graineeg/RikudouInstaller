<?php

namespace Rikudou\Installer\Result\Messages;

use Rikudou\Installer\Enums\MessageType;

final class Message
{
    /**
     * @var string
     */
    private $message;

    /**
     * @var int
     */
    private $messageType;

    public function __construct(string $message, int $messageType)
    {
        $this->message = $message;
        $this->messageType = $messageType;
    }

    public function __toString()
    {
        return $this->message;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string $message
     *
     * @return Message
     */
    public function setMessage(string $message): Message
    {
        $this->message = $message;

        return $this;
    }

    public function isStatusMessage(): bool
    {
        return $this->messageType === MessageType::STATUS;
    }

    public function isWarningMessage(): bool
    {
        return $this->messageType === MessageType::WARNING;
    }

    public function isErrorMessage(): bool
    {
        return $this->messageType === MessageType::ERROR;
    }

    public static function createStatus(string $message): self
    {
        return new static($message, MessageType::STATUS);
    }

    public static function createWarning(string $message): self
    {
        return new static($message, MessageType::WARNING);
    }

    public static function createError(string $message): self
    {
        return new static($message, MessageType::ERROR);
    }
}
