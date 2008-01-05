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
 *
 * This is a port of sfFinder from the symfony-project.
 * http://www.symfony-project.com
 *
 * Allow to build rules to find files and directories.
 *
 * All rules may be invoked several times, except for ->in() method.
 * Some rules are cumulative (->name() for example) whereas others are destructive
 * (most recent value is used, ->maxDepth() method for example).
 *
 * All methods return the current Doctrine_FileFinder object to allow easy chaining:
 *
 * $files = Doctrine_FileFinder::type('file')->name('*.php')->in(.);
 *
 * Interface loosely based on perl File::Find::Rule module.
 *
 * Doctrine_FileFinder
 *
 * @package     Doctrine
 * @subpackage  FileFinder
 * @author      Symfony Project/Fabien Potencier <fabien.potencier@symfony-project.com>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version     $Revision$
 * @link        www.symfony-project.com
 * @since       1.0
 */
class Doctrine_FileFinder
{
  protected $type        = 'file';
  protected $names       = array();
  protected $prunes      = array();
  protected $discards    = array();
  protected $execs       = array();
  protected $minDepth    = 0;
  protected $sizes       = array();
  protected $maxDepth    = 1000000;
  protected $relative    = false;
  protected $followLink = false;

  /**
   * Sets maximum directory depth.
   *
   * Finder will descend at most $level levels of directories below the starting point.
   *
   * @param  integer level
   * @return object current Doctrine_FileFinder object
   */
  public function maxDepth($level)
  {
    $this->maxDepth = $level;

    return $this;
  }

  /**
   * Sets minimum directory depth.
   *
   * Finder will start applying tests at level $level.
   *
   * @param  integer level
   * @return object current Doctrine_FileFinder object
   */
  public function minDepth($level)
  {
    $this->minDepth = $level;

    return $this;
  }

  public function getType()
  {
    return $this->type;
  }

  /**
   * Sets the type of elements to returns.
   *
   * @param  string directory or file or any (for both file and directory)
   * @return object new Doctrine_FileFinder object
   */
  public static function type($name)
  {
    $finder = new Doctrine_FileFinder();

    return $finder->setType($name);
  }

  public function setType($name)
  {
    if (strtolower(substr($name, 0, 3)) == 'dir') {
      $this->type = 'directory';
    } else if (strtolower($name) == 'any') {
      $this->type = 'any';
    } else {
      $this->type = 'file';
    }

    return $this;
  }

  /*
   * glob, patterns (must be //) or strings
   */
  protected function toRegex($str)
  {
    if ($str{0} == '/' && $str{strlen($str) - 1} == '/') {
      return $str;
    } else {
      return Doctrine_FileFinder_GlobToRegex::globToRegex($str);
    }
  }

  protected function argsToArray($argList, $not = false)
  {
    $list = array();

    for ($i = 0; $i < count($argList); $i++) {
      if (is_array($argList[$i])) {
        foreach ($argList[$i] as $arg) {
          $list[] = array($not, $this->toRegex($arg));
        }
      } else {
        $list[] = array($not, $this->toRegex($argList[$i]));
      }
    }

    return $list;
  }

  /**
   * Adds rules that files must match.
   *
   * You can use patterns (delimited with / sign), globs or simple strings.
   *
   * $finder->name('*.php')
   * $finder->name('/\.php$/') // same as above
   * $finder->name('test.php')
   *
   * @param  list   a list of patterns, globs or strings
   * @return object current Doctrine_FileFinder object
   */
  public function name()
  {
    $args = func_get_args();
    $this->names = array_merge($this->names, $this->argsToArray($args));

    return $this;
  }

  /**
   * Adds rules that files must not match.
   *
   * @see    ->name()
   * @param  list   a list of patterns, globs or strings
   * @return object current Doctrine_FileFinder object
   */
  public function notName()
  {
    $args = func_get_args();
    $this->names = array_merge($this->names, $this->argsToArray($args, true));

    return $this;
  }

  /**
   * Adds tests for file sizes.
   *
   * $finder->size('> 10K');
   * $finder->size('<= 1Ki');
   * $finder->size(4);
   *
   * @param  list   a list of comparison strings
   * @return object current Doctrine_FileFinder object
   */
  public function size()
  {
    $args = func_get_args();
    for ($i = 0; $i < count($args); $i++) {
      $this->sizes[] = new Doctrine_FileFinder_NumberCompare($args[$i]);
    }

    return $this;
  }

  /**
   * Traverses no further.
   *
   * @param  list   a list of patterns, globs to match
   * @return object current Doctrine_FileFinder object
   */
  public function prune()
  {
    $args = func_get_args();
    $this->prunes = array_merge($this->prunes, $this->argsToArray($args));

    return $this;
  }

  /**
   * Discards elements that matches.
   *
   * @param  list   a list of patterns, globs to match
   * @return object current Doctrine_FileFinder object
   */
  public function discard()
  {
    $args = func_get_args();
    $this->discards = array_merge($this->discards, $this->argsToArray($args));

    return $this;
  }

  /**
   * Ignores version control directories.
   *
   * Currently supports subversion, CVS, DARCS, Gnu Arch, Monotone, Bazaar-NG
   *
   * @return object current Doctrine_FileFinder object
   */
  public function ignoreVersionControl()
  {
    $ignores = array('.svn', 'CVS', '_darcs', '.arch-params', '.monotone', '.bzr');

    return $this->discard($ignores)->prune($ignores);
  }

  /**
   * Executes function or method for each element.
   *
   * Element match if functino or method returns true.
   *
   * $finder->exec('myfunction');
   * $finder->exec(array($object, 'mymethod'));
   *
   * @param  mixed  function or method to call
   * @return object current Doctrine_FileFinder object
   */
  public function exec()
  {
    $args = func_get_args();
    for ($i = 0; $i < count($args); $i++) {
      if (is_array($args[$i]) && !method_exists($args[$i][0], $args[$i][1])) {
        throw new Doctrine_Exception(sprintf('method "%s" does not exist for object "%s".', $args[$i][1], $args[$i][0]));
      } else if ( ! is_array($args[$i]) && !function_exists($args[$i])) {
        throw new Doctrine_Exception(sprintf('function "%s" does not exist.', $args[$i]));
      }

      $this->execs[] = $args[$i];
    }

    return $this;
  }

  /**
   * Returns relative paths for all files and directories.
   *
   * @return object current Doctrine_FileFinder object
   */
  public function relative()
  {
    $this->relative = true;

    return $this;
  }

  /**
   * Symlink following.
   *
   * @return object current Doctrine_FileFinder object
   */
  public function followLink()
  {
    $this->followLink = true;

    return $this;
  }

  /**
   * Searches files and directories which match defined rules.
   *
   * @return array list of files and directories
   */
  public function in()
  {
    $files    = array();
    $here_dir = getcwd();
    $numargs  = func_num_args();
    $argList = func_get_args(); 

    // first argument is an array?
    if ($numargs == 1 && is_array($argList[0])) {
      $argList = $argList[0];
      $numargs  = count($argList);
    }

    for ($i = 0; $i < $numargs; $i++) {
      $realDir = realpath($argList[$i]);

      // absolute path?
      if ( ! self::isPathAbsolute($realDir)) {
        $dir = $here_dir . DIRECTORY_SEPARATOR . $realDir;
      } else {
        $dir = $realDir;
      }

      if ( ! is_dir($realDir)) {
        continue;
      }

      if ($this->relative) {
        $files = array_merge($files, str_replace($dir . DIRECTORY_SEPARATOR, '', $this->_searchIn($dir)));
      } else {
        $files = array_merge($files, $this->_searchIn($dir));
      }
    }

    return array_unique($files);
  }

  protected function _searchIn($dir, $depth = 0)
  {
    if ($depth > $this->maxDepth) {
      return array();
    }

    if (is_link($dir) && !$this->followLink) {
      return array();
    }

    $files = array();

    if (is_dir($dir)) {
      $currentDir = opendir($dir);
      while (false !== $entryName = readdir($currentDir)) {
        if ($entryName == '.' || $entryName == '..') {
            continue;
        }
        
        $currentEntry = $dir . DIRECTORY_SEPARATOR . $entryName;
        if (is_link($currentEntry) && !$this->followLink) {
          continue;
        }

        if (is_dir($currentEntry)) {
          if (($this->type == 'directory' || $this->type == 'any') && ($depth >= $this->minDepth) && !$this->_isDiscarded($dir, $entryName) && $this->_matchNames($dir, $entryName) && $this->_execOk($dir, $entryName)) {
            $files[] = realpath($currentEntry);
          }

          if ( ! $this->_isPruned($dir, $entryName)) {
            $files = array_merge($files, $this->_searchIn($currentEntry, $depth + 1));
          }
        } else {
          if (($this->type != 'directory' || $this->type == 'any') && ($depth >= $this->minDepth) && !$this->_isDiscarded($dir, $entryName) && $this->_matchNames($dir, $entryName) && $this->_sizeOk($dir, $entryName) && $this->_execOk($dir, $entryName)) {
            $files[] = realpath($currentEntry);
          }
        }
      }

      closedir($currentDir);
    }

    return $files;
  }

  protected function _matchNames($dir, $entry)
  {
    if ( ! count($this->names)) {
        return true;
    }
    
    // we must match one "not_name" rules to be ko
    $oneNotNameRule = false;
    foreach ($this->names as $args) {
      list($not, $regex) = $args;
      if ($not) {
        $oneNotNameRule = true;
        if (preg_match($regex, $entry)) {
          return false;
        }
      }
    }

    $oneNameRule = false;
    // we must match one "name" rules to be ok
    foreach ($this->names as $args) {
      list($not, $regex) = $args;
      if ( ! $not) {
        $oneNameRule = true;
        if (preg_match($regex, $entry)) {
          return true;
        }
      }
    }

    if ($oneNotNameRule && $oneNameRule) {
      return false;
    } else if ($oneNotNameRule) {
      return true;
    } else if ($oneNameRule) {
      return false;
    } else {
      return true;
    }
  }

  protected function _sizeOk($dir, $entry)
  {
    if ( ! count($this->sizes)) {
        return true;
    }
    
    if ( ! is_file($dir . DIRECTORY_SEPARATOR . $entry)) {
        return true;
    }
    
    $filesize = filesize($dir . DIRECTORY_SEPARATOR . $entry);
    foreach ($this->sizes as $number_compare) {
      if ( ! $number_compare->test($filesize)) {
        return false;
      }
    }

    return true;
  }

  protected function _isPruned($dir, $entry)
  {
    if ( ! count($this->prunes)) {
        return false;
    }
    
    foreach ($this->prunes as $args) {
      $regex = $args[1];
      if (preg_match($regex, $entry)) {
          return true;
      }
    }

    return false;
  }

  protected function _isDiscarded($dir, $entry)
  {
    if ( ! count($this->discards)) {
        return false;
    }
    
    foreach ($this->discards as $args) {
      $regex = $args[1];
      if (preg_match($regex, $entry)) {
          return true;
      }
    }

    return false;
  }

  protected function _execOk($dir, $entry)
  {
    if ( ! count($this->execs)) {
        return true;
    }
    
    foreach ($this->execs as $exec) {
      if ( ! call_user_func_array($exec, array($dir, $entry))) {
          return false;
      }
    }

    return true;
  }

  public static function isPathAbsolute($path)
  {
    if ($path{0} == '/' || $path{0} == '\\' ||
        (strlen($path) > 3 && ctype_alpha($path{0}) &&
         $path{1} == ':' &&
         ($path{2} == '\\' || $path{2} == '/')
        )
       ) {
      return true;
    }

    return false;
  }
}