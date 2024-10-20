<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

/** @see https://github.com/doctrine/orm/issues/10889 */
#[Group('GH10889')]
class GH10889Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            GH10889Person::class,
            GH10889Company::class,
            GH10889Resume::class,
        );
    }

    public function testIssue(): void
    {
        $person = new GH10889Person();
        $resume = new GH10889Resume($person, null);

        $this->_em->persist($person);
        $this->_em->persist($resume);
        $this->_em->flush();
        $this->_em->clear();

        /** @var list<GH10889Resume> $resumes */
        $resumes = $this->_em
            ->getRepository(GH10889Resume::class)
            ->createQueryBuilder('resume')
            ->leftJoin('resume.currentCompany', 'company')->addSelect('company')
            ->getQuery()
            ->getResult();

        $this->assertArrayHasKey(0, $resumes);
        $this->assertEquals(1, $resumes[0]->person->id);
        $this->assertNull($resumes[0]->currentCompany);
    }
}

#[ORM\Entity]
class GH10889Person
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    public int|null $id = null;
}

#[ORM\Entity]
class GH10889Company
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    public int|null $id = null;
}

#[ORM\Entity]
class GH10889Resume
{
    public function __construct(
        #[ORM\Id]
        #[ORM\OneToOne]
        public GH10889Person $person,
        #[ORM\ManyToOne]
        public GH10889Company|null $currentCompany,
    ) {
    }
}
