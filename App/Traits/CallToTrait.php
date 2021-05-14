<?php

namespace App\Traits;

trait CallToTrait
{
    /**
     * @param $object
     * @param $method
     * @param $parameters
     * @return mixed
     */
    protected function callTo($object, $method, $parameters)
    {
        try {
            return $object->{$method}(...$parameters);
        } catch (\BadMethodCallException $e) {
            throw $e;
        }
    }
}