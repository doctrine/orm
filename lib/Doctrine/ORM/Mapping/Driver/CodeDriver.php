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

#namespace Doctrine\ORM\Mapping\Driver;

/**
 * The code metadata driver loads the metadata of the classes through invoking
 * a static callback method that needs to be implemented when using this driver.
 *
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Roman Borschel <roman@code-factory.org>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version     $Revision$
 * @link        www.phpdoctrine.org
 * @since       2.0
 */
class Doctrine_ORM_Mapping_Driver_CodeDriver
{
    /**
     * Name of the callback method.
     * 
     * @todo We could make the name of the callback methods customizable for users.
     */
    private $_callback = 'initMetadata';

    public function setCallback($callback)
    {
        $this->_callback = $callback;
    }

    public function getCallback()
    {
        return $this->_callback;
    }

    /**
     * Loads the metadata for the specified class into the provided container.
     */
    public function loadMetadataForClass($className, Doctrine_ORM_Mapping_ClassMetadata $metadata)
    {
        if ( ! method_exists($className, $this->_callback)) {
            throw new Doctrine_Exception("Unable to load metadata for class"
                    . " '$className'. Callback method 'initMetadata' not found.");
        }
        call_user_func_array(array($className, $this->_callback), array($metadata));
    }   
}