<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\Cache\{DefaultCacheFactory, RegionsConfiguration};
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\{SchemaTool, Setup};
use Doctrine\Tests\TestUtil;

use PHPUnit\Framework\TestCase;

class GH7708Test extends TestCase
{
    protected $entityManager;

    protected function getEntityManager()
    {
        if (!$this->entityManager) {
            $regionsConfig = new RegionsConfiguration();

            $cacheDriver = new GH7708ArrayCacheMock();

            $cacheFactory = new DefaultCacheFactory($regionsConfig, $cacheDriver);

            $conn = TestUtil::getConnection();

            $config = Setup::createAnnotationMetadataConfiguration([]);

            $config->setMetadataCacheImpl($cacheDriver);
            $config->setQueryCacheImpl($cacheDriver);
            $config->setResultCacheImpl($cacheDriver);
            $config->setHydrationCacheImpl($cacheDriver);

            $config->setSecondLevelCacheEnabled();
            $secondLevelCacheConfig = $config->getSecondLevelCacheConfiguration();
            $secondLevelCacheConfig->setCacheFactory($cacheFactory);

            $this->entityManager = EntityManager::create($conn, $config);

            // Creating a schema

            $schemaTool = new SchemaTool($this->entityManager);
            $schemaTool->createSchema([
                $this->entityManager->getClassMetadata(GH7708Car::class),
                $this->entityManager->getClassMetadata(GH7708Model::class),
                $this->entityManager->getClassMetadata(GH7708Drive::class)
            ]);

            // Populating with data

            $frontDrive = new GH7708Drive('Front');
            $rearDrive = new GH7708Drive('Rear');
            $fullDrive = new GH7708Drive('Full');

            $this->entityManager->persist(new GH7708Car(new GH7708Model('Audi A4'), $rearDrive));
            $this->entityManager->persist(new GH7708Car(new GH7708Model('Audi A5'), $rearDrive));
            $this->entityManager->persist(new GH7708Car(new GH7708Model('Audi A6'), $rearDrive));
            $this->entityManager->persist(new GH7708Car(new GH7708Model('Audi A8'), $rearDrive));
            $this->entityManager->persist(new GH7708Car(new GH7708Model('Audi Q3'), $fullDrive));
            $this->entityManager->persist(new GH7708Car(new GH7708Model('Audi Q5'), $fullDrive));
            $this->entityManager->persist(new GH7708Car(new GH7708Model('Audi Q7'), $fullDrive));
            $this->entityManager->persist(new GH7708Car(new GH7708Model('BMW 1-series'), $rearDrive));
            $this->entityManager->persist(new GH7708Car(new GH7708Model('BMW 3-series'), $rearDrive));
            $this->entityManager->persist(new GH7708Car(new GH7708Model('BMW 5-series'), $rearDrive));
            $this->entityManager->persist(new GH7708Car(new GH7708Model('BMW 7-series'), $rearDrive));
            $this->entityManager->persist(new GH7708Car(new GH7708Model('BMW X1'), $fullDrive));
            $this->entityManager->persist(new GH7708Car(new GH7708Model('BMW X3'), $fullDrive));
            $this->entityManager->persist(new GH7708Car(new GH7708Model('BMW X5'), $fullDrive));
            $this->entityManager->persist(new GH7708Car(new GH7708Model('BMW X6'), $fullDrive));
            $this->entityManager->persist(new GH7708Car(new GH7708Model('Volkswagen Golf'), $frontDrive));
            $this->entityManager->persist(new GH7708Car(new GH7708Model('Volkswagen Jetta'), $frontDrive));
            $this->entityManager->persist(new GH7708Car(new GH7708Model('Volkswagen Passat'), $frontDrive));
            $this->entityManager->persist(new GH7708Car(new GH7708Model('Volkswagen Polo'), $frontDrive));
            $this->entityManager->persist(new GH7708Car(new GH7708Model('Volkswagen Tiguan'), $fullDrive));

            $this->entityManager->flush();
        }
        return $this->entityManager;
    }

    protected function executeQuery(): array
    {
        return $this->getEntityManager()->getRepository(GH7708Car::class)->createQueryBuilder('ca')
            ->addSelect('mo')
            ->addSelect('dr')
            ->innerJoin('ca.model', 'mo')
            ->innerJoin('ca.drive', 'dr')
            ->getQuery()
            ->setMaxResults(20)
            ->useResultCache(true, 3600, 'result')
            ->setCacheable(true)
            ->getResult();
    }

    public function testSecondLevelCacheCartesianProduct() : void
    {
        // Executing the query for the first time to populate the cache
        $this->executeQuery();

        // Reseting the mock array
        GH7708ArrayCacheMock::$fetchRequests = [];

        // The second query execution for retreiving data from cache
        $result = $this->executeQuery();

        // I expect that cache requests count is less than query result (20 rows).
        $resultObjectsCount = count($result);
        $fetchRequestsCount = count(GH7708ArrayCacheMock::$fetchRequests);
        $this->assertLessThan($resultObjectsCount, $fetchRequestsCount);
    }
}

class GH7708ArrayCacheMock extends ArrayCache
{
    public static $fetchRequests = [];

    protected function doFetch($id)
    {
        self::$fetchRequests[] = [
            'id' => $id,
            'cacheDriverObject' => spl_object_hash($this)
        ];
        return parent::doFetch($id);
    }
}

/**
 * @ORM\Entity
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
 **/
class GH7708Car
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     **/
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity=GH7708Model::class, cascade={"persist"})
     * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
     **/
    protected $model;

    /**
     * @ORM\ManyToOne(targetEntity=GH7708Drive::class, cascade={"persist"})
     * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
     **/
    protected $drive;

    public function __construct(GH7708Model $model, GH7708Drive $drive)
    {
        $this->model = $model;
        $this->drive = $drive;
    }
}

/**
 * @ORM\Entity
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
 **/
class GH7708Model
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     **/
    protected $id;

    /**
     * @ORM\Column(type="string", length=64)
     **/
    protected $title;

    public function __construct(string $title)
    {
        $this->title = $title;
    }
}

/**
 * @ORM\Entity
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
 **/
class GH7708Drive
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     **/
    protected $id;

    /**
     * @ORM\Column(type="string", length=64)
     **/
    protected $title;

    public function __construct(string $title)
    {
        $this->title = $title;
    }
}
