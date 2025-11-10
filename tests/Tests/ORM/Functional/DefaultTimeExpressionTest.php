<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use DateTime;
use DateTimeImmutable;
use Doctrine\Deprecations\PHPUnit\VerifyDeprecations;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

class DefaultTimeExpressionTest extends OrmFunctionalTestCase
{
    use VerifyDeprecations;

    public function testUsingTimeRelatedDefaultExpressionCausesNoDbalDeprecation(): void
    {
        $this->expectNoDeprecationWithIdentifier('https://github.com/doctrine/dbal/pull/7195');

        $this->createSchemaForModels(TimeEntity::class);
    }
}

#[ORM\Entity]
class TimeEntity
{
    #[ORM\Id]
    #[ORM\Column]
    public int $id;

    #[ORM\Column(options: ['default' => 'CURRENT_TIMESTAMP'])]
    public DateTime $createdAt;

    #[ORM\Column(options: ['default' => 'CURRENT_TIMESTAMP'])]
    public DateTimeImmutable $createdAtImmutable;

    #[ORM\Column(options: ['default' => 'CURRENT_TIME'])]
    public DateTime $createdTime;

    #[ORM\Column(options: ['default' => 'CURRENT_DATE'])]
    public DateTime $createdDate;
}
