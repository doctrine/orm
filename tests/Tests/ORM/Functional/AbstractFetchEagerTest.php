<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\AbstractFetchEager\AbstractRemoteControl;
use Doctrine\Tests\Models\AbstractFetchEager\MobileRemoteControl;
use Doctrine\Tests\Models\AbstractFetchEager\User;
use Doctrine\Tests\OrmFunctionalTestCase;

final class AbstractFetchEagerTest extends OrmFunctionalTestCase
{
    public function testWithAbstractFetchEager(): void
    {
        $this->createSchemaForModels(
            AbstractRemoteControl::class,
            User::class,
        );

        $control = new MobileRemoteControl('smart');
        $user    = new User($control);

        $entityManage = $this->getEntityManager();

        $entityManage->persist($control);
        $entityManage->persist($user);
        $entityManage->flush();
        $entityManage->clear();

        $user = $entityManage->find(User::class, $user->id);

        self::assertNotNull($user);
        self::assertEquals('smart', $user->remoteControl->name);
        self::assertTrue($user->remoteControl->users->contains($user));
    }
}
