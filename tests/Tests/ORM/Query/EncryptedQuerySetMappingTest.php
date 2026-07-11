<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Query;

use Doctrine\ORM\Query\EncryptedQuerySetMapping;
use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsUser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(EncryptedQuerySetMapping::class)]
final class EncryptedQuerySetMappingTest extends TestCase
{
    public function testEmptyByDefault(): void
    {
        $eqsm = new EncryptedQuerySetMapping();

        self::assertTrue($eqsm->isEmpty());
        self::assertSame([], $eqsm->getEncryptedParameters());
    }

    public function testAddEncryptedParameter(): void
    {
        $eqsm = new EncryptedQuerySetMapping();

        $eqsm->addEncryptedParameter(1, CmsUser::class, 'name');
        $eqsm->addEncryptedParameter('city', CmsAddress::class, 'city');

        self::assertFalse($eqsm->isEmpty());
        self::assertSame(
            [
                1 => [CmsUser::class, 'name'],
                'city' => [CmsAddress::class, 'city'],
            ],
            $eqsm->getEncryptedParameters(),
        );
    }

    public function testAddEncryptedParameters(): void
    {
        $eqsm = new EncryptedQuerySetMapping();

        $eqsm->addEncryptedParameters([1, 3, 'name'], CmsUser::class, 'name');

        self::assertSame(
            [
                1 => [CmsUser::class, 'name'],
                3 => [CmsUser::class, 'name'],
                'name' => [CmsUser::class, 'name'],
            ],
            $eqsm->getEncryptedParameters(),
        );
    }

    public function testAddEncryptedParameterOverridesSamePosition(): void
    {
        $eqsm = new EncryptedQuerySetMapping();

        $eqsm->addEncryptedParameter(1, CmsUser::class, 'name');
        $eqsm->addEncryptedParameter(1, CmsAddress::class, 'city');

        self::assertSame([1 => [CmsAddress::class, 'city']], $eqsm->getEncryptedParameters());
    }
}
