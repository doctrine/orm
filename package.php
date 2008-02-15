<?php

buildPearPackage('/Users/jwage/Sites/doctrine/trunk', '0.9.0', 'beta');

function buildPearPackage($path, $version, $state)
{
    $packageFile = $path . DIRECTORY_SEPARATOR . 'package.xml';
    
    require_once('PEAR/packageFileManager2.php');
    PEAR::setErrorHandling(PEAR_ERROR_DIE);

    $packagexml = new PEAR_packageFileManager2;
    
    $notes = <<<EOT
-
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

    $options = array(
        'filelistgenerator' => 'svn',
        'changelogoldtonew' => false,
        'simpleoutput'      => true,
        'baseinstalldir'    => '/',
        'packagedirectory'  => $path,
        'packageFile'       => $packageFile,
        'clearcontents'     => false,
        // What to ignore
        'ignore'            => array(
            $path . DIRECTORY_SEPARATOR . 'vendor/',
            $path . DIRECTORY_SEPARATOR . 'package*.*',
            $path . DIRECTORY_SEPARATOR . 'tests_old/',
            $path . DIRECTORY_SEPARATOR . 'tests/',
            $path . DIRECTORY_SEPARATOR . 'tools/'
        ),
        // What to include in package
        'include'            => array(
            $path . DIRECTORY_SEPARATOR . 'lib/',
            $path . DIRECTORY_SEPARATOR . 'manual/',
            $path . DIRECTORY_SEPARATOR . 'vendor/',
            $path . DIRECTORY_SEPARATOR . 'README',
            $path . DIRECTORY_SEPARATOR . 'CHANGELOG',
            $path . DIRECTORY_SEPARATOR . 'LICENSE',
            $path . DIRECTORY_SEPARATOR . 'COPYRIGHT'
        ),
        // Dir roles
        'dir_roles'         => array(
            'lib'           =>  'php',
            'manual'        =>  'doc',
            'vendor'        =>  'php'
        ),
        // File roles
        'exceptions' => array(
            'README' => 'doc',
            'CHANGELOG' => 'doc',
            'LICENSE' => 'doc',
            'COPYRIGHT' => 'doc'
        )
    );

    $package = &PEAR_packageFileManager2::importOptions($packageFile, $options);
    $package->setPackageType('php');

    $package->clearDeps();
    $package->setPhpDep('5.2.3');
    $package->setPearInstallerDep('1.4.0b1');
    $package->addPackageDepWithChannel('required', 'PEAR', 'pear.php.net', '1.3.6');

    $package->addRelease();
    $package->generateContents();
    $package->setReleaseVersion($version);
    $package->setAPIVersion($version);
    $package->setReleaseStability($state);
    $package->setAPIStability($state);
    $package->setNotes($notes);
    $package->setSummary($summary);
    $package->setDescription($description);
    $package->addGlobalReplacement('package-info', '@package_version@', 'version');

    if (isset($_GET['make']) || (isset($_SERVER['argv']) && @$_SERVER['argv'][1] == 'make')) {
        $package->writepackageFile();
    } else {
        $package->debugpackageFile();
    }
}