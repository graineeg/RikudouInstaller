<?php

namespace PHPSTORM_META {

    // register sets

    registerArgumentsSet(
        'messageTypes',
        \Rikudou\Installer\Enums\MessageType::ERROR,
        \Rikudou\Installer\Enums\MessageType::WARNING,
        \Rikudou\Installer\Enums\MessageType::STATUS
    );

    registerArgumentsSet(
        'operationTypes',
        \Rikudou\Installer\Enums\OperationType::COPY_FILES,
        \Rikudou\Installer\Enums\OperationType::ENVIRONMENT_VARIABLES,
        \Rikudou\Installer\Enums\OperationType::REGISTER_SYMFONY_BUNDLE
    );

    registerArgumentsSet(
        'projectTypes',
        'drupal-8',
        'any',
        'symfony4'
    );

    // arguments

    expectedArguments(
        \Rikudou\Installer\Result\Messages\Message::__construct(),
        1,
        argumentsSet('messageTypes')
    );

    // return values

    expectedReturnValues(
        \Rikudou\Installer\Operations\AbstractOperation::handles(),
        argumentsSet('operationTypes')
    );

}
