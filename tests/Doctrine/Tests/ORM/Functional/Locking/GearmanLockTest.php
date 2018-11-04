<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Locking;

use Doctrine\DBAL\LockMode;
use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\OrmFunctionalTestCase;
use GearmanClient;
use const GEARMAN_SUCCESS;
use function class_exists;
use function max;
use function serialize;

/**
 * @group locking_functional
 */
class GearmanLockTest extends OrmFunctionalTestCase
{
    private $gearman;
    private $maxRunTime = 0;
    private $articleId;

    protected function setUp() : void
    {
        if (! class_exists('GearmanClient', false)) {
            $this->markTestSkipped('pecl/gearman is required for this test to run.');
        }

        $this->useModelSet('cms');
        parent::setUp();
        $this->tasks = [];

        $this->gearman = new GearmanClient();
        $this->gearman->addServer(
            $_SERVER['GEARMAN_HOST'] ?? null,
            $_SERVER['GEARMAN_PORT'] ?? 4730
        );
        $this->gearman->setCompleteCallback([$this, 'gearmanTaskCompleted']);

        $article        = new CmsArticle();
        $article->text  = 'my article';
        $article->topic = 'Hello';

        $this->em->persist($article);
        $this->em->flush();

        $this->articleId = $article->id;
    }

    public function gearmanTaskCompleted($task)
    {
        $this->maxRunTime = max($this->maxRunTime, $task->data());
    }

    public function testFindWithLock() : void
    {
        $this->asyncFindWithLock(CmsArticle::class, $this->articleId, LockMode::PESSIMISTIC_WRITE);
        $this->asyncFindWithLock(CmsArticle::class, $this->articleId, LockMode::PESSIMISTIC_WRITE);

        self::assertLockWorked();
    }

    public function testFindWithWriteThenReadLock() : void
    {
        $this->asyncFindWithLock(CmsArticle::class, $this->articleId, LockMode::PESSIMISTIC_WRITE);
        $this->asyncFindWithLock(CmsArticle::class, $this->articleId, LockMode::PESSIMISTIC_READ);

        self::assertLockWorked();
    }

    public function testFindWithReadThenWriteLock() : void
    {
        $this->asyncFindWithLock(CmsArticle::class, $this->articleId, LockMode::PESSIMISTIC_READ);
        $this->asyncFindWithLock(CmsArticle::class, $this->articleId, LockMode::PESSIMISTIC_WRITE);

        self::assertLockWorked();
    }

    public function testFindWithOneLock() : void
    {
        $this->asyncFindWithLock(CmsArticle::class, $this->articleId, LockMode::PESSIMISTIC_WRITE);
        $this->asyncFindWithLock(CmsArticle::class, $this->articleId, LockMode::NONE);

        self::assertLockDoesNotBlock();
    }

    public function testDqlWithLock() : void
    {
        $this->asyncDqlWithLock('SELECT a FROM Doctrine\Tests\Models\CMS\CmsArticle a', [], LockMode::PESSIMISTIC_WRITE);
        $this->asyncFindWithLock(CmsArticle::class, $this->articleId, LockMode::PESSIMISTIC_WRITE);

        self::assertLockWorked();
    }

    public function testLock() : void
    {
        $this->asyncFindWithLock(CmsArticle::class, $this->articleId, LockMode::PESSIMISTIC_WRITE);
        $this->asyncLock(CmsArticle::class, $this->articleId, LockMode::PESSIMISTIC_WRITE);

        self::assertLockWorked();
    }

    public function testLock2() : void
    {
        $this->asyncFindWithLock(CmsArticle::class, $this->articleId, LockMode::PESSIMISTIC_WRITE);
        $this->asyncLock(CmsArticle::class, $this->articleId, LockMode::PESSIMISTIC_READ);

        self::assertLockWorked();
    }

    public function testLock3() : void
    {
        $this->asyncFindWithLock(CmsArticle::class, $this->articleId, LockMode::PESSIMISTIC_READ);
        $this->asyncLock(CmsArticle::class, $this->articleId, LockMode::PESSIMISTIC_WRITE);

        self::assertLockWorked();
    }

    public function testLock4() : void
    {
        $this->asyncFindWithLock(CmsArticle::class, $this->articleId, LockMode::NONE);
        $this->asyncLock(CmsArticle::class, $this->articleId, LockMode::PESSIMISTIC_WRITE);

        self::assertLockDoesNotBlock();
    }

    protected function assertLockDoesNotBlock()
    {
        self::assertLockWorked($onlyForSeconds = 1);
    }

    protected function assertLockWorked($forTime = 2, $notLongerThan = null)
    {
        if ($notLongerThan === null) {
            $notLongerThan = $forTime + 1;
        }

        $this->gearman->runTasks();

        self::assertTrue(
            $this->maxRunTime > $forTime,
            'Because of locking this tests should have run at least ' . $forTime . ' seconds, ' .
            'but only did for ' . $this->maxRunTime . ' seconds.'
        );
        self::assertTrue(
            $this->maxRunTime < $notLongerThan,
            'The longest task should not run longer than ' . $notLongerThan . ' seconds, ' .
            'but did for ' . $this->maxRunTime . ' seconds.'
        );
    }

    protected function asyncFindWithLock($entityName, $entityId, $lockMode)
    {
        $this->startJob('findWithLock', [
            'entityName' => $entityName,
            'entityId' => $entityId,
            'lockMode' => $lockMode,
        ]);
    }

    protected function asyncDqlWithLock($dql, $params, $lockMode)
    {
        $this->startJob('dqlWithLock', [
            'dql' => $dql,
            'dqlParams' => $params,
            'lockMode' => $lockMode,
        ]);
    }

    protected function asyncLock($entityName, $entityId, $lockMode)
    {
        $this->startJob('lock', [
            'entityName' => $entityName,
            'entityId' => $entityId,
            'lockMode' => $lockMode,
        ]);
    }

    protected function startJob($fn, $fixture)
    {
        $this->gearman->addTask($fn, serialize(
            [
                'conn' => $this->em->getConnection()->getParams(),
                'fixture' => $fixture,
            ]
        ));

        self::assertEquals(GEARMAN_SUCCESS, $this->gearman->returnCode());
    }
}
