<?php
/*
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
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\Tests\ORM\Proxy;

use Doctrine\Tests\OrmTestCase;
use Doctrine\ORM\Proxy\Autoloader;

/**
 * @group DDC-1698
 */
class AutoloaderTest extends OrmTestCase
{
    static public function dataResolveFile()
    {
        return array(
            array('/tmp', 'MyProxy', 'MyProxy\__CG__\RealClass', '/tmp' . DIRECTORY_SEPARATOR . '__CG__RealClass.php'),
            array('/tmp', 'MyProxy\Subdir', 'MyProxy\Subdir\__CG__\RealClass', '/tmp' . DIRECTORY_SEPARATOR . '__CG__RealClass.php'),
            array('/tmp', 'MyProxy', 'MyProxy\__CG__\Other\RealClass', '/tmp' . DIRECTORY_SEPARATOR . '__CG__OtherRealClass.php'),
        );
    }

    /**
     * @dataProvider dataResolveFile
     */
    public function testResolveFile($proxyDir, $proxyNamespace, $className, $expectedProxyFile)
    {
        $actualProxyFile = Autoloader::resolveFile($proxyDir, $proxyNamespace, $className);
        $this->assertEquals($expectedProxyFile, $actualProxyFile);
    }

    public function testAutoload()
    {
        if (file_exists(sys_get_temp_dir() ."/AutoloaderTestClass.php")) {
            unlink(sys_get_temp_dir() ."/AutoloaderTestClass.php");
        }

        $autoloader = Autoloader::register(sys_get_temp_dir(), 'ProxyAutoloaderTest', function($proxyDir, $proxyNamespace, $className) {
            file_put_contents(sys_get_temp_dir() . "/AutoloaderTestClass.php", "<?php namespace ProxyAutoloaderTest; class AutoloaderTestClass {} ");
        });

        $this->assertTrue(class_exists('ProxyAutoloaderTest\AutoloaderTestClass', true));
        unlink(sys_get_temp_dir() ."/AutoloaderTestClass.php");
    }
}

