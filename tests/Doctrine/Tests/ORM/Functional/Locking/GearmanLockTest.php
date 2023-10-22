<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Locking;

use Doctrine\DBAL\LockMode;
use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\OrmFunctionalTestCase;
use GearmanClient;

use function class_exists;
use function max;
use function serialize;

/** @group locking_functional */
class GearmanLockTest extends OrmFunctionalTestCase
{
    /** @var GearmanClient */
    private $gearman = null;

    /** @var int $maxRunTime */
    private $maxRunTime = 0;

    /** @var int */
    private $articleId;

    protected function setUp(): void
    {
        if (! class_exists('GearmanClient', false)) {
            self::markTestSkipped('pecl/gearman is required for this test to run.');
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

        $this->_em->persist($article);
        $this->_em->flush();

        $this->articleId = $article->id;
    }

    public function gearmanTaskCompleted($task): void
    {
        $this->maxRunTime = max($this->maxRunTime, $task->data());
    }

    public function testFindWithLock(): void
    {
        $this->asyncFindWithLock(CmsArticle::class, $this->articleId, LockMode::PESSIMISTIC_WRITE);
        $this->asyncFindWithLock(CmsArticle::class, $this->articleId, LockMode::PESSIMISTIC_WRITE);

        $this->assertLockWorked();
    }

    public function testFindWithWriteThenReadLock(): void
    {
        $this->asyncFindWithLock(CmsArticle::class, $this->articleId, LockMode::PESSIMISTIC_WRITE);
        $this->asyncFindWithLock(CmsArticle::class, $this->articleId, LockMode::PESSIMISTIC_READ);

        $this->assertLockWorked();
    }

    public function testFindWithReadThenWriteLock(): void
    {
        $this->asyncFindWithLock(CmsArticle::class, $this->articleId, LockMode::PESSIMISTIC_READ);
        $this->asyncFindWithLock(CmsArticle::class, $this->articleId, LockMode::PESSIMISTIC_WRITE);

        $this->assertLockWorked();
    }

    public function testFindWithOneLock(): void
    {
        $this->asyncFindWithLock(CmsArticle::class, $this->articleId, LockMode::PESSIMISTIC_WRITE);
        $this->asyncFindWithLock(CmsArticle::class, $this->articleId, LockMode::NONE);

        $this->assertLockDoesNotBlock();
    }

    public function testDqlWithLock(): void
    {
        $this->asyncDqlWithLock('SELECT a FROM Doctrine\Tests\Models\CMS\CmsArticle a', [], LockMode::PESSIMISTIC_WRITE);
        $this->asyncFindWithLock(CmsArticle::class, $this->articleId, LockMode::PESSIMISTIC_WRITE);

        $this->assertLockWorked();
    }

    public function testLock(): void
    {
        $this->asyncFindWithLock(CmsArticle::class, $this->articleId, LockMode::PESSIMISTIC_WRITE);
        $this->asyncLock(CmsArticle::class, $this->articleId, LockMode::PESSIMISTIC_WRITE);

        $this->assertLockWorked();
    }

    public function testLock2(): void
    {
        $this->asyncFindWithLock(CmsArticle::class, $this->articleId, LockMode::PESSIMISTIC_WRITE);
        $this->asyncLock(CmsArticle::class, $this->articleId, LockMode::PESSIMISTIC_READ);

        $this->assertLockWorked();
    }

    public function testLock3(): void
    {
        $this->asyncFindWithLock(CmsArticle::class, $this->articleId, LockMode::PESSIMISTIC_READ);
        $this->asyncLock(CmsArticle::class, $this->articleId, LockMode::PESSIMISTIC_WRITE);

        $this->assertLockWorked();
    }

    public function testLock4(): void
    {
        $this->asyncFindWithLock(CmsArticle::class, $this->articleId, LockMode::NONE);
        $this->asyncLock(CmsArticle::class, $this->articleId, LockMode::PESSIMISTIC_WRITE);

        $this->assertLockDoesNotBlock();
    }

    protected function assertLockDoesNotBlock(): void
    {
        $this->assertLockWorked($onlyForSeconds = 1);
    }

    protected function assertLockWorked($forTime = 2, $notLongerThan = null): void
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

    protected function asyncFindWithLock($entityName, $entityId, $lockMode): void
    {
        $this->startJob('findWithLock', [
            'entityName' => $entityName,
            'entityId' => $entityId,
            'lockMode' => $lockMode,
        ]);
    }

    protected function asyncDqlWithLock($dql, $params, $lockMode): void
    {
        $this->startJob('dqlWithLock', [
            'dql' => $dql,
            'dqlParams' => $params,
            'lockMode' => $lockMode,
        ]);
    }

    protected function asyncLock($entityName, $entityId, $lockMode): void
    {
        $this->startJob('lock', [
            'entityName' => $entityName,
            'entityId' => $entityId,
            'lockMode' => $lockMode,
        ]);
    }

    protected function startJob($fn, $fixture): void
    {
        $this->gearman->addTask($fn, serialize(
            [
                'conn' => $this->_em->getConnection()->getParams(),
                'fixture' => $fixture,
            ]
        ));

        self::assertEquals(GEARMAN_SUCCESS, $this->gearman->returnCode());
    }
}
