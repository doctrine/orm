<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\Issue6976\Issue6976Issue;
use Doctrine\Tests\Models\Issue6976\Issue6976Project;

/**
 * @group issue-6976
 */
class Issue6976Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    const PROJECT_TITLE = 'project title';
    const AUTHOR_1 = 'author 1';
    const AUTHOR_2 = 'author 2';

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->useModelSet('issue6976');
        parent::setUp();

        $project = new Issue6976Project();
        $project->setTitle(self::PROJECT_TITLE);

        $firstIssue = new Issue6976Issue();
        $firstIssue->setAuthor(self::AUTHOR_1)
                   ->setProject($project);
        $this->_em->persist($firstIssue);

        $secondIssue = new Issue6976Issue();
        $secondIssue->setAuthor(self::AUTHOR_2)
                    ->setProject($project);
        $this->_em->persist($secondIssue);

        $project->setIssues([
            $firstIssue,
            $secondIssue,
        ]);

        $this->_em->persist($project);

        $this->_em->flush();
        $this->_em->clear();
    }

    /**
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testUpdateCollectionWithOrphanRemoval()
    {
        /** @var Issue6976Project $project */
        $project = $this->_em->getRepository(Issue6976Project::class)
                             ->findOneBy([
                                 'title' => self::PROJECT_TITLE,
                             ]);

        $filtered = [];
        foreach ($project->getIssues() as $issue) {
            if (self::AUTHOR_1 === $issue->getAuthor()) {
                $issue->setAuthor('new author');
                $filtered[] = $issue;
            } else {
                $this->_em->remove($issue);
            }
        }

        $project->setIssues($filtered);

        $this->_em->flush();
        $this->_em->clear();

        $project = $this->_em->getRepository(Issue6976Project::class)
                             ->findOneBy([
                                 'title' => self::PROJECT_TITLE,
                             ]);
        $this->assertCount(1, $project->getIssues());
    }
}
