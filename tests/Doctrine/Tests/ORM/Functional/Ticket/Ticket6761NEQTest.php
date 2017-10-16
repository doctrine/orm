<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\OrmFunctionalTestCase;

class Ticket6761NEQTest extends OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->_schemaTool->createSchema(
            [
                $this->_em->getClassMetadata(TestEntity::class),
            ]
        );
    }

    public function testIssue()
    {
        $testEntity = new TestEntity();
        $testEntity->setName('test name');
        $testEntity->setEmail('test@email.com');
        $testEntity->setUnsubscribed(null);

        $this->_em->persist($testEntity);
        $this->_em->flush();
        $this->_em->clear();

        $queryBuilder = $this->_em->getRepository(TestEntity::class)->createQueryBuilder('t');

        $queryBuilder->select('t.id');
        $queryBuilder->andWhere(
            $queryBuilder->expr()->neq(
                't.unsubscribed',
                $queryBuilder->expr()->literal(true)
            )
        );

        $query  = $queryBuilder->getQuery();
        $result = $query->getResult();

        $this->assertEquals(count($result), 1);
    }
}

/**
 * @Entity
 */
class TestEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     * @var int
     */
    protected $id;

    /**
     * @Column(type="string")
     * @var string
     */
    protected $name;

    /**
     * @Column(type="string")
     * @var string
     */
    protected $email;

    /**
     * @Column(type="boolean", nullable=true)
     * @var bool
     */
    protected $unsubscribed;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return bool
     */
    public function isUnsubscribed()
    {
        return $this->unsubscribed;
    }

    /**
     * @param bool $unsubscribed
     */
    public function setUnsubscribed($unsubscribed)
    {
        $this->unsubscribed = $unsubscribed;
    }
}
