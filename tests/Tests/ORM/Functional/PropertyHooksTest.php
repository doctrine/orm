<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\PropertyHooks\User;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

#[RequiresPhp('>= 8.4.0')]
class PropertyHooksTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->_em->getConfiguration()->isNativeLazyObjectsEnabled()) {
            $this->markTestSkipped('Property hooks require native lazy objects to be enabled.');
        }

        $this->createSchemaForModels(
            User::class,
        );
    }

    public function testMapPropertyHooks(): void
    {
        $user           = new User();
        $user->fullName = 'John Doe';
        $user->language = 'EN';

        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();

        $user = $this->_em->find(User::class, $user->id);

        self::assertEquals('John', $user->first);
        self::assertEquals('Doe', $user->last);
        self::assertEquals('John Doe', $user->fullName);
        self::assertEquals('EN', $user->language, 'The property hook uppercases the language.');

        $language = $this->_em->createQuery('SELECT u.language FROM ' . User::class . ' u WHERE u.id = :id')
            ->setParameter('id', $user->id)
            ->getSingleScalarResult();

        $this->assertEquals('en', $language, 'Selecting a field from DQL does not go through the property hook, accessing raw data.');
    }

    public function testTriggerLazyLoadingWhenAccessingPropertyHooks(): void
    {
        $user           = new User();
        $user->fullName = 'Ludwig von Beethoven';
        $user->language = 'DE';

        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();

        $user = $this->_em->getReference(User::class, $user->id);

        self::assertEquals('Ludwig', $user->first);
        self::assertEquals('von Beethoven', $user->last);
        self::assertEquals('Ludwig von Beethoven', $user->fullName);
        self::assertEquals('DE', $user->language, 'The property hook uppercases the language.');
    }
}
