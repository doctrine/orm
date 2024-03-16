<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\AbstractFetchEager;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class User
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity="AbstractRemoveControl", inversedBy="users")
     * @ORM\JoinColumn(nullable=false)
     *
     * @var AbstractRemoveControl
     */
    public $remoteControl;

    public function __construct(AbstractRemoveControl $control)
    {
        $this->remoteControl = $control;
    }
}
