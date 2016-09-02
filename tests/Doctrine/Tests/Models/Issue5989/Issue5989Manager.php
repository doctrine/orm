<?php

namespace Doctrine\Tests\Models\Issue5989;

/**
 * @Entity
 * @Table(name="issue5989_managers")
 */
class Issue5989Manager extends Issue5989Person
{
    /**
     * @column(type="simple_array", nullable=true)
     *
     * @var array
     */
    private $tags;

    public function getTags()
    {
        return $this->tags;
    }

    public function setTags(array $tags)
    {
        $this->tags = $tags;
    }
}
