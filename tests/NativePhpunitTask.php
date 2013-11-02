<?php
/**
 * Native PHPUnit Task
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to kontakt@beberlei.de so I can send you a copy immediately.
 */

require_once 'PHPUnit/Framework.php';

/**
 * A more flexible and powerful PHPUnit Task than the native Phing one.
 *
 * Plus forward compatibility for PHPUnit 3.5 and later is ensured by using the PHPUnit Test Runner instead of implementing one.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class NativePhpunitTask extends Task
{
    private $test;
    private $testfile;
    private $testdirectory;
    private $configuration = null;
    private $coverageClover = null;
    private $junitlogfile = null;
    private $haltonfailure = true;
    private $haltonerror = true;

    public function setTestdirectory($directory) {
        $this->testdirectory = $directory;
    }

    public function setTest($test) {
        $this->test = $test;
    }

    public function setTestfile($testfile) {
        $this->testfile = $testfile;
    }

    public function setJunitlogfile($junitlogfile) {
        if (strlen($junitlogfile) == 0) {
            $junitlogfile = NULL;
        }

        $this->junitlogfile = $junitlogfile;
    }

    public function setConfiguration($configuration) {
        if (strlen($configuration) == 0) {
            $configuration = NULL;
        }

        $this->configuration = $configuration;
    }

    public function setCoverageClover($coverageClover) {
        if (strlen($coverageClover) == 0) {
            $coverageClover = NULL;
        }

        $this->coverageClover = $coverageClover;
    }

    public function setHaltonfailure($haltonfailures) {
        $this->haltonfailure = $haltonfailures;
    }

    public function setHaltonerror($haltonerrors) {
        $this->haltonerror = $haltonerrors;
    }

    public function init()
    {
        require_once "PHPUnit/Runner/Version.php";
        $version = PHPUnit_Runner_Version::id();

        if (version_compare($version, '3.4.0') < 0)
        {
            throw new BuildException("NativePHPUnitTask requires PHPUnit version >= 3.2.0", $this->getLocation());
        }

        require_once 'PHPUnit/Util/Filter.php';

        // point PHPUnit_MAIN_METHOD define to non-existing method
        if (!defined('PHPUnit_MAIN_METHOD'))
        {
            define('PHPUnit_MAIN_METHOD', 'PHPUnitTask::undefined');
        }
    }

    public function main()
    {
        if (!is_dir(realpath($this->testdirectory))) {
            throw new BuildException("NativePHPUnitTask requires a Test Directory path given, '".$this->testdirectory."' given.");
        }
        set_include_path(realpath($this->testdirectory) . PATH_SEPARATOR . get_include_path());

        $printer = new NativePhpunitPrinter();

        $arguments = array(
            'configuration' => $this->configuration,
            'coverageClover' => $this->coverageClover,
            'junitLogfile' => $this->junitlogfile,
            'printer' => $printer,
        );

        require_once "PHPUnit/TextUI/TestRunner.php";
        $runner = new PHPUnit_TextUI_TestRunner();
        $suite = $runner->getTest($this->test, $this->testfile, true);

        try {
            $result = $runner->doRun($suite, $arguments);
            /* @var $result PHPUnit_Framework_TestResult */

            if ( ($this->haltonfailure && $result->failureCount() > 0) || ($this->haltonerror && $result->errorCount() > 0) ) {
                throw new BuildException("PHPUnit: ".$result->failureCount()." Failures and ".$result->errorCount()." Errors, ".
                    "last failure message: ".$printer->getMessages());
            }

            $this->log("PHPUnit Success: ".count($result->passed())." tests passed, no ".
                "failures (".$result->skippedCount()." skipped, ".$result->notImplementedCount()." not implemented)");

            // Hudson for example doesn't like the backslash in class names
            if (file_exists($this->coverageClover)) {
                $this->log("Generated Clover Coverage XML to: ".$this->coverageClover);
                $content = file_get_contents($this->coverageClover);
                $content = str_replace("\\", ".", $content);
                file_put_contents($this->coverageClover, $content);
                unset($content);
            }

        } catch(\Exception $e) {
            throw new BuildException("NativePhpunitTask failed: ".$e->getMessage());
        }
    }
}

class NativePhpunitPrinter extends PHPUnit_Util_Printer implements PHPUnit_Framework_TestListener
{
    private $_messages = array();

    public function write($buffer)
    {
        // do nothing
    }

    public function getMessages()
    {
        return $this->_messages;
    }

    /**
     * An error occurred.
     *
     * @param  PHPUnit_Framework_Test $test
     * @param  Exception              $e
     * @param  float                  $time
     */
    public function addError(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        $this->_messages[] = "Test ERROR: ".$test->getName().": ".$e->getMessage();
    }

    /**
     * A failure occurred.
     *
     * @param  PHPUnit_Framework_Test                 $test
     * @param  PHPUnit_Framework_AssertionFailedError $e
     * @param  float                                  $time
     */
    public function addFailure(PHPUnit_Framework_Test $test, PHPUnit_Framework_AssertionFailedError $e, $time)
    {
        $this->_messages[] = "Test FAILED: ".$test->getName().": ".$e->getMessage();
    }

    /**
     * Incomplete test.
     *
     * @param  PHPUnit_Framework_Test $test
     * @param  Exception              $e
     * @param  float                  $time
     */
    public function addIncompleteTest(PHPUnit_Framework_Test $test, Exception $e, $time)
    {

    }

    /**
     * Skipped test.
     *
     * @param  PHPUnit_Framework_Test $test
     * @param  Exception              $e
     * @param  float                  $time
     * @since  Method available since Release 3.0.0
     */
    public function addSkippedTest(PHPUnit_Framework_Test $test, Exception $e, $time)
    {

    }

    /**
     * A test suite started.
     *
     * @param  PHPUnit_Framework_TestSuite $suite
     * @since  Method available since Release 2.2.0
     */
    public function startTestSuite(PHPUnit_Framework_TestSuite $suite)
    {

    }

    /**
     * A test suite ended.
     *
     * @param  PHPUnit_Framework_TestSuite $suite
     * @since  Method available since Release 2.2.0
     */
    public function endTestSuite(PHPUnit_Framework_TestSuite $suite)
    {

    }

    /**
     * A test started.
     *
     * @param  PHPUnit_Framework_Test $test
     */
    public function startTest(PHPUnit_Framework_Test $test)
    {

    }

    /**
     * A test ended.
     *
     * @param  PHPUnit_Framework_Test $test
     * @param  float                  $time
     */
    public function endTest(PHPUnit_Framework_Test $test, $time)
    {

    }
}