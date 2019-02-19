<?php

namespace Rikudou\Installer\Enums;

class MessageType
{
    public const ERROR = 1 << 0;

    public const WARNING = 1 << 1;

    public const STATUS = 1 << 2;
}
