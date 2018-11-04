<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Mapping\AssociationMetadata;
use Doctrine\ORM\Mapping\ToOneAssociationMetadata;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;
use ProxyManager\Proxy\GhostObjectInterface;
use ReflectionObject;
use function count;
use function fclose;
use function fopen;
use function fwrite;
use function gettype;
use function is_object;
use function spl_object_id;

/**
 * Use this logger to dump the identity map during the onFlush event. This is useful for debugging
 * weird UnitOfWork behavior with complex operations.
 */
class DebugUnitOfWorkListener
{
    /** @var string */
    private $file;

    /** @var string */
    private $context;

    /**
     * Pass a stream and context information for the debugging session.
     *
     * The stream can be php://output to print to the screen.
     *
     * @param string $file
     * @param string $context
     */
    public function __construct($file = 'php://output', $context = '')
    {
        $this->file    = $file;
        $this->context = $context;
    }

    public function onFlush(OnFlushEventArgs $args)
    {
        $this->dumpIdentityMap($args->getEntityManager());
    }

    /**
     * Dumps the contents of the identity map into a stream.
     */
    public function dumpIdentityMap(EntityManagerInterface $em)
    {
        $uow         = $em->getUnitOfWork();
        $identityMap = $uow->getIdentityMap();

        $fh = fopen($this->file, 'xb+');

        if (count($identityMap) === 0) {
            fwrite($fh, 'Flush Operation [' . $this->context . "] - Empty identity map.\n");

            return;
        }

        fwrite($fh, 'Flush Operation [' . $this->context . "] - Dumping identity map:\n");

        foreach ($identityMap as $className => $map) {
            fwrite($fh, 'Class: ' . $className . "\n");

            foreach ($map as $entity) {
                fwrite($fh, ' Entity: ' . $this->getIdString($entity, $uow) . ' ' . spl_object_id($entity) . "\n");
                fwrite($fh, "  Associations:\n");

                $cm = $em->getClassMetadata($className);

                foreach ($cm->getDeclaredPropertiesIterator() as $field => $association) {
                    if (! ($association instanceof AssociationMetadata)) {
                        continue;
                    }

                    fwrite($fh, '   ' . $field . ' ');

                    $value = $association->getValue($entity);

                    if ($value === null) {
                        fwrite($fh, " NULL\n");

                        continue;
                    }

                    if ($association instanceof ToOneAssociationMetadata) {
                        if ($value instanceof GhostObjectInterface && ! $value->isProxyInitialized()) {
                            fwrite($fh, '[PROXY] ');
                        }

                        fwrite($fh, $this->getIdString($value, $uow) . ' ' . spl_object_id($value) . "\n");
                    } else {
                        $initialized = ! ($value instanceof PersistentCollection) || $value->isInitialized();

                        if ($initialized) {
                            fwrite($fh, '[INITIALIZED] ' . $this->getType($value) . ' ' . count($value) . " elements\n");

                            foreach ($value as $obj) {
                                fwrite($fh, '    ' . $this->getIdString($obj, $uow) . ' ' . spl_object_id($obj) . "\n");
                            }
                        } else {
                            fwrite($fh, '[PROXY] ' . $this->getType($value) . " unknown element size\n");

                            foreach ($value->unwrap() as $obj) {
                                fwrite($fh, '    ' . $this->getIdString($obj, $uow) . ' ' . spl_object_id($obj) . "\n");
                            }
                        }
                    }
                }
            }
        }

        fclose($fh);
    }

    /**
     * @param mixed $var
     *
     * @return string
     */
    private function getType($var)
    {
        if (is_object($var)) {
            $refl = new ReflectionObject($var);

            return $refl->getShortName();
        }

        return gettype($var);
    }

    /**
     * @param object $entity
     *
     * @return string
     */
    private function getIdString($entity, UnitOfWork $uow)
    {
        if ($uow->isInIdentityMap($entity)) {
            $ids      = $uow->getEntityIdentifier($entity);
            $idstring = '';

            foreach ($ids as $k => $v) {
                $idstring .= $k . '=' . $v;
            }
        } else {
            $idstring = 'NEWOBJECT ';
        }

        $state = $uow->getEntityState($entity);

        if ($state === UnitOfWork::STATE_NEW) {
            $idstring .= ' [NEW]';
        } elseif ($state === UnitOfWork::STATE_REMOVED) {
            $idstring .= ' [REMOVED]';
        } elseif ($state === UnitOfWork::STATE_MANAGED) {
            $idstring .= ' [MANAGED]';
        } elseif ($state === UnitOfWork::STATE_DETACHED) {
            $idstring .= ' [DETACHED]';
        }

        return $idstring;
    }
}
