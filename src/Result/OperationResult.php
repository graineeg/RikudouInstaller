<?php

namespace Rikudou\Installer\Result;

use Rikudou\Installer\Enums\MessageType;
use Rikudou\Installer\Result\Messages\Message;
use Rikudou\Installer\Result\Messages\MessagesCollection;

final class OperationResult
{
    /**
     * @var MessagesCollection|null
     */
    private $messagesCollection = null;

    /**
     * @var array
     */
    private $extraConfig = [];

    /**
     * @var string
     */
    private $version = '0';

    /**
     * @var string
     */
    private $operationName = '';

    public function isFailure(): bool
    {
        if (!count($this->getMessagesCollection()->getMessages())) {
            return false;
        }
        foreach ($this->getMessagesCollection()->getGenerator() as $message) {
            if ($message->isErrorMessage()) {
                return true;
            }
        }

        return false;
    }

    public function addMessage($message, int $messageType): self
    {
        if (!is_string($message) && !$message instanceof Message) {
            throw new \InvalidArgumentException('Message must be a string or ' . Message::class);
        }
        if (is_string($message)) {
            $message = new Message($message, $messageType);
        }
        $this->getMessagesCollection()->addMessage($message);

        return $this;
    }

    public function addStatusMessage($message): self
    {
        return $this->addMessage($message, MessageType::STATUS);
    }

    public function addWarningMessage($message): self
    {
        return $this->addMessage($message, MessageType::WARNING);
    }

    public function addErrorMessage($message): self
    {
        return $this->addMessage($message, MessageType::ERROR);
    }

    public function isSuccess(): bool
    {
        if (!count($this->getMessagesCollection()->getMessages())) {
            return false;
        }
        foreach ($this->getMessagesCollection()->getGenerator() as $message) {
            if ($message->isErrorMessage()) {
                return false;
            }
        }

        return true;
    }

    public function isNeutral(): bool
    {
        return !$this->isSuccess() && !$this->isFailure();
    }

    public function getMessagesCollection(): MessagesCollection
    {
        if (is_null($this->messagesCollection)) {
            $this->messagesCollection = new MessagesCollection();
        }

        return $this->messagesCollection;
    }

    /**
     * @param MessagesCollection $messagesCollection
     *
     * @return OperationResult
     */
    public function setMessagesCollection(MessagesCollection $messagesCollection): OperationResult
    {
        $this->messagesCollection = $messagesCollection;

        return $this;
    }

    /**
     * @return array
     */
    public function getExtraConfig(): array
    {
        return $this->extraConfig;
    }

    /**
     * @param array $extraConfig
     *
     * @return OperationResult
     */
    public function setExtraConfig(array $extraConfig): OperationResult
    {
        $this->extraConfig = $extraConfig;

        return $this;
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @param string $version
     */
    public function setVersion(string $version): void
    {
        $this->version = $version;
    }

    /**
     * @return string
     */
    public function getOperationName(): string
    {
        return $this->operationName;
    }

    /**
     * @param string $operationName
     */
    public function setOperationName(string $operationName): void
    {
        $this->operationName = $operationName;
    }
}
