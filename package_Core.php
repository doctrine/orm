<?php

require_once('PEAR/PackageFileManager2.php');
PEAR::setErrorHandling(PEAR_ERROR_DIE);

$packagexml = new PEAR_PackageFileManager2;

$version_release = '0.9';
$version_api = $version_release;
$state = 'beta';

$notes = <<<EOT
barfoo
EOT;

$summary = 'PHP5 Database Interface Core Package';

$description =<<<EOT
Doctrine_Core is the core package for the Doctrine DBAL/ORM. It contains various
helper classes that are necessary for both the DBAL and the ORM.
EOT;

$packagefile = './package_core.xml';

$options = array(
    'filelistgenerator' => 'svn',
    'changelogoldtonew' => false,
    'simpleoutput'      => true,
    'baseinstalldir'    => '/',
    'packagedirectory'  => './',
    'packagefile'       => $packagefile,
    'clearcontents'     => false,
    'ignore'            => array(
        'vendor/',
        'tools/',
        'package*.php',
        'package*.xml',
        'models/',
        'Access.php',
        'Adapter.php',
        'Adapter/',
        'Auditlog.php',
        'Auditlog/',
        'Cache.php',
        'Cache/',
        'Collection.php',
        'Collection/',
        'Column.php',
        'Connection.php',
        'Connection/',
        'DataDict.php',
        'DataDict/',
        'Export.php',
        'Export/',
        'Expression.php',
        'Expression/',
        'Hook.php',
        'Hook/',
        'Hydrator.php',
        'Hydrator/',
        'I18n.php',
        'I18n/',
        'Import.php',
        'Import/',
        'IntegrityMapper.php',
        'Locking/',
        'Manager.php',
        'Manager/',
        'Mapper/',
        'Migration.php',
        'Migration/',
        'Node.php',
        'Node/',
        'Pager.php',
        'Pager/',
        'Query.php',
        'Query/',
        'RawSql.php',
        'RawSql/',
        'Record.php',
        'Record/',
        'Relation.php',
        'Relation/',
        'Search.php',
        'Search/',
        'Sequence.php',
        'Sequence/',
        'Table.php',
        'Table/',
        'Template.php',
        'Template/',
        'Transaction.php',
        'Transaction/',
        'Tree.php',
        'Tree/',
        'Validator.php',
        'Validator/',
        'View.php',
        'View/',
    ),
    'dir_roles'         => array(
        'lib'           => 'php',
        'manual'        => 'doc',
        'tests'         => 'test',
    ),
    'exceptions' => array(
        'README' => 'doc',
        'CHANGELOG' => 'doc',
        'LICENSE' => 'doc',
        'COPYRIGHT' => 'doc'
    )
);

$package = &PEAR_PackageFileManager2::importOptions($packagefile, $options);
$package->setPackageType('php');

$package->clearDeps();
$package->setPhpDep('5.2.3');
$package->setPearInstallerDep('1.4.0b1');
$package->addPackageDepWithChannel('required', 'PEAR', 'pear.php.net', '1.3.6');

$package->addRelease();
$package->generateContents();
$package->setReleaseVersion($version_release);
$package->setAPIVersion($version_api);
$package->setReleaseStability($state);
$package->setAPIStability($state);
$package->setNotes($notes);
$package->setSummary($summary);
$package->setDescription($description);
$package->addGlobalReplacement('package-info', '@package_version@', 'version');

if (isset($_GET['make']) || (isset($_SERVER['argv']) && @$_SERVER['argv'][1] == 'make')) {
    $package->writePackageFile();
} else {
    $package->debugPackageFile();
}
