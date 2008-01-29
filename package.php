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

$summary = 'PHP5 Database ORM';

$description =<<<EOT
Doctrine is an ORM (object relational mapper) for PHP 5.2.x+ that sits on top of
a powerful DBAL (database abstraction layer). One of its key features is the
ability to optionally write database queries in an OO (object oriented)
SQL-dialect called DQL inspired by Hibernates HQL. This provides developers with
a powerful alternative to SQL that maintains a maximum of flexibility without
requiring needless code duplication.
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
    ),
    'dir_roles'         => array(
        'lib'           => 'php',
        'manual'        => 'doc',
        'models'        => 'doc',
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
