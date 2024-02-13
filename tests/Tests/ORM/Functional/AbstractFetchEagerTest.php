<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\AbstractFetchEager\WithFetchEager\AbstractRemoveControl;
use Doctrine\Tests\Models\AbstractFetchEager\WithFetchEager\MobileRemoteControl;
use Doctrine\Tests\Models\AbstractFetchEager\WithFetchEager\User;
use Doctrine\Tests\Models\AbstractFetchEager\WithoutFetchEager\AbstractRemoveControl as AbstractRemoveControlWithoutFetchEager;
use Doctrine\Tests\Models\AbstractFetchEager\WithoutFetchEager\MobileRemoteControl as MobileRemoteControlWithoutFetchEager;
use Doctrine\Tests\Models\AbstractFetchEager\WithoutFetchEager\User as UserWithoutFetchEager;
use Doctrine\Tests\OrmFunctionalTestCase;

final class AbstractFetchEagerTest extends OrmFunctionalTestCase
{
    public function testWithAbstractFetchEager(): void
    {
        $this->createSchemaForModels(
            AbstractRemoveControl::class,
            User::class
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

    public function testWithoutAbstractFetchEager(): void
    {
        $this->createSchemaForModels(
            AbstractRemoveControlWithoutFetchEager::class,
            UserWithoutFetchEager::class
        );

        $control = new MobileRemoteControlWithoutFetchEager('smart');
        $user    = new UserWithoutFetchEager($control);

        $entityManage = $this->getEntityManager();

        $entityManage->persist($control);
        $entityManage->persist($user);
        $entityManage->flush();
        $entityManage->clear();

        $user = $entityManage->find(UserWithoutFetchEager::class, $user->id);

        self::assertNotNull($user);
        self::assertEquals('smart', $user->remoteControl->name);
        self::assertTrue($user->remoteControl->users->contains($user));
    }
}
