<?php

/*
 * This file is part of the Phony package.
 *
 * Copyright © 2016 Erin Millard
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Eloquent\Phony\Mock\Builder\Definition;

use Closure;
use Eloquent\Phony\Feature\FeatureDetector;
use Eloquent\Phony\Feature\FeatureDetectorInterface;
use Eloquent\Phony\Invocation\InvocableInspector;
use Eloquent\Phony\Invocation\InvocableInspectorInterface;
use Eloquent\Phony\Mock\Builder\Definition\Method\CustomMethodDefinition;
use Eloquent\Phony\Mock\Builder\Definition\Method\MethodDefinitionCollection;
use Eloquent\Phony\Mock\Builder\Definition\Method\MethodDefinitionCollectionInterface;
use Eloquent\Phony\Mock\Builder\Definition\Method\RealMethodDefinition;
use Eloquent\Phony\Mock\Builder\Definition\Method\TraitMethodDefinition;
use ReflectionClass;
use ReflectionFunction;

/**
 * Represents a mock class definition.
 */
class MockDefinition implements MockDefinitionInterface
{
    /**
     * Construct a new mock definition.
     *
     * @param array<string,ReflectionClass>    $types                  The types.
     * @param array<string,callable|null>      $customMethods          The custom methods.
     * @param array<string,mixed>              $customProperties       The custom properties.
     * @param array<string,callable|null>      $customStaticMethods    The custom static methods.
     * @param array<string,mixed>              $customStaticProperties The custom static properties.
     * @param array<string,mixed>              $customConstants        The custom constants.
     * @param string|null                      $className              The class name.
     * @param InvocableInspectorInterface|null $invocableInspector     The invocable inspector.
     * @param FeatureDetectorInterface|null    $featureDetector        The feature detector to use.
     */
    public function __construct(
        array $types = array(),
        array $customMethods = array(),
        array $customProperties = array(),
        array $customStaticMethods = array(),
        array $customStaticProperties = array(),
        array $customConstants = array(),
        $className = null,
        InvocableInspectorInterface $invocableInspector = null,
        FeatureDetectorInterface $featureDetector = null
    ) {
        if (null === $invocableInspector) {
            $invocableInspector = InvocableInspector::instance();
        }
        if (null === $featureDetector) {
            $featureDetector = FeatureDetector::instance();
        }

        $this->types = $types;
        $this->customMethods = $customMethods;
        $this->customProperties = $customProperties;
        $this->customStaticMethods = $customStaticMethods;
        $this->customStaticProperties = $customStaticProperties;
        $this->customConstants = $customConstants;
        $this->className = $className;
        $this->invocableInspector = $invocableInspector;
        $this->featureDetector = $featureDetector;

        $this->signature = array(
            'types' => array_keys($types),
            'customMethods' => array(),
            'customProperties' => $customProperties,
            'customStaticMethods' => array(),
            'customStaticProperties' => $customStaticProperties,
            'customConstants' => $customConstants,
            'className' => $className,
        );

        foreach ($customMethods as $name => $method) {
            if ($method instanceof Closure) {
                $reflector = new ReflectionFunction($method);
                $this->signature['customMethods'][$name] = array(
                    'custom',
                    $reflector->getFileName(),
                    $reflector->getStartLine(),
                    $reflector->getEndLine(),
                );
            } else {
                $this->signature['customMethods'][$name] = $method;
            }
        }

        foreach ($customStaticMethods as $name => $method) {
            if ($method instanceof Closure) {
                $reflector = new ReflectionFunction($method);
                $this->signature['customStaticMethods'][$name] = array(
                    'custom',
                    $reflector->getFileName(),
                    $reflector->getStartLine(),
                    $reflector->getEndLine(),
                );
            } else {
                $this->signature['customStaticMethods'][$name] = $method;
            }
        }

        $this->isTraitSupported = $this->featureDetector->isSupported('trait');
        $this->isRelaxedKeywordsSupported
             = $this->featureDetector->isSupported('parser.relaxed-keywords');
    }

    /**
     * Get the types.
     *
     * @return array<string,ReflectionClass> The types.
     */
    public function types()
    {
        return $this->types;
    }

    /**
     * Get the custom methods.
     *
     * @return array<string,callable|null> The custom methods.
     */
    public function customMethods()
    {
        return $this->customMethods;
    }

    /**
     * Get the custom properties.
     *
     * @return array<string,mixed> The custom properties.
     */
    public function customProperties()
    {
        return $this->customProperties;
    }

    /**
     * Get the custom static methods.
     *
     * @return array<string,callable|null> The custom static methods.
     */
    public function customStaticMethods()
    {
        return $this->customStaticMethods;
    }

    /**
     * Get the custom static properties.
     *
     * @return array<string,mixed> The custom static properties.
     */
    public function customStaticProperties()
    {
        return $this->customStaticProperties;
    }

    /**
     * Get the custom constants.
     *
     * @return array<string,mixed> The custom constants.
     */
    public function customConstants()
    {
        return $this->customConstants;
    }

    /**
     * Get the class name.
     *
     * @return string|null The class name.
     */
    public function className()
    {
        return $this->className;
    }

    /**
     * Get the signature.
     *
     * This is an opaque value designed to aid in determining whether two mock
     * definitions are the same.
     *
     * @return mixed The signature.
     */
    public function signature()
    {
        return $this->signature;
    }

    /**
     * Get the invocable inspector.
     *
     * @return InvocableInspectorInterface The invocable inspector.
     */
    public function invocableInspector()
    {
        return $this->invocableInspector;
    }

    /**
     * Get the feature detector.
     *
     * @return FeatureDetectorInterface The feature detector.
     */
    public function featureDetector()
    {
        return $this->featureDetector;
    }

    /**
     * Get the type names.
     *
     * @return array<string> The type names.
     */
    public function typeNames()
    {
        $this->inspectTypes();

        return $this->typeNames;
    }

    /**
     * Get the parent class name.
     *
     * @return string|null The parent class name, or null if the mock will not extend a class.
     */
    public function parentClassName()
    {
        $this->inspectTypes();

        return $this->parentClassName;
    }

    /**
     * Get the interface names.
     *
     * @return array<string> The interface names.
     */
    public function interfaceNames()
    {
        $this->inspectTypes();

        return $this->interfaceNames;
    }

    /**
     * Get the trait names.
     *
     * @return array<string> The trait names.
     */
    public function traitNames()
    {
        $this->inspectTypes();

        return $this->traitNames;
    }

    /**
     * Get the method definitions.
     *
     * Calling this method will finalize the mock builder.
     *
     * @return MethodDefinitionCollectionInterface The method definitions.
     */
    public function methods()
    {
        $this->buildMethods();

        return $this->methods;
    }

    /**
     * Check if the supplied definition is equal to this definition.
     *
     * @return boolean True if equal.
     */
    public function isEqualTo(MockDefinitionInterface $definition)
    {
        return $definition->signature() === $this->signature;
    }

    /**
     * Inspect the supplied types and build caches of useful information.
     */
    protected function inspectTypes()
    {
        if (null !== $this->typeNames) {
            return;
        }

        $this->typeNames = array();
        $this->interfaceNames = array();
        $this->traitNames = array();

        foreach ($this->types as $type) {
            $this->typeNames[] = $typeName = $type->getName();

            if ($type->isInterface()) {
                $this->interfaceNames[] = $typeName;
            } elseif ($this->isTraitSupported && $type->isTrait()) {
                $this->traitNames[] = $typeName;
            } else {
                $this->parentClassName = $typeName;
            }
        }
    }

    /**
     * Build the method definitions.
     */
    protected function buildMethods()
    {
        if (null !== $this->methods) {
            return;
        }

        $methods = array();
        $unmockable = array();

        if ($typeName = $this->parentClassName()) {
            foreach ($this->types[$typeName]->getMethods() as $method) {
                if ($method->isPrivate()) {
                    continue;
                }

                $methodName = $method->getName();

                if ($method->isConstructor() || $method->isFinal()) {
                    $unmockable[$methodName] = true;
                } else {
                    $methods[$methodName] = new RealMethodDefinition($method);
                }
            }
        }

        $traitMethods = array();

        foreach ($this->traitNames() as $typeName) {
            foreach ($this->types[$typeName]->getMethods() as $method) {
                $methodDefinition = new TraitMethodDefinition($method);
                $methodName = $methodDefinition->name();

                if (!$method->isAbstract()) {
                    $traitMethods[] = $methodDefinition;
                }

                if (isset($unmockable[$methodName])) {
                    continue;
                }

                if (!isset($methods[$methodName])) {
                    $methods[$methodName] = $methodDefinition;
                }
            }
        }

        foreach ($this->interfaceNames() as $typeName) {
            foreach ($this->types[$typeName]->getMethods() as $method) {
                $methodName = $method->getName();

                if (isset($unmockable[$methodName])) {
                    continue;
                }

                if (!isset($methods[$methodName])) {
                    $methods[$methodName] = new RealMethodDefinition($method);
                }
            }
        }

        if ($this->isRelaxedKeywordsSupported) { // @codeCoverageIgnoreStart
            // class is the only keyword that can not be used as a method name
            unset($methods['class']);
        } else { // @codeCoverageIgnoreEnd
            $methodNames = array_keys($methods);
            $tokens = token_get_all('<?php ' . implode(' ', $methodNames));

            foreach ($methodNames as $index => $methodName) {
                $tokenIndex = $index * 2 + 1;

                if (
                    !is_array($tokens[$tokenIndex]) ||
                    $tokens[$tokenIndex][0] !== T_STRING
                ) { // @codeCoverageIgnoreStart
                    unset($methods[$methodName]);
                } // @codeCoverageIgnoreEnd
            }
        }

        foreach ($this->customStaticMethods as $methodName => $callback) {
            if (null === $callback) {
                $reflector = null;
            } else {
                $reflector =
                    $this->invocableInspector->callbackReflector($callback);
            }

            $methods[$methodName] = new CustomMethodDefinition(
                true,
                $methodName,
                $callback,
                $reflector
            );
        }

        foreach ($this->customMethods as $methodName => $callback) {
            if (null === $callback) {
                $reflector = null;
            } else {
                $reflector =
                    $this->invocableInspector->callbackReflector($callback);
            }

            $methods[$methodName] = new CustomMethodDefinition(
                false,
                $methodName,
                $callback,
                $reflector
            );
        }

        $this->methods =
            new MethodDefinitionCollection($methods, $traitMethods);
    }

    private $types;
    private $customMethods;
    private $customProperties;
    private $customStaticMethods;
    private $customStaticProperties;
    private $customConstants;
    private $className;
    private $signature;
    private $invocableInspector;
    private $featureDetector;
    private $isTraitSupported;
    private $isRelaxedKeywordsSupported;
    private $typeNames;
    private $parentClassName;
    private $interfaceNames;
    private $traitNames;
    private $methods;
}
