<?php

namespace Doctrine\Tests\Models\Issue6976;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @Entity
 * @Table(name="issue6976_project")
 */
class Issue6976Project
{
    /**
     * @Id()
     * @Column(type="integer")
     * @GeneratedValue()
     *
     * @var int
     */
    private $id;

    /**
     * @var string
     * @Column(name="title")
     */
    private $title;

    /**
     * @OneToMany(targetEntity="Doctrine\Tests\Models\Issue6976\Issue6976Issue", mappedBy="project", cascade={"persist"}, orphanRemoval=true)
     *
     * @var Issue6976Issue[]|Collection
     */
    private $issues;

    /**
     * Issue6976Project constructor.
     */
    public function __construct()
    {
        $this->issues = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     *
     * @return Issue6976Project
     */
    public function setTitle(string $title): Issue6976Project
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return Collection|Issue6976Issue[]
     */
    public function getIssues()
    {
        return $this->issues;
    }

    /**
     * @param Collection|Issue6976Issue[] $issues
     *
     * @return Issue6976Project
     */
    public function setIssues(iterable $issues)
    {
        $this->issues = $issues;

        return $this;
    }

}
