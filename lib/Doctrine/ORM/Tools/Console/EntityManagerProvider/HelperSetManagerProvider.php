<?php

namespace Doctrine\ORM\Tools\Console\EntityManagerProvider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Console\EntityManagerProvider;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Symfony\Component\Console\Helper\HelperSet;

use function assert;

class HelperSetManagerProvider implements EntityManagerProvider
{
    private $helperSet;

    public function __construct(HelperSet $helperSet)
    {
        $this->helperSet = $helperSet;
    }

    public function getManager(string $name = 'default'): EntityManagerInterface
    {
        if ($name !== 'default') {
            throw UnknownManagerException::unknownManager($name, ['default']);
        }

        $helper = $this->helperSet->get('entityManager');

        assert($helper instanceof EntityManagerHelper);

        return $helper->getEntityManager();
    }
}
