<?php

namespace Doctrine\ORM\Tools\Console;

use Doctrine\ORM\EntityManagerInterface;

interface EntityManagerProvider
{
    public function getManager(string $name = 'default'): EntityManagerInterface;
}
