<?php

require_once realpath(dirname(__FILE__)."/../../../../../../lib/Doctrine/Common/IsolatedClassLoader.php");
$classLoader = new \Doctrine\Common\IsolatedClassLoader('Doctrine');
$classLoader->setBasePath(realpath(dirname(__FILE__)."/../../../../../../lib/"));
$classLoader->register();

$params = array(
    'driver' => 'pdo_mysql',
    'dbname' => $argv[3],
    'user' => $argv[1],
    'password' => $argv[2]
);

$conn = \Doctrine\DBAL\DriverManager::getConnection($params);
$sm = $conn->getSchemaManager();

if(isset($argv[4])) {
    $filterString = $argv[4];
} else {
    $filterString = false;
}

$tables = $sm->listTables();
$fixture = array();
foreach($tables AS $tableName) {
    if($filterString !== false && strpos($tableName, $filterString) === false) {
        continue;
    }
    $fixture[$tableName] = $sm->listTableColumns($tableName);
}

ksort($fixture);

$regexp = '(Doctrine\\\\DBAL\\\\Types\\\\([a-zA-Z]+)Type::__set_state\(array\([\s]+\)\))';

$code = var_export($fixture, true);
$code = preg_replace(
    $regexp,
    'Doctrine\\DBAL\\Types\\Type::getType(strtolower("\1"))',
    $code
);

echo "<?php\n\return ";
echo $code;
echo ";\n?>\n";