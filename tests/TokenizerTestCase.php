<?php
/*
 *  $Id: TokenizerTestCase.php 1181 2007-03-20 23:22:51Z gnat $
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
 * Doctrine_Tokenizer_TestCase
 * This class tests the functinality of Doctrine_Tokenizer component
 *
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 1181 $
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_Tokenizer_TestCase extends Doctrine_UnitTestCase
{
    public function prepareData()
    { }
    public function prepareTables()
    { }

    public function testSqlExplode()
    {
        $tokenizer = new Doctrine_Query_Tokenizer();
        
        $str = "word1 word2 word3";
        $a   = $tokenizer->sqlExplode($str);

        $this->assertEqual($a, array("word1", "word2", "word3"));

        $str = "word1 (word2 word3)";
        $a   = $tokenizer->sqlExplode($str);
        $this->assertEqual($a, array("word1", "(word2 word3)"));

        $str = "word1 'word2 word3'";
        $a   = $tokenizer->sqlExplode($str);
        $this->assertEqual($a, array("word1", "'word2 word3'"));

        $str = "word1 'word2 word3'";
        $a   = $tokenizer->sqlExplode($str);
        $this->assertEqual($a, array("word1", "'word2 word3'"));

        $str = "word1 \"word2 word3\"";
        $a   = $tokenizer->sqlExplode($str);
        $this->assertEqual($a, array("word1", "\"word2 word3\""));

        $str = "word1 ((word2) word3)";
        $a   = $tokenizer->sqlExplode($str);
        $this->assertEqual($a, array("word1", "((word2) word3)"));

        $str = "word1 ( (word2) 'word3')";
        $a   = $tokenizer->sqlExplode($str);
        $this->assertEqual($a, array("word1", "( (word2) 'word3')"));

        $str = "word1 ( \"(word2) 'word3')";
        $a   = $tokenizer->sqlExplode($str);
        $this->assertEqual($a, array("word1", "( \"(word2) 'word3')"));

        $str = "word1 ( ''(word2) 'word3')";
        $a   = $tokenizer->sqlExplode($str);
        $this->assertEqual($a, array("word1", "( ''(word2) 'word3')"));

        $str = "word1 ( '()()'(word2) 'word3')";
        $a   = $tokenizer->sqlExplode($str);
        $this->assertEqual($a, array("word1", "( '()()'(word2) 'word3')"));

        $str = "word1 'word2)() word3'";
        $a   = $tokenizer->sqlExplode($str);
        $this->assertEqual($a, array("word1", "'word2)() word3'"));

        $str = "word1 (word2() word3)";
        $a   = $tokenizer->sqlExplode($str);
        $this->assertEqual($a, array("word1", "(word2() word3)"));

        $str = "word1 \"word2)() word3\"";
        $a   = $tokenizer->sqlExplode($str);
        $this->assertEqual($a, array("word1", "\"word2)() word3\""));

        $str = "something (subquery '')";
        $a   = $tokenizer->sqlExplode($str);
        $this->assertEqual($a, array("something", "(subquery '')"));

        $str = "something ((  ))";
        $a   = $tokenizer->sqlExplode($str);
        $this->assertEqual($a, array("something", "((  ))"));
    }

    public function testSqlExplode2()
    {
        $tokenizer = new Doctrine_Query_Tokenizer();
        $str = 'rdbms (dbal OR database)';
        $a   = $tokenizer->sqlExplode($str, ' OR ');

        $this->assertEqual($a, array('rdbms (dbal OR database)'));
    }


    public function testQuoteExplodedShouldQuoteArray()
    {
        $tokenizer = new Doctrine_Query_Tokenizer();
        $term = $tokenizer->quoteExplode("test", array("'test'", "test2"));
        $this->assertEqual($term[0], "test");
    }
}
