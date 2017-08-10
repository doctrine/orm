<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC964;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\Mapping;

/**
 * @ORM\Entity
 * @ORM\AttributeOverrides({
 *      @ORM\AttributeOverride(
 *          name="id",
 *          column=@ORM\Column(
 *              name = "guest_id",
 *              type = "integer"
 *          )
 *      ),
 *      @ORM\AttributeOverride(
 *          name="name",
 *          column=@ORM\Column(
 *              name     = "guest_name",
 *              type     = "string",
 *              nullable = false,
 *              unique   = true,
                length   = 240
 *          )
 *      )
 * })
 */
class DDC964Guest extends DDC964User
{
}
