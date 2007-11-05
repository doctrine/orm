<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.phpdoctrine.com>.
 */

/**
 * Doctrine_UnitTestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Bjarte S. Karlsen <bjartka@pvv.ntnu.no>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */

require_once dirname(__FILE__) . '/DoctrineTest/UnitTestCase.php';
require_once dirname(__FILE__) . '/DoctrineTest/GroupTest.php';
require_once dirname(__FILE__) . '/DoctrineTest/Doctrine_UnitTestCase.php';
require_once dirname(__FILE__) . '/DoctrineTest/Reporter.php';

class DoctrineTest
{

    protected $testGroup; // the default test group
    protected $groups;

    public function __construct()
    {
        $this->requireModels();
        $this->testGroup = new GroupTest('Doctrine Framework Unit Tests', 'main');
    }

    /**
     * Add a test to be run. 
     *
     * This is a thin wrapper around GroupTest that also store the testcase in 
     * this class so that it is easier to create custom groups
     *
     * @param UnitTestCase A test case
     */
    public function addTestCase($testCase){
        $this->groups[$testCase->getName()] = $testCase;
        $this->testGroup->addTestCase($testCase);
    }

    /**
     * Run the tests
     *
     * This method will run the tests with the correct Reporter. It will run 
     * grouped tests if asked to and filter results. It also has support for 
     * running coverage report. 
     *
     */
    public function run(){
        $testGroup = $this->testGroup;
        if (PHP_SAPI === 'cli') {
            require_once(dirname(__FILE__) . '/DoctrineTest/Reporter/Cli.php');
            $reporter = new DoctrineTest_Reporter_Cli();
            $argv = $_SERVER['argv'];
            array_shift($argv);
            $options = $this->parseOptions($argv);
        } else {
            require_once(dirname(__FILE__) . '/DoctrineTest/Reporter/Html.php');
            $options = $_GET;
            if(isset($options["filter"])){
                $options["filter"] = explode(",", $options["filter"]);
            }
            if(isset($options["group"])){
                $options["group"] = explode(",", $options["group"]);
            }
            $reporter = new DoctrineTest_Reporter_Html();
        }

        //replace global group with custom group if we have group option set
        if (isset($options['group'])) {
            $testGroup = new GroupTest('Doctrine Framework Custom test', 'custom');
            foreach($options['group'] as $group) {
                if (isset($this->groups[$group])) {
                    $testGroup->addTestCase($this->groups[$group]);
                } else if (class_exists($group)) {
                    $testGroup->addTestCase(new $group);
                } else {
                    die($group . " is not a valid group or doctrine test class\n ");
                }
            }
        } 

        $filter = '';
        if (isset($options['filter'])) {
            $filter = $options['filter'];
        }

        //show help text
        if (isset($options['help'])) {
            echo "Doctrine test runner help\n";
            echo "===========================\n";
            echo " To run all tests simply run this script without arguments. \n";
            echo "\n Flags:\n";
            echo " -coverage will generate coverage report data that can be viewed with the cc.php script in this folder. NB! This takes time. You need xdebug to run this\n";
            echo " -group <groupName1> <groupName2> <className1> Use this option to run just a group of tests or tests with a given classname. Groups are currently defined as the variable name they are called in this script.\n";
            echo " -filter <string1> <string2> case insensitive strings that will be applied to the className of the tests. A test_classname must contain all of these strings to be run\n"; 
            echo "\nAvailable groups:\n tickets, transaction, driver, data_dict, sequence, export, import, expression, core, relation, data_types, utility, db, event_listener, query_tests, record, cache\n";
            die();
        }

        //generate coverage report
        if (isset($options['coverage'])) {

            /*
             * The below code will not work for me (meus). It would be nice if 
             * somebody could give it a try. Just replace this block of code 
             * with the one below
             *
             define('PHPCOVERAGE_HOME', dirname(dirname(__FILE__)) . '/vendor/spikephpcoverage');
            require_once PHPCOVERAGE_HOME . '/CoverageRecorder.php';
            require_once PHPCOVERAGE_HOME . '/reporter/HtmlCoverageReporter.php';

            $covReporter = new HtmlCoverageReporter('Doctrine Code Coverage Report', '', 'coverage2');

            $includePaths = array('../lib');
            $excludePaths = array();
            $cov = new CoverageRecorder($includePaths, $excludePaths, $covReporter);

            $cov->startInstrumentation();
            $testGroup->run($reporter, $filter);
            $cov->stopInstrumentation();

            $cov->generateReport();
            $covReporter->printTextSummary();
             */
            xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
            $testGroup->run($reporter, $filter);
            $result['coverage'] = xdebug_get_code_coverage();
            xdebug_stop_code_coverage();
            file_put_contents(dirname(__FILE__) . '/coverage/coverage.txt', serialize($result));
            require_once dirname(__FILE__) . '/DoctrineTest/Coverage.php';
            $coverageGeneration = new DoctrineTest_Coverage();
            $coverageGeneration->generateReport();
            return;
            // */

        }
        $testGroup->run($reporter, $filter);
    }


    /**
     * Require all the models needed in the tests
     *
     */
    public function requireModels()
    {
        $models = new DirectoryIterator(dirname(__FILE__) . '/../models/');
        foreach($models as $key => $file) {
            if ($file->isFile() && ! $file->isDot()) {
                $e = explode('.', $file->getFileName());
                if (end($e) === 'php') {
                    require_once $file->getPathname();
                }
            }
        }
    }

    /**
     * Parse Options from cli into an associative array
     *
     * @param array $array An argv array from cli
     * @return array An array with options
     */
    public function parseOptions($array) {
        $currentName='';
        $options=array();
        foreach($array as $name) {
            if (strpos($name,'-')===0) {
                $name=str_replace('-','',$name);      
                $currentName=$name;
                if ( ! isset($options[$currentName])) {
                    $options[$currentName]=array();         
                }
            } else {
                $values=$options[$currentName];
                array_push($values,$name);    
                $options[$currentName]=$values;
            }
        }
        return $options;
    }

    /**
     * Autoload test cases
     *
     * Will create test case if it does not exist
     *
     * @param string $class The name of the class to autoload 
     * @return boolean True 
     */
    public static function autoload($class) {
        if (strpos($class, 'TestCase') === false) {
            return false;
        }

        $e      = explode('_', $class);
        $count  = count($e);

        $prefix = array_shift($e);

        if ($prefix !== 'Doctrine') {
            return false;
        }

        $dir    = array_shift($e);

        $file   = $dir . '_' . substr(implode('_', $e), 0, -(strlen('_TestCase'))) . 'TestCase.php';

        if ( $count > 3) {
            $file   = str_replace('_', DIRECTORY_SEPARATOR, $file);
        } else {
            $file   = str_replace('_', '', $file);
        }

        // create a test case file if it doesn't exist

        if ( ! file_exists($file)) {
            $contents = file_get_contents('template.tpl');
            $contents = sprintf($contents, $class, $class);

            if ( ! file_exists($dir)) {
                mkdir($dir, 0777);
            }

            file_put_contents($file, $contents);
        }
        require_once($file);

        return true;
    }
}
