<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;
use UnexpectedValueException;

class IdentifierFunctionalTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('cms');

        parent::setUp();
    }

    public function testIdentifierArrayValue(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Unexpected identifier value: Expecting scalar or Stringable, got array.');
        $this->_em->find(CmsUser::class, ['id' => ['array']]);
    }
}
