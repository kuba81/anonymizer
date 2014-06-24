<?php

/*
 * This file is part of the Anonymizer package, an OpenSky project.
 *
 * Copyright (c) 2004-2014 Kuba Stawiarski <kuba.stawiarski@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Anonymizer;

/**
 * Class Anonymizer
 * @package Anonymizer
 */
class Anonymizer {
    /**
     * @var string
     */
    private $classname;

    /**
     * @var \Closure[]
     */
    private $methods = [];

    /**
     * @param string $classname class/interface to instantiate (use null if you
     *     want to create a class that doesn’t have any abstraction)
     * @param \Closure[] $methods
     * @return object
     */
    static function generate($classname = null, $methods = null) {
        $generator = new self($classname);

        foreach ($methods as $name => $implementation) {
            $generator->addMethod($name, $implementation);
        }

        return $generator->newInstance();
    }

    /**
     * @param string $class
     */
    private function __construct($class = null) {
        $this->classname = $class;
    }

    /**
     * @param string $methodName
     * @param \Closure $implementation
     */
    private function addMethod($methodName, $implementation) {
        $this->methods[$methodName] = $implementation;
    }

    /**
     * @return object
     */
    private function newInstance() {
        $classname = $this->classname;
        $methods = $this->methods;

        $instanceName = 'Anonymous_' . md5('Anonymizer' . $classname . microtime());

        $stereotype = '';

        if ($classname !== null) {
            $class = new \ReflectionClass($classname);
            $stereotype = $class->isInterface() ? "implements {$classname}" : "extends {$classname}";
        }

        $src  = "class {$instanceName} {$stereotype} {\n";
        $src .= "    private \$__methods = [];\n";


        $remainingMethods = $methods;

        if (isset($class)) {
            foreach ($class->getMethods() as $method) {
                if ($method->isAbstract() && !isset($methods[$method->getName()])) {
                    throw new \LogicException(sprintf('Missing implementation for %s()', $method->getName()));
                }
                $src .= $this->generateMethodSourceFromMethod($method);
                unset($remainingMethods[$method->getName()]);
            }
        }

        foreach ($remainingMethods as $name => $implementation) {
            $src .= $this->generateMethodSourceFromFunction($name, $implementation);
        }

        $src .= $this->generateMethodSetter();
        $src .= "return new {$instanceName};\n";

        $instance = eval($src);

        foreach ($methods as $name => $implementation) {
            $function = new \ReflectionFunction($implementation);
            if (isset($class) && $class->hasMethod($name)
                && !$this->functionsHaveMatchingSignatures($function, $class->getMethod($name))) {
                throw new \LogicException(sprintf('Incompatible definition for method %s', $name));
            }

            $instance->__setMethod($name, $implementation);
        }
        return $instance;
    }

    /**
     * @param $name
     * @param callable $function
     * @return string
     */
    private function generateMethodSourceFromFunction($name, \Closure $function) {
        $parameters = $this->generateParametersList(new \ReflectionFunction($function));

        return $this->generateMethodSource($name, $parameters);
    }

    /**
     * @param \ReflectionMethod $method
     * @return string
     */
    private function generateMethodSourceFromMethod(\ReflectionMethod $method) {
        $name = $method->getName();

        $parameters = $this->generateParametersList($method);

        return $this->generateMethodSource($name, $parameters);
    }

    /**
     * Generates the list of parameters
     *
     * @param \ReflectionFunctionAbstract $function
     * @return string
     */
    private function generateParametersList(\ReflectionFunctionAbstract $function) {
        $parameters = [];

        foreach ($function->getParameters() as $parameter) {
            $parameterDeclaration = '';
            if ($parameter->getClass() !== null) {
                $parameterDeclaration .= $parameter->getClass()->getName() . ' ';
            } else if ($parameter->isArray()) {
                $parameterDeclaration .= 'array ';
            }

            if ($parameter->isPassedByReference()) {
                $parameterDeclaration .= '& ';
            }

            $parameterDeclaration .= '$' . $parameter->getName();

            if ($parameter->isDefaultValueAvailable()) {
                $parameterDeclaration .= ' = ' . var_export($parameter->getDefaultValue(), true);
            }

            $parameters[] = $parameterDeclaration;
        }

        if (!empty($parameters)) {
            return implode(', ', $parameters);
        } else {
            return '';
        }
    }

    /**
     * Generates source code for a method
     *
     * @param $name
     * @param $parameters
     * @return string
     */
    private static function generateMethodSource($name, $parameters) {
        $src  = "    public function {$name}({$parameters}) {\n";
        $src .= "        if (isset(\$this->__methods['{$name}'])) {\n";
        $src .= "           return call_user_func_array(\$this->__methods['{$name}'], func_get_args());\n";
        $src .= "        }\n";
        $src .= "    }\n";

        return $src;
    }

    /**
     * Generates the method setter
     *
     * @return string
     */
    private function generateMethodSetter() {
        $src  = "    public function __setMethod(\$name, \$implementation) {\n";
        $src .= "        \$this->__methods[\$name] = \$implementation;\n";
        $src .= "    }\n";
        $src .= "}\n";

        return $src;
    }

    /**
     * Checks if two functions have identical signatures
     *
     * @param \ReflectionFunctionAbstract $function1
     * @param \ReflectionFunctionAbstract $function2
     * @return bool
     */
    private function functionsHaveMatchingSignatures(
        \ReflectionFunctionAbstract $function1,
        \ReflectionFunctionAbstract $function2
    ) {
        return $this->generateParametersList($function1) == $this->generateParametersList($function2);
    }
}
