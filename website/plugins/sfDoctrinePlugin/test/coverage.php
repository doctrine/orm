<?php
/*
 * This file is part of the sfDoctrine package.
 * (c) 2006 Olivier Verdier <Olivier.Verdier@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @package    symfony.plugins
 * @subpackage sfDoctrine
 * @author     Pavel Kunc
 * @author     Olivier Verdier <Olivier.Verdier@gmail.com>
 * @version    SVN: $Id: coverage.php 2690 2006-11-15 18:35:07Z chtito $
 */
$testsDir = realpath(dirname(__FILE__));
define('SF_ROOT_DIR', realpath($testsDir.'/../../../'));

// symfony directories
require_once(SF_ROOT_DIR.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config.php');
require_once($sf_symfony_lib_dir.'/vendor/lime/lime.php');

$h = new lime_harness(new lime_output_color());
$h->base_dir = dirname(__FILE__);

// unit tests
$h->register_glob($h->base_dir.'/unit/*/*Test.php');

// functional tests
$h->register_glob($h->base_dir.'/functional/*Test.php');

$c = new lime_coverage($h);
$c->extension = '.class.php';
$c->verbose = false;
$c->base_dir = realpath(dirname(__FILE__).'/../lib');
$c->register_glob($c->base_dir.'/*/*.php');
$c->run();
