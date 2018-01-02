<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ProxySpecifics;

use Doctrine\ORM\Annotation as ORM;

/** @ORM\Entity */
class FuncGetArgs
{
    /** @ORM\Id @ORM\Column(type="integer") */
    public $id;

    public function funcGetArgsCallingMethod() : array
    {
        return \func_get_args();
    }
}
