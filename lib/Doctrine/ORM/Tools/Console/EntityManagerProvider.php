<?php

namespace Doctrine\ORM\Tools\Console;

use Doctrine\ORM\EntityManager;

interface EntityManagerProvider
{
    public function getManager(string $name = 'default'): EntityManager;
}
