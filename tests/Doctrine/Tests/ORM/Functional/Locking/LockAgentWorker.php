<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Locking;

use Closure;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\DBAL\Logging\EchoSQLLogger;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use GearmanWorker;
use InvalidArgumentException;
use const GEARMAN_SUCCESS;
use function is_array;
use function microtime;
use function sleep;
use function unserialize;

class LockAgentWorker
{
    private $em;

    public static function run()
    {
        $lockAgent = new LockAgentWorker();

        $worker = new GearmanWorker();
        $worker->addServer(
            $_SERVER['GEARMAN_HOST'] ?? null,
            $_SERVER['GEARMAN_PORT'] ?? 4730
        );
        $worker->addFunction('findWithLock', [$lockAgent, 'findWithLock']);
        $worker->addFunction('dqlWithLock', [$lockAgent, 'dqlWithLock']);
        $worker->addFunction('lock', [$lockAgent, 'lock']);

        while ($worker->work()) {
            if ($worker->returnCode() !== GEARMAN_SUCCESS) {
                echo 'return_code: ' . $worker->returnCode() . "\n";
                break;
            }
        }
    }

    protected function process($job, Closure $do)
    {
        $fixture = $this->processWorkload($job);

        $s = microtime(true);
        $this->em->beginTransaction();
        $do($fixture, $this->em);

        sleep(1);
        $this->em->rollback();
        $this->em->clear();
        $this->em->close();
        $this->em->getConnection()->close();

        return microtime(true) - $s;
    }

    public function findWithLock($job)
    {
        return $this->process($job, static function ($fixture, $em) {
            $entity = $em->find($fixture['entityName'], $fixture['entityId'], $fixture['lockMode']);
        });
    }

    public function dqlWithLock($job)
    {
        return $this->process($job, static function ($fixture, $em) {
            /** @var Doctrine\ORM\Query $query */
            $query = $em->createQuery($fixture['dql']);
            $query->setLockMode($fixture['lockMode']);
            $query->setParameters($fixture['dqlParams']);
            $result = $query->getResult();
        });
    }

    public function lock($job)
    {
        return $this->process($job, static function ($fixture, $em) {
            $entity = $em->find($fixture['entityName'], $fixture['entityId']);
            $em->lock($entity, $fixture['lockMode']);
        });
    }

    protected function processWorkload($job)
    {
        echo 'Received job: ' . $job->handle() . ' for function ' . $job->functionName() . "\n";

        $workload = $job->workload();
        $workload = unserialize($workload);

        if (! isset($workload['conn']) || ! is_array($workload['conn'])) {
            throw new InvalidArgumentException('Missing Database parameters');
        }

        $this->em = $this->createEntityManager($workload['conn']);

        if (! isset($workload['fixture'])) {
            throw new InvalidArgumentException('Missing Fixture parameters');
        }
        return $workload['fixture'];
    }

    protected function createEntityManager($conn)
    {
        $config = new Configuration();
        $config->setProxyDir(__DIR__ . '/../../../Proxies');
        $config->setProxyNamespace('MyProject\Proxies');
        $config->setAutoGenerateProxyClasses(true);

        $annotDriver = $config->newDefaultAnnotationDriver([__DIR__ . '/../../../Models/']);
        $config->setMetadataDriverImpl($annotDriver);

        $cache = new ArrayCache();
        $config->setMetadataCacheImpl($cache);
        $config->setQueryCacheImpl($cache);
        $config->setSQLLogger(new EchoSQLLogger());

        return EntityManager::create($conn, $config);
    }
}

LockAgentWorker::run();
