<?php
/*
 * $Id$
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
 *
 * Small command line script to bundle Doctrine classes in a single file.
 *
 * @author      Nicolas Bérard-Nault <nicobn@php.net>
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version     $Revision$
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 */
 
if (count($argv) < 2) {
    echo "Usage: bundle.php [Target file] <Library directory>\n\n".
         "Note: If the library directory is ommited, the path will be deducted if possible\n";
    exit(1);
} else if (count($argv) == 3) {
    $doctrineBaseDir = $argv[2];
} else {
    $pathInfos = pathinfo($_SERVER['PHP_SELF']);
    
    $doctrineBaseDir = str_replace('tools/cli/'. $pathInfos['basename'], 
                        'lib', getcwd() .'/'. $_SERVER['SCRIPT_NAME'], $Cnt);
    
    if ($Cnt != 1) {
        echo "Can't find library directory, please specify it as an argument\n";
        exit(1);
    }
}

$targetFile = $argv[1];

echo "Target file: $targetFile" . PHP_EOL;
echo "Base directory: $doctrineBaseDir" . PHP_EOL;
echo PHP_EOL;

set_include_path(get_include_path() . PATH_SEPARATOR . $doctrineBaseDir);

require_once 'Doctrine.php';
require_once 'Doctrine/Compiler.php';

spl_autoload_register(array('Doctrine', 'autoload'));

echo "Bundling classes and interfaces..." . PHP_EOL;

Doctrine_Compiler::compile($targetFile);

echo PHP_EOL . "Bundle complete." . PHP_EOL;
echo "File: $targetFile (size: ". number_format(filesize($targetFile) / 1024, 2, '.', '') ." kb)." . PHP_EOL;

exit(0);
?>
