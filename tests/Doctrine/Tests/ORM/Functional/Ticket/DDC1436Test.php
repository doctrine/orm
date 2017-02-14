<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;

/**
 * @group DDC-1436
 */
class DDC1436Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        try {
            $this->schemaTool->createSchema(
                [
                $this->em->getClassMetadata(DDC1436Page::class),
                ]
            );
        } catch (\Exception $ignored) {
        }
    }

    public function testIdentityMap()
    {
        // fixtures
        $parent = null;
        for ($i = 0; $i < 3; $i++) {
            $page = new DDC1436Page();
            $page->setParent($parent);
            $this->em->persist($page);
            $parent = $page;
        }
        $this->em->flush();
        $this->em->clear();

        $id = $parent->getId();

        // step 1
        $page = $this->em
                ->createQuery('SELECT p, parent FROM ' . __NAMESPACE__ . '\DDC1436Page p LEFT JOIN p.parent parent WHERE p.id = :id')
                ->setParameter('id', $id)
                ->getOneOrNullResult();

        self::assertInstanceOf(DDC1436Page::class, $page);

        // step 2
        $page = $this->em->find(DDC1436Page::class, $id);
        self::assertInstanceOf(DDC1436Page::class, $page);
        self::assertInstanceOf(DDC1436Page::class, $page->getParent());
        self::assertInstanceOf(DDC1436Page::class, $page->getParent()->getParent());
    }
}

/**
 * @ORM\Entity
 */
class DDC1436Page
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", name="id")
     */
    protected $id;
    /**
     * @ORM\ManyToOne(targetEntity="DDC1436Page")
     * @ORM\JoinColumn(name="pid", referencedColumnName="id")
     */
    protected $parent;

    public function getId()
    {
        return $this->id;
    }

    /**
     * @return DDC1436Page
     */
    public function getParent()
    {
        return $this->parent;
    }

    public function setParent($parent)
    {
        $this->parent = $parent;
    }
}

