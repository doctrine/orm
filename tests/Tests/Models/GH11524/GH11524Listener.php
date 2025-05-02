<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\GH11524;

use Doctrine\ORM\Event\PostLoadEventArgs;

class GH11524Listener
{
    public function postLoad(PostloadEventArgs $eventArgs): void
    {
        $object = $eventArgs->getObject();

        if (! $object instanceof GH11524Relation) {
            return;
        }

        $object->setCurrentLocale('en');
    }
}
