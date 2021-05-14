<?php

namespace App\Contracts;

interface CommandInterface
{
    public function execute(): void;
}