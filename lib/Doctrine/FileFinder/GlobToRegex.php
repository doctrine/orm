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
 * Doctrine_FileFinder_GlobToRegex
 *
 * Match globbing patterns against text.
 *
 *   if match_glob("foo.*", "foo.bar") echo "matched\n";
 *
 * // prints foo.bar and foo.baz
 * $regex = globToRegex("foo.*");
 * for (array('foo.bar', 'foo.baz', 'foo', 'bar') as $t)
 * {
 *   if (/$regex/) echo "matched: $car\n";
 * }
 *
 * Doctrine_FileFinder_GlobToRegex implements glob(3) style matching that can be used to match
 * against text, rather than fetching names from a filesystem.
 *
 * based on perl Text::Glob module.
 *
 * @package    Doctrine
 * @subpackage FileFinder
 * @author     Fabien Potencier <fabien.potencier@gmail.com> php port
 * @author     Richard Clamp <richardc@unixbeard.net> perl version
 * @copyright  2004-2005 Fabien Potencier <fabien.potencier@gmail.com>
 * @copyright  2002 Richard Clamp <richardc@unixbeard.net>
 * @version    SVN: $Id: Doctrine_FileFinder.class.php 5110 2007-09-15 12:07:18Z fabien $
 */
class Doctrine_FileFinder_GlobToRegex
{
  protected static $strictLeadingDot = true;
  protected static $strictWildcardSlash = true;

  public static function setStrictLeadingDot($boolean)
  {
    self::$strictLeadingDot = $boolean;
  }

  public static function setStrictWildcardSlash($boolean)
  {
    self::$strictWildcardSlash = $boolean;
  }

  /**
   * Returns a compiled regex which is the equiavlent of the globbing pattern.
   *
   * @param  string glob pattern
   * @return string regex
   */
  public static function globToRegex($glob)
  {
    $firstByte = true;
    $escaping = false;
    $inCurlies = 0;
    $regex = '';
    for ($i = 0; $i < strlen($glob); $i++) {
      $car = $glob[$i];
      if ($firstByte) {
        if (self::$strictLeadingDot && $car != '.') {
          $regex .= '(?=[^\.])';
        }

        $firstByte = false;
      }

      if ($car == '/') {
        $firstByte = true;
      }

      if ($car == '.' || $car == '(' || $car == ')' || $car == '|' || $car == '+' || $car == '^' || $car == '$') {
        $regex .= "\\$car";
      } else if ($car == '*') {
        $regex .= ($escaping ? "\\*" : (self::$strictWildcardSlash ? "[^/]*" : ".*"));
      } else if ($car == '?') {
        $regex .= ($escaping ? "\\?" : (self::$strictWildcardSlash ? "[^/]" : "."));
      } else if ($car == '{') {
        $regex .= ($escaping ? "\\{" : "(");
        if ( ! $escaping) {
            ++$inCurlies;
        }
      } else if ($car == '}' && $inCurlies) {
        $regex .= ($escaping ? "}" : ")");
        if ( ! $escaping) {
            --$inCurlies;
        }
      } else if ($car == ',' && $inCurlies) {
        $regex .= ($escaping ? "," : "|");
      } else if ($car == "\\") {
        if ($escaping) {
          $regex .= "\\\\";
          $escaping = false;
        } else {
          $escaping = true;
        }

        continue;
      } else {
        $regex .= $car;
        $escaping = false;
      }

      $escaping = false;
    }

    return "#^$regex$#";
  }
}