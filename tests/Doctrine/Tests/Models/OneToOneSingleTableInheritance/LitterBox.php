<?php

namespace Doctrine\Tests\Models\OneToOneSingleTableInheritance;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="one_to_one_single_table_inheritance_litter_box")
 */
class LitterBox
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue(strategy="AUTO") */
    public $id;
}
