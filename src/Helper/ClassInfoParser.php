<?php

namespace Rikudou\Installer\Helper;

class ClassInfoParser
{

    /**
     * @var string
     */
    private $file;

    /**
     * @var bool
     */
    private $parsed = false;

    /**
     * @var \ReflectionClass|null
     */
    private $reflection = null;

    public function __construct(string $file)
    {
        if (!file_exists($file)) {
            throw new \InvalidArgumentException("The file '{$file}' does not exist");
        }
        $this->file = $file;
    }

    public function isValidClass(): bool
    {
        $this->parse();
        return $this->reflection !== null;
    }

    public function isInstantiable(): bool
    {
        $this->parse();
        if (!$this->isValidClass()) {
            throw new \LogicException("The file does not contain a valid class");
        }
        return $this->reflection->isInstantiable();
    }

    public function getClassName(): string
    {
        $this->parse();
        if (!$this->isValidClass()) {
            throw new \LogicException("The file does not contain a valid class");
        }
        return $this->reflection->getName();
    }

    public function isSubclassOf(string $class): bool
    {
        return $this->reflection->isSubclassOf($class);
    }

    public function isInstanceOf(string $classOrInterface): bool
    {
        try {
            if ($this->implementsInterface($classOrInterface)) {
                return true;
            }
        } catch (\ReflectionException $e) {
            // do nothing
        }
        try {
            if ($this->isSubclassOf($classOrInterface)) {
                return true;
            }
        } catch (\ReflectionException $e) {
            // do nothing
        }
        return false;
    }

    public function implementsInterface(string $interface): bool
    {
        $this->parse();
        if (!$this->isValidClass()) {
            throw new \LogicException("The file does not contain a valid class");
        }
        return $this->reflection->implementsInterface($interface);
    }

    private function parse(): void
    {
        if (!$this->parsed) {
            $this->parsed = true;

            $fileContent = file_get_contents($this->file);
            $className = "";
            $namespace = "";

            $tokens = @token_get_all($fileContent);
            $tokensCount = count($tokens);

            for ($i = 0; $i < $tokensCount; $i++) {
                if ($className) {
                    break;
                }
                if (!$namespace && $tokens[$i][0] === T_NAMESPACE) {
                    for ($j = $i + 1; $j < $tokensCount; $j++) {
                        if (
                            isset($tokens[$j][0]) &&
                            ($tokens[$j][0] === T_STRING || $tokens[$j][0] === T_NS_SEPARATOR)
                        ) {
                            if (trim($tokens[$j][0])) {
                                $namespace .= $tokens[$j][1];
                            }
                        } else if ($tokens[$j] === '{' || $tokens[$j] === ';') {
                            break;
                        }
                    }
                } else if ($tokens[$i][0] === T_CLASS) {
                    for ($j = $i; $j < $tokensCount; $j++) {
                        if (isset($tokens[$j][0]) && $tokens[$j][0] === T_STRING) {
                            $className = $tokens[$j][1];
                            break;
                        }
                    }
                }
            }
            if ($namespace && substr($namespace, 0, 1) !== "\\") {
                $namespace = "\\{$namespace}";
            }
            $className = "{$namespace}\\{$className}";

            if ($className === "\\") {
                return;
            }

            require_once $this->file;

            if (!class_exists($className)) {
                return;
            }

            try {
                $this->reflection = new \ReflectionClass($className);
            } catch (\ReflectionException $e) {
                $this->isClass = false;
                return;
            }
        }
    }

    /**
     * @return \ReflectionClass
     */
    public function getReflection(): \ReflectionClass
    {
        $this->parse();
        if (!$this->isValidClass()) {
            throw new \LogicException("The file does not contain a valid class");
        }
        return $this->reflection;
    }

}