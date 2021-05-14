<?php

namespace App\System\Database;

class Expression
{
    protected $value;

    public function __construct(?string $value)
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return strval($this->value);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getValue();
    }
}