<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group DDC-1300
 */
class DDC1300Test extends OrmFunctionalTestCase
{
    public function setUp() : void
    {
        parent::setUp();

        $this->schemaTool->createSchema(
            [
                $this->em->getClassMetadata(DDC1300Foo::class),
                $this->em->getClassMetadata(DDC1300FooLocale::class),
            ]
        );
    }

    public function testIssue() : void
    {
        $foo               = new DDC1300Foo();
        $foo->fooReference = 'foo';

        $this->em->persist($foo);
        $this->em->flush();

        $locale         = new DDC1300FooLocale();
        $locale->foo    = $foo;
        $locale->locale = 'en';
        $locale->title  = 'blub';

        $this->em->persist($locale);
        $this->em->flush();

        $query  = $this->em->createQuery('SELECT f, fl FROM Doctrine\Tests\ORM\Functional\Ticket\DDC1300Foo f JOIN f.fooLocaleRefFoo fl');
        $result =  $query->getResult();

        self::assertCount(1, $result);
    }
}

/**
 * @ORM\Entity
 */
class DDC1300Foo
{
    /**
     * @ORM\Column(name="fooID", type="integer", nullable=false)
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Id
     *
     * @var int fooID
     */
    public $fooID;

    /**
     * @ORM\Column(name="fooReference", type="string", nullable=true, length=45)
     *
     * @var string fooReference
     */
    public $fooReference;

    /**
     * @ORM\OneToMany(targetEntity=DDC1300FooLocale::class, mappedBy="foo",
     * cascade={"persist"})
     */
    public $fooLocaleRefFoo;

    /**
     * Constructor
     *
     * @param array|Zend_Config|null $options
     */
    public function __construct($options = null)
    {
        $this->fooLocaleRefFoo = new ArrayCollection();
    }
}

/**
 * @ORM\Entity
 */
class DDC1300FooLocale
{
    /**
     * @ORM\ManyToOne(targetEntity=DDC1300Foo::class)
     * @ORM\JoinColumn(name="fooID", referencedColumnName="fooID")
     * @ORM\Id
     */
    public $foo;

    /**
     * @ORM\Column(name="locale", type="string", nullable=false, length=5)
     * @ORM\Id
     *
     * @var string locale
     */
    public $locale;

    /**
     * @ORM\Column(name="title", type="string", nullable=true, length=150)
     *
     * @var string title
     */
    public $title;
}
