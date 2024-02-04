<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ValueConversionType;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\Table;

/**
 * @Entity
 * @Table(name="vct_inversed_onetoone_compositeid")
 */
class InversedOneToOneCompositeIdEntity
{
    /**
     * @var string
     * @Column(type="rot13", length=255)
     * @Id
     */
    public $id1;

    /**
     * @var string
     * @Column(type="rot13", length=255)
     * @Id
     */
    public $id2;

    /**
     * @var string
     * @Column(type="string", length=255, name="some_property")
     */
    public $someProperty;

    /**
     * @var OwningOneToOneCompositeIdEntity
     * @OneToOne(targetEntity="OwningOneToOneCompositeIdEntity", mappedBy="associatedEntity")
     */
    public $associatedEntity;
}
