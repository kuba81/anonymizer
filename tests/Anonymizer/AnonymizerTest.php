<?php

namespace Anonymizer;

class AnonymizerTest extends \PHPUnit_Framework_TestCase {
    public function testShouldCreateInstanceFromMethodDefinitions() {
        $anonymous = Anonymizer::generate(null, [
            'getX' => function() {
                return 5;
            },
            'getY' => function() {
                return 10;
            }
        ]);

        $this->assertEquals(5, $anonymous->getX());
        $this->assertEquals(10, $anonymous->getY());
    }

    public function testShouldCreateInstanceOfAnInterface() {
        $anonymousIterator = Anonymizer::generate(
            'Anonymizer\InterfaceWithOneMethod', [
            'a' => function() {
                return 'a';
            }
        ]);

        $this->assertInstanceOf('Anonymizer\InterfaceWithOneMethod', $anonymousIterator);
    }

    public function testShouldCreateInstanceOfAnAbstractClass() {
        $anonymousAbstractClass = Anonymizer::generate(
            'Anonymizer\AbstractClassWithOneAbstractMethod', [
            'a' => function() {
                return 'a';
            }
        ]);

        $this->assertInstanceOf(
            'Anonymizer\AbstractClassWithOneAbstractMethod',
            $anonymousAbstractClass
        );
    }

    public function testShouldBePossibleToOverrideConcreteMethods() {
        $instance = Anonymizer::generate(
            'Anonymizer\AbstractClassWithOneConcreteMethod', [
            'a' => function() {
                return 50;
            }
        ]);

        $this->assertEquals(50, $instance->a());
    }

    public function testShouldRaiseErrorWhenInterfaceMethodIsNotImplemented() {
        $this->setExpectedException('LogicException',
            'Missing implementation for getIterator()');
        Anonymizer::generate('IteratorAggregate', []);
    }

    public function testShouldRaiseErrorWhenAbstractClassMethodIsNotImplemented() {
        $this->setExpectedException('LogicException',
            'Missing implementation for b()');
        Anonymizer::generate(
            'Anonymizer\AbstractClassWithOneAbstractAndOneConcreteMethod',
            []
        );
    }
}

abstract class AbstractClassWithOneAbstractAndOneConcreteMethod {
    public function a() {
        return 'a';
    }
    abstract public function b();
}

abstract class AbstractClassWithOneConcreteMethod {
    public function a() {
        return 'a';
    }
}

abstract class AbstractClassWithOneAbstractMethod {
    abstract public function a();
}

interface InterfaceWithOneMethod {
    public function a();
}