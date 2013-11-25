<?php
/**
 * @author eshenbrener
 * @since 25.11.2013 17:42
 */

namespace Doctrine\ORM\Event;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\EventArgs;

/**
 * Class PreQueryExecuteEvent
 */
class PreQueryExecuteEvent extends EventArgs
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var \Doctrine\ORM\AbstractQuery
     */
    protected $query;

    /**
     * Parameters passed to Query#execute() method
     *
     * @var \Doctrine\Common\Collections\ArrayCollection|array|null
     */
    protected $parameters;

    /**
     * @var integer|null Processing mode to be used during the hydration process.
     */
    protected $hydrationMode;

    /**
     * @param EntityManager $em
     * @param AbstractQuery $query
     * @param $parameters
     * @param $hydrationMode
     */
    public function __construct(EntityManager $em, AbstractQuery $query, $parameters, $hydrationMode)
    {
        $this->em = $em;
        $this->query = $query;
        $this->parameters = $parameters;
        $this->hydrationMode = $hydrationMode;
    }

    /**
     * Get EntityManager
     *
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEntityManager()
    {
        return $this->em;
    }

    /**
     * Get HydrationMode
     *
     * @return int|null
     */
    public function getHydrationMode()
    {
        return $this->hydrationMode;
    }

    /**
     * Get Parameters
     *
     * @return array|\Doctrine\Common\Collections\ArrayCollection|null
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Get Query
     *
     * @return \Doctrine\ORM\AbstractQuery
     */
    public function getQuery()
    {
        return $this->query;
    }
}
