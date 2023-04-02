<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Doctrine\Tests\OrmFunctionalTestCase;

use function array_map;
use function implode;
use function sprintf;

use const PHP_EOL;

final class SQLFilterWithFetchEAGERTest extends OrmFunctionalTestCase
{
    private function createModels(): void
    {
        $company      = new Company(1);
        $patient      = new Patient(1, $company);
        $insurance    = new Insurance(1, $company, 'name1');
        $patInsurance = new PatientInsurance(1, $patient, $insurance, 'test1');

        $company2      = new Company(2);
        $patient2      = new Patient(2, $company2);
        $insurance2    = new Insurance(2, $company2, 'name2');
        $patInsurance2 = new PatientInsurance(2, $patient2, $insurance2, 'test1');

        $this->_em->persist($company);
        $this->_em->persist($patient);
        $this->_em->persist($patInsurance);
        $this->_em->persist($insurance);

        $this->_em->persist($company2);
        $this->_em->persist($patient2);
        $this->_em->persist($patInsurance2);
        $this->_em->persist($insurance2);

        $this->_em->flush();
        $this->_em->clear();
    }

    private function switchCompanyContext(int $companyId, callable $handle): void
    {
        $this->_em->getFilters()->enable('company')->setParameter('companyId', $companyId);

        $handle();

        $this->_em->getFilters()->disable('company');
    }

    public function testChangeSQLFilterParametersWithFetchEagerCollection(): void
    {
        $this->createSchemaForModels(Company::class, Patient::class, PatientInsurance::class, Insurance::class);
        $this->_em->getConfiguration()->addFilter('company', CompanyFilter::class);

        $this->createModels();
        $this->switchCompanyContext(1, function (): void {
            $company1 = $this->_em->find(Company::class, 1);
            $company2 = $this->_em->find(Company::class, 2);

            self::assertInstanceOf(Company::class, $company1, $this->getLastQuery());
            self::assertNull($company2, $this->getLastQuery());

            $patient1 = $this->_em->find(Patient::class, 1);
            $patient2 = $this->_em->find(Patient::class, 2);

            self::assertInstanceOf(Patient::class, $patient1, $this->getLastQuery());
            self::assertNull($patient2, $this->getLastQuery());

            $patInsurance = $patient1->patInsurance->toArray();
            self::assertCount(1, $patInsurance, $this->getLastQuery());
            self::assertEquals(1, $patInsurance[0]->insurance->id, $this->getLastQuery());
        });

        $this->_em->clear();

        $this->switchCompanyContext(2, function (): void {
            $company1 = $this->_em->find(Company::class, 1);
            $company2 = $this->_em->find(Company::class, 2);

            self::assertNull($company1, $this->getLastQuery());
            self::assertInstanceOf(Company::class, $company2, $this->getLastQuery());

            $patient1 = $this->_em->find(Patient::class, 1);
            $patient2 = $this->_em->find(Patient::class, 2);

            self::assertNull($patient1, $this->getLastQuery());
            self::assertInstanceOf(Patient::class, $patient2, $this->getLastQuery());

            $patInsurance = $patient2->patInsurance->toArray();
            self::assertCount(1, $patInsurance, $this->getLastQuery());
            self::assertEquals(2, $patInsurance[0]->insurance->id, $this->getLastQuery());
        });
    }

    private function getLastQuery(): string
    {
        $querys                       = $this->getQueryLog()->queries;
        $this->getQueryLog()->queries = [];

        return implode(
            PHP_EOL,
            array_map(
                static function ($query) {
                    return sprintf(
                        '%s [params: %s], [types: %s]',
                        $query['sql'],
                        implode(', ', $query['params'] ?? []),
                        implode(', ', $query['types'] ?? [])
                    );
                },
                $querys
            )
        );
    }
}

final class CompanyFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
    {
        if (! $this->hasParameter('companyId')) {
            return '';
        }

        switch ($targetEntity->getName()) {
            case Company::class:
                return $targetTableAlias . '.id = ' . $this->getParameter('companyId');

            case Patient::class:
            case Insurance::class:
                return $targetTableAlias . '.company = ' . $this->getParameter('companyId');

            default:
                return '';
        }
    }
}

/**
 * @ORM\Entity
 * @ORM\Table(name="Company_Master")
 */
class Company
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @var int
     */
    public $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }
}

/**
 * @ORM\Entity()
 * @ORM\Table(name="Insurance_Master")
 */
class Insurance
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @var int
     */
    public $id;

    /**
     * @ORM\JoinColumn(name="company")
     * @ORM\ManyToOne(targetEntity="Company")
     *
     * @var Company
     */
    public $company;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    public $name;

    public function __construct(int $id, Company $company, string $name)
    {
        $this->id      = $id;
        $this->company = $company;
        $this->name    = $name;
    }
}

/**
 * @ORM\Entity()
 * @ORM\Table(name="Patient_Master")
 */
class Patient
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @var int
     */
    public $id;

    /**
     * @ORM\OneToMany(targetEntity="PatientInsurance", mappedBy="patient")
     *
     * @var Collection<int, PatientInsurance>
     */
    public $patInsurance;

    /**
     * @ORM\JoinColumn(name="company")
     * @ORM\ManyToOne(targetEntity="Company")
     *
     * @var Company
     */
    public $company;

    public function __construct(int $id, Company $company)
    {
        $this->id           = $id;
        $this->company      = $company;
        $this->patInsurance = new ArrayCollection();
    }
}

/**
 * @ORM\Entity
 * @ORM\Table(name="Patient_Insurance")
 */
class PatientInsurance
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @var int
     */
    public $id;

    /**
     * @ORM\JoinColumn(nullable=false)
     * @ORM\ManyToOne(targetEntity="Patient", inversedBy="patInsurance")
     *
     * @var Patient
     */
    public $patient;

    /**
     * @ORM\JoinColumn(nullable=false)
     * @ORM\ManyToOne(targetEntity="Insurance", fetch="EAGER")
     *
     * @var Insurance
     */
    public $insurance;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    public $policy;

    public function __construct(int $id, Patient $patient, Insurance $insurance, string $policy)
    {
        $this->id        = $id;
        $this->patient   = $patient;
        $this->insurance = $insurance;
        $this->policy    = $policy;

        $patient->patInsurance->add($this);
    }
}
