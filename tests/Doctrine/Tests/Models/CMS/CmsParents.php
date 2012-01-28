<?php

namespace Doctrine\Tests\Models\CMS;

/**
 * @Embeddable
 */
class CmsParents
{
    /**
     * @Column(length=250)
     */
    public $father;
    /**
     * @Column(length=250)
     */
    public $mother;

    public function setFather($father) {
        $this->father = $father;
    }

    public function getFather() {
        return $this->father;
    }

    public function setMother($mother) {
        $this->mother = $mother;
    }

    public function getMother() {
        return $this->mother;
    }
}
