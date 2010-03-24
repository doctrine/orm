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
 
namespace Doctrine\ORM\Tools\CLI\Tasks;

use Doctrine\Common\CLI\Tasks\AbstractTask,
    Doctrine\Common\CLI\CLIException;

/**
 * CLI Task to ensure that Doctrine is properly configured for a production environment.
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class EnsureProductionSettingsTask extends AbstractTask
{
    /**
     * @inheritdoc
     */
    public function buildDocumentation()
    {
        $doc = $this->getDocumentation();
        $doc->setName('ensure-production-settings')
            ->setDescription('Verify that Doctrine is properly configured for a production environment.');
    }
    
    /**
     * @inheritdoc
     */
    public function validate()
    {
        // Check if we have an active EntityManager
        $em = $this->getConfiguration()->getAttribute('em');
        
        if ($em === null) {
            throw new CLIException(
                "Attribute 'em' of CLI Configuration is not defined or it is not a valid EntityManager."
            );
        }
        
        return true;
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $em = $this->getConfiguration()->getAttribute('em');
        $em->getConfiguration()->ensureProductionSettings();

        $this->getPrinter()->writeln('Environment is correctly configured for production.');
    }
}