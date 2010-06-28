<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use DateTime;

require_once __DIR__ . '/../../../TestInit.php';

class DDC618Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC618Author')
            ));

            // Create author 10/Joe with two books 22/JoeA and 20/JoeB
            $author = new DDC618Author();
            $author->id = 10;
            $author->name = 'Joe';
            $this->_em->persist($author);

            // Create author 11/Alice with two books 21/AliceA and 23/AliceB
            $author = new DDC618Author();
            $author->id = 11;
            $author->name = 'Alice';
            $this->_em->persist($author);

            $this->_em->flush();
            $this->_em->clear();
        } catch(\Exception $e) {
            
        }
    }

    public function testIndexByHydrateObject()
    {
        $dql = 'SELECT A FROM Doctrine\Tests\ORM\Functional\Ticket\DDC618Author A INDEX BY A.name ORDER BY A.name ASC';
        $result = $this->_em->createQuery($dql)->getResult(\Doctrine\ORM\Query::HYDRATE_OBJECT);

        $joe    = $this->_em->find('Doctrine\Tests\ORM\Functional\Ticket\DDC618Author', 10);
        $alice  = $this->_em->find('Doctrine\Tests\ORM\Functional\Ticket\DDC618Author', 11);

        $this->assertArrayHasKey('Joe', $result, "INDEX BY A.name should return an index by the name of 'Joe'.");
        $this->assertArrayHasKey('Alice', $result, "INDEX BY A.name should return an index by the name of 'Alice'.");
    }

    public function testIndexByHydrateArray()
    {
        $dql = 'SELECT A FROM Doctrine\Tests\ORM\Functional\Ticket\DDC618Author A INDEX BY A.name ORDER BY A.name ASC';
        $result = $this->_em->createQuery($dql)->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);

        $joe    = $this->_em->find('Doctrine\Tests\ORM\Functional\Ticket\DDC618Author', 10);
        $alice  = $this->_em->find('Doctrine\Tests\ORM\Functional\Ticket\DDC618Author', 11);

        $this->assertArrayHasKey('Joe', $result, "INDEX BY A.name should return an index by the name of 'Joe'.");
        $this->assertArrayHasKey('Alice', $result, "INDEX BY A.name should return an index by the name of 'Alice'.");
    }
}

/**
 * @Entity
 * @Table (name="ddc618author", uniqueConstraints={ @Index (name="UQ_authorname", columns={ "name" }) })
 */
class DDC618Author
{
    /**
     * @Id
     * @Column(type="integer")
     */
    public $id;

    /** @Column(type="string") */
    public $name;

    public function __construct()
    {
        $this->books = new \Doctrine\Common\Collections\ArrayCollection;
    }
}