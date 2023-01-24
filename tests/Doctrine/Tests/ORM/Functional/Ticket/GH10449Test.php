<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\Tests\OrmTestCase;

use function get_parent_class;
use function method_exists;

class GH10449Test extends OrmTestCase
{
    public function testToManyAssociationOnMappedSuperclassShallBeRejected(): void
    {
        $em      = $this->getTestEntityManager();
        $classes = [GH10449MappedSuperclass::class, GH10449Entity::class, GH10449ToManyAssociationTarget::class];

        $this->expectException(MappingException::class);
        $this->expectExceptionMessageMatches('/illegal to put an inverse side one-to-many or many-to-many association on mapped superclass/');

        foreach ($classes as $class) {
            $cm = $em->getClassMetadata($class);
        }
    }

    /**
     * Override for BC with PHPUnit <8
     */
    public function expectExceptionMessageMatches(string $regularExpression): void
    {
        if (method_exists(get_parent_class($this), 'expectExceptionMessageMatches')) {
            parent::expectExceptionMessageMatches($regularExpression);
        } else {
            parent::expectExceptionMessageRegExp($regularExpression);
        }
    }
}

/**
 * @ORM\Entity
 */
class GH10449ToManyAssociationTarget
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     *
     * @var int
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity="GH10449MappedSuperclass", inversedBy="targets")
     *
     * @var GH10449MappedSuperclass
     */
    public $base;
}

/**
 * @ORM\MappedSuperclass
 */
class GH10449MappedSuperclass
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     *
     * @var int
     */
    public $id;

    /**
     * @ORM\OneToMany(targetEntity="GH10449ToManyAssociationTarget", mappedBy="base")
     *
     * @var Collection
     */
    public $targets;
}

/**
 * @ORM\Entity
 */
class GH10449Entity extends GH10449MappedSuperclass
{
}
