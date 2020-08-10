<?php

declare(strict_types=1);

namespace Doctrine\ORM\Event;

use Doctrine\Common\EventArgs;
use Doctrine\ORM\PersistentCollection;

/**
 * Provides event arguments for the initializePersistentCollection event.
 */
class InitializePersistentCollectionEventArgs extends EventArgs
{
    /** @var PersistentCollection */
    private $collection;

    public function __construct(PersistentCollection $collection)
    {
        $this->collection = $collection;
    }

    /**
     * Retrieve associated collection.
     */
    public function getCollection() : PersistentCollection
    {
        return $this->collection;
    }
}
