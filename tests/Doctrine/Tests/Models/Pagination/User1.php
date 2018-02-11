<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Pagination;

use Doctrine\ORM\Annotation as ORM;

/**
 * Class User1
 *
 * @ORM\Entity()
 */
class User1 extends User
{
    /** @ORM\Column(type="string") */
    public $email;
}
