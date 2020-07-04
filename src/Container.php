<?php

namespace Mqwerty\DI;

use Psr\Container\ContainerInterface;
use ReflectionClass;

final class Container implements ContainerInterface
{
    private array $config;
    private array $container = [];

    public function __construct(array $config = [])
    {
        $this->config = array_merge(
            [
                'shared' => [],
            ],
            $config
        );
        /** @phan-suppress-next-line PhanTypeNoPropertiesForeach */
        foreach ($this->config['shared'] as $value) {
            $this->container[$value] = null;
        }
    }

    public function has($id): bool
    {
        return array_key_exists($id, $this->config) || class_exists($id);
    }

    /**
     * @param string $id
     * @return mixed
     * @throws NotFoundException
     */
    public function get($id)
    {
        if (array_key_exists($id, $this->config)) {
            if (!is_callable($this->config[$id])) {
                return $this->config[$id];
            }
            if (isset($this->container[$id])) {
                return $this->container[$id];
            }
            if (array_key_exists($id, $this->container)) {
                $this->container[$id] = call_user_func($this->config[$id], $this);
                return $this->container[$id];
            }
            return call_user_func($this->config[$id], $this);
        }

        if (class_exists($id)) {
            return $this->autowire($id);
        }

        throw new NotFoundException("$id not found");
    }

    /**
     * @param string $id
     * @return mixed
     * @throws NotFoundException
     */
    private function autowire($id)
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $class = new ReflectionClass($id);
        $constructor = $class->getConstructor();
        $args = [];
        if ($constructor) {
            $params = $constructor->getParameters();
            foreach ($params as $param) {
                if ($param->isOptional()) {
                    /** @noinspection PhpUnhandledExceptionInspection */
                    $args[] = $param->getDefaultValue();
                } else {
                    $paramClass = $param->getClass();
                    if (!$paramClass) {
                        throw new NotFoundException("Can't resolve param '{$param->getName()}' for $id");
                    }
                    $args[] = $this->get($paramClass->getName());
                }
            }
        }
        return $class->newInstanceArgs($args);
    }
}
