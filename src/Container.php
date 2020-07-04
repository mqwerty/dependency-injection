<?php

namespace Mqwerty\DI;

use Psr\Container\ContainerInterface;
use ReflectionClass;

class Container implements ContainerInterface
{
    protected const SHARED = 'shared';

    protected array $shared = [];
    protected array $config;

    public function __construct(array $config = [])
    {
        if (isset($config[static::SHARED])) {
            foreach ($config[static::SHARED] as $value) {
                $this->shared[$value] = null;
            }
            unset($config[static::SHARED]);
        }
        $this->config = $config;
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
            if (isset($this->shared[$id])) {
                return $this->shared[$id];
            }
            if (array_key_exists($id, $this->shared)) {
                $this->shared[$id] = call_user_func($this->config[$id], $this);
                return $this->shared[$id];
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
    protected function autowire($id)
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
