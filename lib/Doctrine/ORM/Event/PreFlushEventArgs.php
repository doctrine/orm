<?php

declare(strict_types=1);

namespace Doctrine\ORM\Event;

use Doctrine\Common\EventArgs;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Provides event arguments for the preFlush event.
 *
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 * @link        www.doctrine-project.com
 * @since       2.0
 * @author      Roman Borschel <roman@code-factory.de>
 * @author      Benjamin Eberlei <kontakt@beberlei.de>
 */
class PreFlushEventArgs extends EventArgs
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
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEntityManager()
    {
        return $this->em;
    }
}
