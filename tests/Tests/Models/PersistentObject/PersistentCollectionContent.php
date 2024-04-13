<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\PersistentObject;

use Doctrine\Common\Persistence\PersistentObject;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

/** @Entity */
class PersistentCollectionContent extends PersistentObject
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     * @var int
     */
    protected $id;
}
