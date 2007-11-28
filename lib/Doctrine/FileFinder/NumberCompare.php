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
 * Doctrine_FileFinder_NumberCompare
 *
 * Numeric comparisons.
 *
 * Doctrine_FileFinder_NumberCompare compiles a simple comparison to an anonymous
 * subroutine, which you can call with a value to be tested again.
 *
 * Now this would be very pointless, if Doctrine_FileFinder_NumberCompare didn't understand
 * magnitudes.
 *
 * The target value may use magnitudes of kilobytes (k, ki),
 * megabytes (m, mi), or gigabytes (g, gi).  Those suffixed
 * with an i use the appropriate 2**n version in accordance with the
 * IEC standard: http://physics.nist.gov/cuu/Units/binary.html
 *
 * based on perl Number::Compare module.
 *
 * @package    Doctrine
 * @subpackage FileFinder
 * @author     Fabien Potencier <fabien.potencier@gmail.com> php port
 * @author     Richard Clamp <richardc@unixbeard.net> perl version
 * @copyright  2004-2005 Fabien Potencier <fabien.potencier@gmail.com>
 * @copyright  2002 Richard Clamp <richardc@unixbeard.net>
 * @see        http://physics.nist.gov/cuu/Units/binary.html
 * @version    SVN: $Id: Doctrine_FileFinder.class.php 5110 2007-09-15 12:07:18Z fabien $
 */
class Doctrine_FileFinder_NumberCompare
{
  protected $test = '';

  public function __construct($test)
  {
    $this->test = $test;
  }

  public function test($number)
  {
    if ( ! preg_match('{^([<>]=?)?(.*?)([kmg]i?)?$}i', $this->test, $matches)) {
      throw new Doctrine_Exception(sprintf('don\'t understand "%s" as a test.', $this->test));
    }

    $target = array_key_exists(2, $matches) ? $matches[2] : '';
    $magnitude = array_key_exists(3, $matches) ? $matches[3] : '';
    if (strtolower($magnitude) == 'k') {
        $target *=           1000;
    }
    
    if (strtolower($magnitude) == 'ki') {
        $target *=           1024;
    }
    
    if (strtolower($magnitude) == 'm') {
        $target *=        1000000;
    }
    
    if (strtolower($magnitude) == 'mi') {
        $target *=      1024*1024;
    }
    
    if (strtolower($magnitude) == 'g') {
        $target *=     1000000000;
    }
    
    if (strtolower($magnitude) == 'gi') {
        $target *= 1024*1024*1024;
    }
    
    $comparison = array_key_exists(1, $matches) ? $matches[1] : '==';

    if ($comparison == '==' || $comparison == '') {
      return ($number == $target);
    } else if ($comparison == '>') {
      return ($number > $target);
    } else if ($comparison == '>=') {
      return ($number >= $target);
    } else if ($comparison == '<') {
      return ($number < $target);
    } else if ($comparison == '<=') {
      return ($number <= $target);
    }

    return false;
  }
}