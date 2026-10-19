<?php

declare(strict_types=1);

namespace Doctrine\Performance\Collection;

use Composer\InstalledVersions;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\Mapping\Driver\StaticPHPDriver;
use InvalidArgumentException;
use RuntimeException;

use function count;
use function dirname;
use function function_exists;
use function gc_collect_cycles;
use function hrtime;
use function in_array;
use function json_encode;
use function memory_get_peak_usage;
use function memory_get_usage;
use function memory_reset_peak_usage;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const PHP_VERSION;
use const PHP_VERSION_ID;

// Run from either revision; the optional final argument selects another ORM source directory.
// php tests/Performance/Collection/deferred-storage.php 10000 8 hydrate 5 [/path/to/base/src]
$loader = require dirname(__DIR__, 3) . '/vendor/autoload.php';

if (isset($argv[5])) {
    $loader->setPsr4('Doctrine\\ORM\\', [$argv[5]]);
}

$entities   = (int) ($argv[1] ?? 10000);
$relations  = (int) ($argv[2] ?? 8);
$operation  = $argv[3] ?? 'hydrate';
$iterations = (int) ($argv[4] ?? 5);

if ($entities < 1 || $relations < 0 || $relations > 8 || $iterations < 1 || ! in_array($operation, ['hydrate', 'flush', 'load-one'], true)) {
    throw new InvalidArgumentException('Expected a positive entity count, 0..8 relations, hydrate|flush|load-one, and a positive iteration count.');
}

if ($operation === 'load-one' && $relations === 0) {
    throw new InvalidArgumentException('load-one requires at least one relation.');
}

if (! function_exists('memory_reset_peak_usage')) {
    throw new RuntimeException('This memory benchmark requires PHP 8.2 or newer.');
}

StorageBenchmarkOwner::$relations = $relations;
$config                           = ORMSetup::createAttributeMetadataConfiguration([], true);
$config->setMetadataDriverImpl(new StaticPHPDriver([]));
if (PHP_VERSION_ID >= 80400) {
    $config->enableNativeLazyObjects(true);
}

$connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
$em         = new EntityManager($connection, $config);
(new SchemaTool($em))->createSchema([
    $em->getClassMetadata(StorageBenchmarkOwner::class),
    $em->getClassMetadata(StorageBenchmarkTarget::class),
]);

$connection->beginTransaction();
for ($id = 1; $id <= $entities; ++$id) {
    $data = ['id' => $id];
    for ($field = 0; $field < 6; ++$field) {
        $data['value' . $field] = 'value-' . $id . '-' . $field;
    }

    $connection->insert('storage_benchmark_owners', $data);
}

$connection->commit();

$query   = $em->createQuery('SELECT e FROM ' . StorageBenchmarkOwner::class . ' e ORDER BY e.id');
$samples = [];
for ($iteration = -2; $iteration < $iterations; ++$iteration) {
    unset($result, $entity);
    $em->clear();
    gc_collect_cycles();
    memory_reset_peak_usage();
    $before = memory_get_usage(false);
    $start  = hrtime(true);
    $result = $query->getResult();

    if ($operation === 'flush') {
        $result[0]->value0 = 'changed-' . $iteration;
        $em->flush();
    } elseif ($operation === 'load-one') {
        foreach ($result as $entity) {
            $entity->items0->count();
        }
    }

    $elapsed  = (hrtime(true) - $start) / 1e6;
    $peak     = memory_get_peak_usage(false) - $before;
    $retained = memory_get_usage(false) - $before;

    if (count($result) !== $entities || $result[$entities - 1]->id !== $entities) {
        throw new RuntimeException('Incorrect hydration result.');
    }

    if ($iteration >= 0) {
        $samples[] = ['ms' => $elapsed, 'peak_bytes' => $peak, 'retained_bytes' => $retained];
    }
}

echo json_encode([
    'php' => PHP_VERSION,
    'dbal' => InstalledVersions::getPrettyVersion('doctrine/dbal'),
    'entities' => $entities,
    'relations' => $relations,
    'operation' => $operation,
    'warmups' => 2,
    'samples' => $samples,
], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR), "\n";

class StorageBenchmarkOwner
{
    public static int $relations = 8;

    public int $id;
    public string $value0;
    public string $value1;
    public string $value2;
    public string $value3;
    public string $value4;
    public string $value5;

    /** @var Collection<int, StorageBenchmarkTarget> */
    public Collection $items0;
    /** @var Collection<int, StorageBenchmarkTarget> */
    public Collection $items1;
    /** @var Collection<int, StorageBenchmarkTarget> */
    public Collection $items2;
    /** @var Collection<int, StorageBenchmarkTarget> */
    public Collection $items3;
    /** @var Collection<int, StorageBenchmarkTarget> */
    public Collection $items4;
    /** @var Collection<int, StorageBenchmarkTarget> */
    public Collection $items5;
    /** @var Collection<int, StorageBenchmarkTarget> */
    public Collection $items6;
    /** @var Collection<int, StorageBenchmarkTarget> */
    public Collection $items7;

    /** @param ClassMetadata<self> $metadata */
    public static function loadMetadata(ClassMetadata $metadata): void
    {
        $metadata->setPrimaryTable(['name' => 'storage_benchmark_owners']);
        $metadata->mapField(['fieldName' => 'id', 'type' => 'integer', 'id' => true]);
        for ($field = 0; $field < 6; ++$field) {
            $metadata->mapField(['fieldName' => 'value' . $field, 'type' => 'string']);
        }

        for ($relation = 0; $relation < self::$relations; ++$relation) {
            $metadata->mapManyToMany([
                'fieldName' => 'items' . $relation,
                'targetEntity' => StorageBenchmarkTarget::class,
                'joinTable' => ['name' => 'storage_benchmark_links_' . $relation],
            ]);
        }
    }
}

class StorageBenchmarkTarget
{
    public int $id;

    /** @param ClassMetadata<self> $metadata */
    public static function loadMetadata(ClassMetadata $metadata): void
    {
        $metadata->setPrimaryTable(['name' => 'storage_benchmark_targets']);
        $metadata->mapField(['fieldName' => 'id', 'type' => 'integer', 'id' => true]);
    }
}
