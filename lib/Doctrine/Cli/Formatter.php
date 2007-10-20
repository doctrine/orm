<?php
/*
 * This file is part of the symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*
 *  $Id: Formatter.php 2702 2007-10-03 21:43:22Z Jonathan.Wage $
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
 * Doctrine_Cli_Formatter provides methods to format text to be displayed on a console.
 *
 * @package    Doctrine
 * @subpackage Cli
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id: Doctrine_Cli_Formatter.class.php 5250 2007-09-24 08:11:50Z fabien $
 */
class Doctrine_Cli_Formatter
{
  protected
    $size = 65;

  function __construct($maxLineSize = 65)
  {
    $this->size = $maxLineSize;
  }

  /**
   * Formats a text according to the given parameters.
   *
   * @param  string The test to style
   * @param  mixed  An array of parameters
   * @param  stream A stream (default to STDOUT)
   *
   * @return string The formatted text
   */
  public function format($text = '', $parameters = array(), $stream = STDOUT)
  {
    return $text;
  }

  /**
   * Formats a message within a section.
   *
   * @param string  The section name
   * @param string  The text message
   * @param integer The maximum size allowed for a line (65 by default)
   */
  public function formatSection($section, $text, $size = null)
  {
    return sprintf(">> %-$9s %s", $section, $this->excerpt($text, $size));
  }

  /**
   * Truncates a line.
   *
   * @param string  The text
   * @param integer The maximum size of the returned string (65 by default)
   *
   * @return string The truncated string
   */
  public function excerpt($text, $size = null)
  {
    if (!$size)
    {
      $size = $this->size;
    }

    if (strlen($text) < $size)
    {
      return $text;
    }

    $subsize = floor(($size - 3) / 2);

    return substr($text, 0, $subsize).'...'.substr($text, -$subsize);
  }

  /**
   * Sets the maximum line size.
   *
   * @param integer The maximum line size for a message
   */
  public function setMaxLineSize($size)
  {
    $this->size = $size;
  }
}