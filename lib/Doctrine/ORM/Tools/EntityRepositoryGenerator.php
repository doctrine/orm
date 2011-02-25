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
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Tools;

use \Doctrine\ORM\Tools\Code\Writer;

/**
 * Class to generate entity repository classes
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 * @author  Mykhailo Stadnyk <mikhus@gmail.com>
 */
class EntityRepositoryGenerator
{

	/**
     * @var \Doctrine\ORM\Tools\Code\Writer
     */
    private $_codeWriter = null;

    /**
     * Constructor
     * 
     * @param \Doctrine\ORM\Tools\Code\Writer $codeWriter
     */
    public function __construct( Writer $codeWriter) {
    	$this->_codeWriter = $codeWriter;
    }

    public function generateEntityRepositoryClass($fullClassName, $fullParentClassName)
    {
        $namespace       = substr($fullClassName, 0, strrpos($fullClassName, '\\'));
        $className       = substr($fullClassName, strrpos($fullClassName, '\\') + 1, strlen($fullClassName));
        $parentClassName = substr($fullParentClassName, strrpos($fullParentClassName, '\\') + 1, strlen($fullParentClassName));

        $variables = array(
            '<namespace>'       => $namespace,
        	'<use>'             => $fullParentClassName,
            '<className>'       => $className,
        	'<parentClassName>' => $parentClassName
        );

        return $this->_codeWriter->renderTemplate('class', $variables);
    }

    public function writeEntityRepositoryClass($fullClassName, $outputDirectory, $parentClassName)
    {
        $code = $this->generateEntityRepositoryClass($fullClassName, $parentClassName);

        $path = $outputDirectory . DIRECTORY_SEPARATOR
              . str_replace('\\', \DIRECTORY_SEPARATOR, $fullClassName) . '.php';
        $dir = dirname($path);

        if ( ! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        if ( ! file_exists($path)) {
            file_put_contents($path, $code);
        }
    }
}