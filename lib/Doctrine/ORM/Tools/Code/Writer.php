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

namespace Doctrine\ORM\Tools\Code;

/**
 * Abstract writer class for code generator templates
 * 
 * If there is a requirement to tune automatically generated code - this is a right
 * place to do that. To configure your own code templates it's required to
 * extend this writer class and configure entity manager to use your own custom
 * implementation. Usually it can be done in cli-config.php:
 * 
 * $config->setEntityWriterImpl( new \My\EntityWriter);
 * $config->setRepositoryWriterImpl( new \My\RepositoryWriter);
 * 
 * Don't forget that custom classes should be correcly included or handled by an autoloader.
 * 
 * To implement custom writers it is enough to implement init() abstract method within them,
 * which will preset custom templates, which further will be used by Doctrine's
 * code generator. To learn more about how templates are organized, see default Doctrine's
 * templates implementation:
 * 
 * @see \Doctrine\ORM\Tools\Code\Writer\Entity
 * @see \Doctrine\ORM\Tools\Code\Writer\Repository
 * 
 * @since 2.0
 * @author  Mykhailo Stadnyk <mikhus@gmail.com>
 */
abstract class Writer
{
    /**
     * Holder for collection of templates
     * 
     * @var array
     */
    private $templates = array();

    /**
     * Sets the template to internal holder
     * 
     * @param  string $name
     * @param  string $body
     * @return \Doctrine\ORM\Tools\Code\Writer
     */
    final public function setTemplate($name, $body)
    {
        $this->templates[$name] = $body;
        return $this;
    }

    /**
     * Gets the template from internal holder by its name
     * 
     * @param  string $name
     * @return string
     */
    final public function getTemplate($name)
    {
        if (!$this->templates) {
            $this->init();
        }

        if (!isset($this->templates[$name])) {
            throw \Doctrine\ORM\ORMException::missingCodeWriterTemplate($this, $name);
        }

        return $this->templates[$name];
    }

    /**
     * Renders a template extracted from internal holder by its name. Replaces a placeholders defined in
     * template with a passed replacements. Returns a rendered code generated within specified template.
     * 
     * @param string $name
     * @param array $replacements
     * 
     */
    public function renderTemplate($name, array $replacements = array())
    {
        return str_replace(
            array_keys($replacements), 
            array_values($replacements), 
            $this->getTemplate($name)
        );
    }

    /**
     * Returns all the templates stored within current writer
     * 
     * @return array
     */
    final public function getTemplates()
    {
        if (!$this->templates) {
            $this->init();
        }

        return $this->templates;
    }

    /**
     * Initialized the code writer
     * 
     * @internal This method should be implemented with a custom class. In this method
     * required templates should be initialized and added to internal templates
     * holder.
     */
    abstract public function init();
}
