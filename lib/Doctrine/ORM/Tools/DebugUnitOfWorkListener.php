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
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Tools;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;

/**
 * Use this logger to dump the identity map during the onFlush event. This is useful for debugging
 * weird UnitOfWork behavior with complex operations.
 */
class DebugUnitOfWorkListener
{
    private $file;
    private $context;

    /**
     * Pass a stream and contet information for the debugging session.
     *
     * The stream can be php://output to print to the screen.
     *
     * @param string $file
     * @param string $context
     */
    public function __construct($file = 'php://output', $context = '')
    {
        $this->file = $file;
        $this->context = $context;
    }

    public function onFlush(OnFlushEventArgs $args)
    {
        $this->dumpIdentityMap($args->getEntityManager());
    }

    /**
     * Dump the contents of the identity map into a stream.
     *
     * @param EntityManager $em
     * @return void
     */
    public function dumpIdentityMap(EntityManager $em)
    {
        $uow = $em->getUnitOfWork();
        $identityMap = $uow->getIdentityMap();

        $fh = fopen($this->file, "x+");
        if (count($identityMap) == 0) {
            fwrite($fh, "Flush Operation [".$this->context."] - Empty identity map.\n");
            return;
        }

        fwrite($fh, "Flush Operation [".$this->context."] - Dumping identity map:\n");
        foreach ($identityMap as $className => $map) {
            fwrite($fh, "Class: ". $className . "\n");
            foreach ($map as $entity) {
                fwrite($fh, " Entity: " . $this->getIdString($entity, $uow) . " " . spl_object_hash($entity)."\n");
                fwrite($fh, "  Associations:\n");

                $cm = $em->getClassMetadata($className);
                foreach ($cm->associationMappings as $field => $assoc) {
                    fwrite($fh, "   " . $field . " ");
                    $value = $cm->reflFields[$field]->getValue($entity);

                    if ($assoc['type'] & ClassMetadata::TO_ONE) {
                        if ($value === null) {
                            fwrite($fh, " NULL\n");
                        } else {
                            if ($value instanceof Proxy && !$value->__isInitialized__) {
                                fwrite($fh, "[PROXY] ");
                            }

                            fwrite($fh, $this->getIdString($value, $uow) . " " . spl_object_hash($value) . "\n");
                        }
                    } else {
                        $initialized = !($value instanceof PersistentCollection) || $value->isInitialized();
                        if ($value === null) {
                            fwrite($fh, " NULL\n");
                        } else if ($initialized) {
                            fwrite($fh, "[INITIALIZED] " . $this->getType($value). " " . count($value) . " elements\n");
                            foreach ($value as $obj) {
                                fwrite($fh, "    " . $this->getIdString($obj, $uow) . " " . spl_object_hash($obj)."\n");
                            }
                        } else {
                            fwrite($fh, "[PROXY] " . $this->getType($value) . " unknown element size\n");
                            foreach ($value->unwrap() as $obj) {
                                fwrite($fh, "    " . $this->getIdString($obj, $uow) . " " . spl_object_hash($obj)."\n");
                            }
                        }
                    }
                }
            }
        }
        fclose($fh);
    }

    private function getType($var)
    {
        if (is_object($var)) {
            $refl = new \ReflectionObject($var);
            return $refl->getShortname();
        } else {
            return gettype($var);
        }
    }

    private function getIdString($entity, $uow)
    {
        if ($uow->isInIdentityMap($entity)) {
            $ids = $uow->getEntityIdentifier($entity);
            $idstring = "";
            foreach ($ids as $k => $v) {
                $idstring .= $k."=".$v;
            }
        } else {
            $idstring = "NEWOBJECT ";
        }

        $state = $uow->getEntityState($entity);
        if ($state == UnitOfWork::STATE_NEW) {
            $idstring .= " [NEW]";
        } else if ($state == UnitOfWork::STATE_REMOVED) {
            $idstring .= " [REMOVED]";
        } else if ($state == UnitOfWork::STATE_MANAGED) {
            $idstring .= " [MANAGED]";
        } else if ($state == UnitOfwork::STATE_DETACHED) {
            $idstring .= " [DETACHED]";
        }

        return $idstring;
    }
}
