<?php

require 'vendor/autoload.php';

require_once 'bootstrap.php';
/**
 * @var \App\System\Console\Invoker $invoker
 */
$invoker = new \App\System\Console\Invoker($argv);

$invoker->execute();