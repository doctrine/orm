<?php

declare(strict_types=1);

/**
 * Welcome to Doctrine 2.
 *
 * This is the index file of the sandbox. The first section of this file
 * demonstrates the bootstrapping and configuration procedure of Doctrine 2.
 * Below that section you can place your test code and experiment.
 */

namespace Sandbox;

use Entities\Address;
use Entities\User;

use const PHP_EOL;

$em = require_once __DIR__ . '/bootstrap.php';

// PUT YOUR TEST CODE BELOW

$user    = new User();
$address = new Address();

echo 'Hello World!' . PHP_EOL;
