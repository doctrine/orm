<?php

namespace Doctrine\Tests\Models\Cache;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table("cache_attraction_contact_info")
 */
class AttractionContactInfo extends AttractionInfo
{
    /**
     * @ORM\Column(unique=true)
     */
    protected $fone;

    public function __construct($fone, Attraction $attraction)
    {
        $this->setAttraction($attraction);
        $this->setFone($fone);
    }

    public function getFone()
    {
        return $this->fone;
    }

    public function setFone($fone)
    {
        $this->fone = $fone;
    }
}
