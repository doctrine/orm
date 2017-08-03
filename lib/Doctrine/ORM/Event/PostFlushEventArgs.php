<?php
declare(strict_types=1);

namespace Doctrine\ORM\Event;

use Doctrine\Common\EventArgs;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Provides event arguments for the postFlush event.
 *
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 * @link        www.doctrine-project.org
 * @since       2.0
 * @author      Daniel Freudenberger <df@rebuy.de>
 */
class PostFlushEventArgs extends EventArgs
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * Constructor.
     *
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Retrieves associated EntityManager.
     *
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEntityManager()
    {
        return $this->em;
    }
}
