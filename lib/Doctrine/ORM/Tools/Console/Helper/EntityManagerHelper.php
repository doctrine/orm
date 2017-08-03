<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Console\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Helper\Helper;

/**
 * Doctrine CLI Connection Helper.
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class EntityManagerHelper extends Helper
{
    /**
     * Doctrine ORM EntityManagerInterface.
     *
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * Constructor.
     *
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Retrieves Doctrine ORM EntityManager.
     *
     * @return EntityManagerInterface
     */
    public function getEntityManager()
    {
        return $this->em;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'entityManager';
    }
}
