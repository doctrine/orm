<?php

namespace Doctrine\Tests\ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\NotifyPropertyChanged;
use Doctrine\Common\PropertyChangedListener;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\ORMException;
use Doctrine\Tests\Mocks\ConnectionMock;
use Doctrine\Tests\Mocks\DriverMock;
use Doctrine\Tests\Mocks\EntityManagerMock;
use Doctrine\Tests\Mocks\EntityPersisterMock;
use Doctrine\Tests\Mocks\UnitOfWorkMock;
use Doctrine\Tests\Models\CMS\CmsPhonenumber;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\Forum\ForumAvatar;
use Doctrine\Tests\Models\Forum\ForumUser;
use Doctrine\Tests\Models\GeoNames\City;
use Doctrine\Tests\Models\GeoNames\Country;
use Doctrine\Tests\OrmTestCase;
use stdClass;

/**
 * @covers \Doctrine\ORM\ORMInvalidArgumentException
 */
class ORMInvalidArgumentExceptionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider invalidEntityNames
     *
     * @param mixed  $value
     * @param string $expectedMessage
     *
     * @return void
     */
    public function testInvalidEntityName($value, $expectedMessage)
    {
        $exception = ORMInvalidArgumentException::invalidEntityName($value);

        self::assertInstanceOf(ORMInvalidArgumentException::class, $exception);
        self::assertSame($expectedMessage, $exception->getMessage());
    }

    /**
     * @return string[][]
     */
    public function invalidEntityNames()
    {
        return [
            [null, 'Entity name must be a string, NULL given'],
            [true, 'Entity name must be a string, boolean given'],
            [123, 'Entity name must be a string, integer given'],
            [123.45, 'Entity name must be a string, double given'],
            [new \stdClass(), 'Entity name must be a string, object given'],
        ];
    }
}
