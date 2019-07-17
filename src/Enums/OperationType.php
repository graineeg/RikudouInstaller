<?php

namespace Rikudou\Installer\Enums;

final class OperationType
{
    public const COPY_FILES = 'copyFiles';

    public const ENVIRONMENT_VARIABLES = 'environmentVariables';

    public const REGISTER_SYMFONY_BUNDLE = 'symfonyBundleRegister';

    public const GITIGNORE = 'gitignore';
}
