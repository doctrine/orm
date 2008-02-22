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
 * <http://www.phpdoctrine.org>.
 */

/**
 * Doctrine_Parser_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Parser_TestCase extends Doctrine_UnitTestCase 
{
    public function testGetParserInstance()
    {
        $instance = Doctrine_Parser::getParser('Yml');
        
        if ($instance instanceof Doctrine_Parser_Yml) {
            $this->pass();
        } else {
            $this->fail();
        }
    }
    
    public function testFacadeLoadAndDump()
    {
        Doctrine_Parser::dump(array('test' => 'good job', 'test2' => true, array('testing' => false)), 'yml', 'test.yml');
        $array = Doctrine_Parser::load('test.yml', 'yml');
        
        $this->assertEqual($array, array('test' => 'good job', 'test2' => true, array('testing' => false)));
    }
    
    public function testParserSupportsEmbeddingPhpSyntax()
    {
        $parser = Doctrine_Parser::getParser('Yml');
        $yml = "---
test: good job
test2: true
testing: <?php echo 'false'.\"\n\"; ?>
w00t: not now
";
        $data = $parser->doLoad($yml);
        
        $array = $parser->loadData($data);
        
        $this->assertEqual($array, array('test' => 'good job', 'test2' => true, 'testing' => false, 'w00t' => 'not now'));
    }
    
    public function testParserWritingToDisk()
    {
        $parser = Doctrine_Parser::getParser('Yml');
        $parser->doDump('test', 'test.yml');
        
        $this->assertEqual('test', file_get_contents('test.yml'));
    }
    
    public function testParserReturningLoadedData()
    {
        $parser = Doctrine_Parser::getParser('Yml');
        $result = $parser->doDump('test');
        
        $this->assertEqual('test', $result);
    }
    
    public function testLoadFromString()
    {
        $yml = "---
test: good job
test2: true
testing: <?php echo 'false'.\"\n\"; ?>
w00t: not now
";

        $array = Doctrine_Parser::load($yml, 'yml');
        
        $this->assertEqual($array, array('test' => 'good job', 'test2' => true, 'testing' => false, 'w00t' => 'not now'));
    }
}
