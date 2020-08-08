<?php
namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Annotation\AttributeOverrides;
use Doctrine\ORM\Annotation\AttributeOverride;
use Doctrine\ORM\Annotation\Column;
use Doctrine\ORM\Annotation\DiscriminatorColumn;
use Doctrine\ORM\Annotation\DiscriminatorMap;
use Doctrine\ORM\Annotation\Entity;
use Doctrine\ORM\Annotation\Id;
use Doctrine\ORM\Annotation\InheritanceType;
use Doctrine\ORM\Annotation\Table;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group GH-8229
 */
class GH8229Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEntitySchema([GH8229Resource::class, GH8229User::class]);
    }

    public function testCorrectColumnNameInParentClassAfterAttributeOveride()
    {
        // Test creation
        $entity = new GH8229User('foo');
        $identifier = $entity->id;
        $this->em->persist($entity);
        $this->em->flush();
        $this->em->clear();

        // Test reading
        $entity = $this->em->getRepository(GH8229User::class)->find($identifier);
        self::assertEquals($identifier, $entity->id);

        // Test update
        $entity->username = 'bar';
        $this->em->persist($entity);
        $this->em->flush();
        $this->em->clear();
        $entity = $this->em->getRepository(GH8229User::class)->find($identifier);
        self::assertEquals('bar', $entity->username);

        // Test deletion
        $this->em->remove($entity);
        $this->em->flush();
        $this->em->clear();
    }
}

/**
 * @Entity
 * @Table(name="gh8229_resource")
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="resource_type", type="string", length=191)
 * @DiscriminatorMap({
 *     "resource"=GH8229Resource::class,
 *     "user"=GH8229User::class,
 * })
 */
abstract class GH8229Resource
{
    /**
     * @Id()
     * @Column(name="resource_id", type="integer")
     */
    public $id;

    private static $sequence = 0;

    protected function __construct()
    {
        $this->id = ++self::$sequence;
    }
}

/**
 * @Entity
 * @Table(name="gh8229_user")
 * @AttributeOverrides({@AttributeOverride(name="id", column=@Column(name="user_id", type="integer"))})
 */
final class GH8229User extends GH8229Resource
{
    /**
     * @Column(type="string", name="username", length=191, nullable=false)
     */
    public $username;

    public function __construct($username)
    {
        parent::__construct();

        $this->username = $username;
    }
}
