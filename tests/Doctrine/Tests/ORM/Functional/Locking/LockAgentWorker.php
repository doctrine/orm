<?php

namespace Doctrine\Tests\ORM\Functional\Locking;

require_once __DIR__ . "/../../../TestInit.php";

class LockAgentWorker
{
    private $em;

    static public function run()
    {
        $lockAgent = new LockAgentWorker();

        $worker = new \GearmanWorker();
        $worker->addServer();
        $worker->addFunction("findWithLock", array($lockAgent, "findWithLock"));
        $worker->addFunction("dqlWithLock", array($lockAgent, "dqlWithLock"));
        $worker->addFunction('lock', array($lockAgent, 'lock'));

        while($worker->work()) {
            if ($worker->returnCode() != GEARMAN_SUCCESS) {
                echo "return_code: " . $worker->returnCode() . "\n";
                break;
            }
        }
    }

    protected function process($job, \Closure $do)
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

        return (microtime(true) - $s);
    }

    public function findWithLock($job)
    {
        return $this->process($job, function($fixture, $em) {
            $entity = $em->find($fixture['entityName'], $fixture['entityId'], $fixture['lockMode']);
        });
    }

    public function dqlWithLock($job)
    {
        return $this->process($job, function($fixture, $em) {
            /* @var $query Doctrine\ORM\Query */
            $query = $em->createQuery($fixture['dql']);
            $query->setLockMode($fixture['lockMode']);
            $query->setParameters($fixture['dqlParams']);
            $result = $query->getResult();
        });
    }

    public function lock($job)
    {
        return $this->process($job, function($fixture, $em) {
            $entity = $em->find($fixture['entityName'], $fixture['entityId']);
            $em->lock($entity, $fixture['lockMode']);
        });
    }

    protected function processWorkload($job)
    {
        echo "Received job: " . $job->handle() . " for function " . $job->functionName() . "\n";

        $workload = $job->workload();
        $workload = unserialize($workload);

        if (!isset($workload['conn']) || !is_array($workload['conn'])) {
            throw new \InvalidArgumentException("Missing Database parameters");
        }

        $this->em = $this->createEntityManager($workload['conn']);

        if (!isset($workload['fixture'])) {
            throw new \InvalidArgumentException("Missing Fixture parameters");
        }
        return $workload['fixture'];
    }

    protected function createEntityManager($conn)
    {
        $config = new \Doctrine\ORM\Configuration();
        $config->setProxyDir(__DIR__ . '/../../../Proxies');
        $config->setProxyNamespace('MyProject\Proxies');
        $config->setAutoGenerateProxyClasses(true);

        $annotDriver = $config->newDefaultAnnotationDriver(array(__DIR__ . '/../../../Models/'), true);
        $config->setMetadataDriverImpl($annotDriver);

        $cache = new \Doctrine\Common\Cache\ArrayCache();
        $config->setMetadataCacheImpl($cache);
        $config->setQueryCacheImpl($cache);
        $config->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());

        $em = \Doctrine\ORM\EntityManager::create($conn, $config);

        return $em;
    }
}

LockAgentWorker::run();