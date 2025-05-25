<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\SwitchContextWithFilter;

use Doctrine\ORM\Query\Filter\SQLFilter;
use Doctrine\Tests\ORM\Functional\Ticket\SwitchContextWithFilter\Entity\Insurance;
use Doctrine\Tests\ORM\Functional\Ticket\SwitchContextWithFilter\Entity\Patient;
use Doctrine\Tests\ORM\Functional\Ticket\SwitchContextWithFilter\Entity\PatientInsurance;
use Doctrine\Tests\ORM\Functional\Ticket\SwitchContextWithFilter\Entity\Practice;
use Doctrine\Tests\ORM\Functional\Ticket\SwitchContextWithFilter\Entity\PrimaryPatInsurance;
use Doctrine\Tests\ORM\Functional\Ticket\SwitchContextWithFilter\Entity\SecondaryPatInsurance;
use Doctrine\Tests\ORM\Functional\Ticket\SwitchContextWithFilter\SQLFilter\PracticeContextSQLFilter;

final class SwitchContextTest extends AbstractTestCase
{
    /** @var SQLFilter|PracticeContextSQLFilter */
    private $sqlFilter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            Practice::class,
            Patient::class,
            PatientInsurance::class,
            PrimaryPatInsurance::class,
            SecondaryPatInsurance::class,
            Insurance::class,
        );

        $this->_em->getConfiguration()->addFilter(PracticeContextSQLFilter::class, PracticeContextSQLFilter::class);
        $this->sqlFilter = $this->_em->getFilters()->enable(PracticeContextSQLFilter::class);
    }

    /** @return array{Patient, Patient} */
    private function fixtureGenerate(): array
    {
        $practiceA        = new Practice('Practice A');
        $practiceB        = new Practice('Practice B');
        $insuranceAetna   = new Insurance($practiceA, 'Aetna in Practice A');
        $insuranceBHumana = new Insurance($practiceB, 'Humana in Practice B');
        $insuranceBCustom = new Insurance($practiceB, 'Custom in Practice B');

        $patientEgor = new Patient('Egor');
        $patientEgor->addPrimaryInsurance($insuranceAetna);
        $patientEgor->addPrimaryInsurance($insuranceBHumana);

        $patientGena = new Patient('Gena');
        $patientGena->addPrimaryInsurance($insuranceBHumana);
        $patientGena->addSecondaryInsurance($insuranceBCustom);

        $this->persistFlushClear(
            $practiceA,
            $practiceB,
            $insuranceAetna,
            $insuranceBHumana,
            $insuranceBCustom,
            $patientEgor,
            $patientGena,
        );

        return [
            $this->_em->getReference(Patient::class, $patientEgor->id),
            $this->_em->getReference(Patient::class, $patientGena->id),
        ];
    }

    /**
     * @param callable(): T $callback
     *
     * @return T
     *
     * @template T
     */
    private function switchPracticeContext(Practice $practice, callable $callback)
    {
        $this->sqlFilter->setParameter('practiceId', $practice->id);

        try {
            return $callback();
        } finally {
            $this->sqlFilter->setParameter('practiceId', null);
        }
    }

    public function testSwitchContext(): void
    {
        [$patientEgor, $patentGena] = $this->fixtureGenerate();

        $practiceA = $this->_em->getRepository(Practice::class)->findOneBy(['name' => 'Practice A']);
        $practiceB = $this->_em->getRepository(Practice::class)->findOneBy(['name' => 'Practice B']);

        $this->switchPracticeContext($practiceA, function () use ($patientEgor, $patentGena): void {
            $this->clearCachedData($patentGena, $patientEgor);

            self::assertCount(1, $patientEgor->insurances);
            self::assertInstanceOf(PrimaryPatInsurance::class, $patientEgor->getPrimaryInsurances()->first());
            self::assertEquals('Aetna in Practice A', $patientEgor->getPrimaryInsurances()->first()->insurance->name);

            self::assertCount(0, $patentGena->insurances);
        });

        $this->switchPracticeContext($practiceB, function () use ($patientEgor, $patentGena): void {
            $this->clearCachedData($patentGena, $patientEgor);

            self::assertCount(1, $patientEgor->insurances);
            self::assertInstanceOf(PrimaryPatInsurance::class, $patientEgor->getPrimaryInsurances()->first());
            self::assertEquals('Humana in Practice B', $patientEgor->getPrimaryInsurances()->first()->insurance->name);

            self::assertCount(2, $patentGena->insurances);
            self::assertInstanceOf(PrimaryPatInsurance::class, $patentGena->getPrimaryInsurances()->first());
            self::assertInstanceOf(SecondaryPatInsurance::class, $patentGena->getSecondaryInsurances()->first());
            self::assertEquals('Humana in Practice B', $patentGena->getPrimaryInsurances()->first()->insurance->name);
            self::assertEquals('Custom in Practice B', $patentGena->getSecondaryInsurances()->first()->insurance->name);
        });
    }
}
