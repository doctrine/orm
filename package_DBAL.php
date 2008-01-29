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

$summary = 'PHP5 Database DBAL';

$description =<<<EOT
Doctrine_DBAL is a DBAL (database abstraction layer) for PHP 5.2.x+ .
EOT;

$packagefile = './package.xml';

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
        'manual/',
        'models/',
        'tests/',
        'README',
        'CHANGELOG',
        'LICENSE',
        'COPYRIGHT',
        'Access.php',
        'Adapter.php',
        'Adapter/',
        'Auditlog.php',
        'Auditlog/',
        'Builder.php',
        'Builder/',
        'Cache.php',
        'Cache/',
        'Cli.php',
        'Cli/',
        'Collection.php',
        'Collection/',
        'Column.php',
        'Compiler.php',
        'Compiler/',
        'Configurable.php',
        'Data.php',
        'Data/',
        'Event.php',
        'Event/',
        'EventListener.php',
        'EventListener/',
        'Exception.php',
        'Expression.php',
        'Expression/',
        'File.php',
        'File/',
        'FileFinder.php',
        'FileFinder/',
        'Formatter.php',
        'Hook.php',
        'Hook/',
        'Hydrator.php',
        'Hydrator/',
        'I18n.php',
        'I18n/',
        'Inflector.php',
        'IntegrityMapper.php',
        'Lib.php',
        'Locator.php',
        'Locator/',
        'Locking/',
        'Log.php',
        'Log/',
        'Mapper/',
        'Migration.php',
        'Migration/',
        'Node.php',
        'Node/',
        'Null.php',
        'Overloadable.php',
        'Pager.php',
        'Pager/',
        'Parser.php',
        'Parser/',
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
        'Table.php',
        'Table/',
        'Task.php',
        'Task/',
        'Template.php',
        'Template/',
        'Tree.php',
        'Tree/',
        'Util.php',
        'Validator.php',
        'Validator/',
        'View.php',
        'View/',
    ),
    'dir_roles'         => array(
        'lib'           => 'php',
    ),
    'exceptions' => array(
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
