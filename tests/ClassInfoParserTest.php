<?php

namespace Rikudou\Installer\Tests;

use PHPUnit\Framework\TestCase;
use Rikudou\Installer\Helper\ClassInfoParser;
use Rikudou\Installer\Tests\Resources\ClassInfoParser\AlternativeNamespaceSyntaxClass;
use Rikudou\Installer\Tests\Resources\ClassInfoParser\MixedInterfaceAndClass;
use Rikudou\Installer\Tests\Resources\ClassInfoParser\StandardClass;
use Rikudou\Installer\Tests\Resources\ClassInfoParser\TwoNamespacesTwoClasses;

class ClassInfoParserTest extends TestCase
{

    public function testStandardClass()
    {
        $classInfo = $this->getInstance(__DIR__ . "/Resources/ClassInfoParser/StandardClass.php");
        $this->assertEquals(true, $classInfo->isValidClass());
        $this->assertEquals(StandardClass::class, $classInfo->getClassName());
    }

    public function testNoNamespaceClass()
    {
        $classInfo = $this->getInstance(__DIR__ . "/Resources/ClassInfoParser/NoNamespaceClass.php");
        $this->assertEquals(true, $classInfo->isValidClass());
        $this->assertEquals(\NoNamespaceClass::class, $classInfo->getClassName());
    }

    public function testAlternativeNamespaceSyntax()
    {
        $classInfo = $this->getInstance(__DIR__ . "/Resources/ClassInfoParser/AlternativeNamespaceSyntaxClass.php");
        $this->assertEquals(true, $classInfo->isValidClass());
        $this->assertEquals(AlternativeNamespaceSyntaxClass::class, $classInfo->getClassName());
    }

    public function testMultipleNamespacesMultipleClasses()
    {
        $classInfo = $this->getInstance(__DIR__ . "/Resources/ClassInfoParser/TwoNamespacesTwoClasses.php");
        $this->assertEquals(true, $classInfo->isValidClass());
        // only the first namespace and first class should be taken into account
        $this->assertEquals(TwoNamespacesTwoClasses::class, $classInfo->getClassName());
    }

    public function testNoClass()
    {
        $classInfo = $this->getInstance(__DIR__ . "/Resources/ClassInfoParser/NoClass.php");
        $this->assertEquals(false, $classInfo->isValidClass());
        $this->expectException(\LogicException::class);
        $classInfo->getClassName();
    }

    public function testInterface()
    {
        $classInfo = $this->getInstance(__DIR__ . "/Resources/ClassInfoParser/InterfaceOnly.php");
        // the class only handles classes
        $this->assertEquals(false, $classInfo->isValidClass());
        $this->expectException(\LogicException::class);
        $classInfo->getClassName();
    }

    public function testTrait()
    {
        $classInfo = $this->getInstance(__DIR__ . "/Resources/ClassInfoParser/TraitOnly.php");
        // the class only handles classes
        $this->assertEquals(false, $classInfo->isValidClass());
        $this->expectException(\LogicException::class);
        $classInfo->getClassName();
    }

    public function testMixedInterfaceAndClass()
    {
        $classInfo = $this->getInstance(__DIR__ . "/Resources/ClassInfoParser/MixedInterfaceAndClass.php");
        $this->assertEquals(true, $classInfo->isValidClass());
        $this->assertEquals(MixedInterfaceAndClass::class, $classInfo->getClassName());
    }

    private function getInstance(string $file)
    {
        return new ClassInfoParser($file);
    }

}