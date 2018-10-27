<?php

namespace Rikudou\Installer\Operations\Messages;

final class MessagesCollection
{

    /**
     * @var Message[]
     */
    private $messages = [];

    /**
     * MessagesCollection constructor.
     * @param Message ...$messages
     */
    public function __construct(...$messages)
    {
        $this->setMessages($messages);
    }

    /**
     * @return \Generator|Message[]
     */
    public function getGenerator()
    {
        foreach ($this->messages as $message) {
            yield $message;
        }
    }

    public function addMessage(Message $message): self
    {
        $this->messages[] = $message;
        return $this;
    }

    /**
     * @return Message[]
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * @param Message[] $messages
     * @return MessagesCollection
     */
    public function setMessages(array $messages): MessagesCollection
    {
        foreach ($messages as $message) {
            if (!$message instanceof Message) {
                throw new \InvalidArgumentException("One of the messages is not instance of " . Message::class);
            }
            $this->messages[] = $message;
        }
        return $this;
    }

}