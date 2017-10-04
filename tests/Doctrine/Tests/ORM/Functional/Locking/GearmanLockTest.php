<?php

namespace Doctrine\Tests\ORM\Functional\Locking;

use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\DBAL\LockMode;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group locking_functional
 */
class GearmanLockTest extends OrmFunctionalTestCase
{
    private $gearman = null;
    private $maxRunTime = 0;
    private $articleId;

    protected function setUp()
    {
        if (!class_exists('GearmanClient', false)) {
            $this->markTestSkipped('pecl/gearman is required for this test to run.');
        }

        $this->useModelSet('cms');
        parent::setUp();
        $this->tasks = [];

        $this->gearman = new \GearmanClient();
        $this->gearman->addServer(
            $_SERVER['GEARMAN_HOST'] ?? null,
            $_SERVER['GEARMAN_PORT'] ?? 4730
        );
        $this->gearman->setCompleteCallback([$this, "gearmanTaskCompleted"]);

        $article = new CmsArticle();
        $article->text = "my article";
        $article->topic = "Hello";

        $this->_em->persist($article);
        $this->_em->flush();

        $this->articleId = $article->id;
    }

    public function gearmanTaskCompleted($task)
    {
        $this->maxRunTime = max($this->maxRunTime, $task->data());
    }

    public function testFindWithLock()
    {
        $this->asyncFindWithLock(CmsArticle::class, $this->articleId, LockMode::PESSIMISTIC_WRITE);
        $this->asyncFindWithLock(CmsArticle::class, $this->articleId, LockMode::PESSIMISTIC_WRITE);

        $this->assertLockWorked();
    }

    public function testFindWithWriteThenReadLock()
    {
        $this->asyncFindWithLock(CmsArticle::class, $this->articleId, LockMode::PESSIMISTIC_WRITE);
        $this->asyncFindWithLock(CmsArticle::class, $this->articleId, LockMode::PESSIMISTIC_READ);

        $this->assertLockWorked();
    }

    public function testFindWithReadThenWriteLock()
    {
        $this->asyncFindWithLock(CmsArticle::class, $this->articleId, LockMode::PESSIMISTIC_READ);
        $this->asyncFindWithLock(CmsArticle::class, $this->articleId, LockMode::PESSIMISTIC_WRITE);

        $this->assertLockWorked();
    }

    public function testFindWithOneLock()
    {
        $this->asyncFindWithLock(CmsArticle::class, $this->articleId, LockMode::PESSIMISTIC_WRITE);
        $this->asyncFindWithLock(CmsArticle::class, $this->articleId, LockMode::NONE);

        $this->assertLockDoesNotBlock();
    }

    public function testDqlWithLock()
    {
        $this->asyncDqlWithLock('SELECT a FROM Doctrine\Tests\Models\CMS\CmsArticle a', [], LockMode::PESSIMISTIC_WRITE);
        $this->asyncFindWithLock(CmsArticle::class, $this->articleId, LockMode::PESSIMISTIC_WRITE);

        $this->assertLockWorked();
    }

    public function testLock()
    {
        $this->asyncFindWithLock(CmsArticle::class, $this->articleId, LockMode::PESSIMISTIC_WRITE);
        $this->asyncLock(CmsArticle::class, $this->articleId, LockMode::PESSIMISTIC_WRITE);

        $this->assertLockWorked();
    }

    public function testLock2()
    {
        $this->asyncFindWithLock(CmsArticle::class, $this->articleId, LockMode::PESSIMISTIC_WRITE);
        $this->asyncLock(CmsArticle::class, $this->articleId, LockMode::PESSIMISTIC_READ);

        $this->assertLockWorked();
    }

    public function testLock3()
    {
        $this->asyncFindWithLock(CmsArticle::class, $this->articleId, LockMode::PESSIMISTIC_READ);
        $this->asyncLock(CmsArticle::class, $this->articleId, LockMode::PESSIMISTIC_WRITE);

        $this->assertLockWorked();
    }

    public function testLock4()
    {
        $this->asyncFindWithLock(CmsArticle::class, $this->articleId, LockMode::NONE);
        $this->asyncLock(CmsArticle::class, $this->articleId, LockMode::PESSIMISTIC_WRITE);

        $this->assertLockDoesNotBlock();
    }

    protected function assertLockDoesNotBlock()
    {
        $this->assertLockWorked($onlyForSeconds = 1);
    }

    protected function assertLockWorked($forTime = 2, $notLongerThan = null)
    {
        if ($notLongerThan === null) {
            $notLongerThan = $forTime + 1;
        }

        $this->gearman->runTasks();

        $this->assertTrue($this->maxRunTime > $forTime,
            "Because of locking this tests should have run at least " . $forTime . " seconds, ".
            "but only did for " . $this->maxRunTime . " seconds.");
        $this->assertTrue($this->maxRunTime < $notLongerThan,
            "The longest task should not run longer than " . $notLongerThan . " seconds, ".
            "but did for " . $this->maxRunTime . " seconds."
        );
    }

    protected function asyncFindWithLock($entityName, $entityId, $lockMode)
    {
        $this->startJob('findWithLock', [
            'entityName' => $entityName,
            'entityId' => $entityId,
            'lockMode' => $lockMode,
        ]
        );
    }

    protected function asyncDqlWithLock($dql, $params, $lockMode)
    {
        $this->startJob('dqlWithLock', [
            'dql' => $dql,
            'dqlParams' => $params,
            'lockMode' => $lockMode,
        ]
        );
    }

    protected function asyncLock($entityName, $entityId, $lockMode)
    {
        $this->startJob('lock', [
            'entityName' => $entityName,
            'entityId' => $entityId,
            'lockMode' => $lockMode,
        ]
        );
    }

    protected function startJob($fn, $fixture)
    {
        $this->gearman->addTask($fn, serialize(
            [
            'conn' => $this->_em->getConnection()->getParams(),
            'fixture' => $fixture
            ]
        ));

        $this->assertEquals(GEARMAN_SUCCESS, $this->gearman->returnCode());
    }
}
