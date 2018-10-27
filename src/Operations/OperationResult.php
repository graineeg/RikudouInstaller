<?php

namespace Rikudou\Installer\Operations;

use Rikudou\Installer\Enums\MessageType;
use Rikudou\Installer\Operations\Messages\Message;
use Rikudou\Installer\Operations\Messages\MessagesCollection;

final class OperationResult
{

    /**
     * @var MessagesCollection|null
     */
    private $messagesCollection = null;

    public function isFailure(): bool
    {
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
            throw new \InvalidArgumentException("Message must be a string or " . Message::class);
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

    public function isSuccess()
    {
        return !$this->isFailure();
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
     * @return OperationResult
     */
    public function setMessagesCollection(MessagesCollection $messagesCollection): OperationResult
    {
        $this->messagesCollection = $messagesCollection;
        return $this;
    }

}