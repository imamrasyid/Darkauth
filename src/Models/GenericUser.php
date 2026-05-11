<?php

namespace Darkauth\Models;

use Darkauth\Core\UserInterface;
use Darkauth\Core\AuthenticatableTrait;

/**
 * Class GenericUser
 * 
 * A simple implementation of UserInterface.
 */
class GenericUser implements UserInterface
{
    use AuthenticatableTrait;

    /**
     * @var array
     */
    protected $attributes;

    /**
     * GenericUser constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    /**
     * Dynamically access attributes.
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Dynamically set attributes.
     *
     * @param string $key
     * @param mixed $value
     */
    public function __set($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Get all attributes.
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }
}
