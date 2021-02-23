<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

class DDC1987TestWorker
{
    private $em;

    static public function run()
    {
        $testWorker = new DDC1987TestWorker();

        $worker = new \GearmanWorker();
        $worker->addServer();
        $worker->addFunction("incrementPrice", array($testWorker, "incrementPrice"));

        while($worker->work()) {
            if ($worker->returnCode() != GEARMAN_SUCCESS) {
                echo "return_code: " . $worker->returnCode() . "\n";
                break;
            }
        }
    }

    public function incrementPrice($job)
    {
        $this->processWorkload($job);

        // Fetch into memory
        $order = $this->em->find('Doctrine\Tests\Models\DDC1987\DDC1987Order', 1);
        $items = $order->getItems();
        $item = $items->first();

        // 'Old' value
        echo '$item->price = '.$item->price.PHP_EOL;

        // Some delay to allow for interleaving of the tasks
        echo 'Sleep...'.PHP_EOL;
        sleep(2);

        // Refresh
        echo 'Refresh..'.PHP_EOL;
        $this->em->refresh($order);
        //$this->em->refresh($item); // <---- By explicitly refreshing we fix the bug

        // 'New' value
        $item = $order->getItems()->first();
        echo '$item->price = '.$item->price.PHP_EOL;

        // Update
        echo 'Changed $item->price to '.($item->price+1).PHP_EOL;
        $item->price = $item->price+1;

        echo 'Persist'.PHP_EOL;
        $this->em->persist($order);
        $this->em->flush();

        return $item->price;
    }

    protected function processWorkload($job)
    {
        echo "Received job: " . $job->handle() . " for function " . $job->functionName() . "\n";

        $workload = $job->workload();
        $workload = unserialize($workload);

        if (!isset($workload['conn']) || !is_array($workload['conn'])) {
            throw new \InvalidArgumentException("Missing Database parameters");
        }

        // Allow for interleaving of tasks
        sleep($workload['delay']);

        $this->em = $this->createEntityManager($workload['conn']);
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

DDC1987TestWorker::run();
