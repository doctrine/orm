<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

/**
 * @group Issue-5801
 */
class Issue5801Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->_schemaTool->createSchema(
            [
            $this->_em->getClassMetadata(Issue5801User::class),
            ]
        );
    }

    public function testIssue()
    {
        $user = new Issue5801User();
        $user->id = 1;

        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();

        $dql = 'SELECT CASE WHEN u.id > 0 THEN u.id ELSE NULL END as case_expr_result FROM ' . Issue5801User::class . ' u';
        $result = $this->_em->createQuery($dql)
            ->getSingleResult();

        self::assertEquals(1, $result['case_expr_result']);

        $dql = 'SELECT CASE WHEN u.id > 0 THEN NULL ELSE u.id END as case_expr_result FROM ' . Issue5801User::class . ' u';
        $result = $this->_em->createQuery($dql)
            ->getSingleResult();

        self::assertEquals(null, $result['case_expr_result']);
    }
}

/** @Entity */
class Issue5801User
{
    /** @Id @Column(type="integer") */
    public $id;
}
