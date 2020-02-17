<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group DDC-1526
 */
class DDC1526Test extends OrmFunctionalTestCase
{
    public function setUp() : void
    {
        parent::setUp();
        $this->schemaTool->createSchema(
            [
                $this->em->getClassMetadata(DDC1526Menu::class),
            ]
        );
    }

    public function testIssue() : void
    {
        $parents = [];
        for ($i = 0; $i < 9; $i++) {
            $entity = new DDC1526Menu();

            if (isset($parents[($i % 3)])) {
                $entity->parent = $parents[($i%3)];
            }

            $this->em->persist($entity);
            $parents[$i] = $entity;
        }
        $this->em->flush();
        $this->em->clear();

        $dql   = 'SELECT m, c
            FROM ' . __NAMESPACE__ . '\DDC1526Menu m
            LEFT JOIN m.children c';
        $menus = $this->em->createQuery($dql)->getResult();

        // All Children collection now have to be initialized
        foreach ($menus as $menu) {
            self::assertTrue($menu->children->isInitialized());
        }
    }
}

/**
 * @ORM\Entity
 */
class DDC1526Menu
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    public $id;
    /** @ORM\ManyToOne(targetEntity=DDC1526Menu::class, inversedBy="children") */
    public $parent;

    /** @ORM\OneToMany(targetEntity=DDC1526Menu::class, mappedBy="parent") */
    public $children;
}
