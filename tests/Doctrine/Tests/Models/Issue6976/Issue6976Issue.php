<?php

namespace Doctrine\Tests\Models\Issue6976;

/**
 * @Entity
 * @Table(name="issue6976_issue")
 */
class Issue6976Issue
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
     * @Column(name="author")
     *
     * @var string
     */
    private $author;

    /**
     * @ManyToOne(targetEntity="Doctrine\Tests\Models\Issue6976\Issue6976Project", inversedBy="issues")
     *
     * @var Issue6976Project
     */
    private $project;

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
    public function getAuthor(): string
    {
        return $this->author;
    }

    /**
     * @param string $author
     *
     * @return Issue6976Issue
     */
    public function setAuthor(string $author): Issue6976Issue
    {
        $this->author = $author;

        return $this;
    }

    /**
     * @return Issue6976Project
     */
    public function getProject(): Issue6976Project
    {
        return $this->project;
    }

    /**
     * @param Issue6976Project $project
     *
     * @return Issue6976Issue
     */
    public function setProject(Issue6976Project $project): Issue6976Issue
    {
        $this->project = $project;

        return $this;
    }
}
