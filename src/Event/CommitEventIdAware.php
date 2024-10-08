<?php

declare(strict_types=1);

namespace Doctrine\ORM\Event;

/**
 * Allows for discerning from which commit did the event come from.
 *
 * Nullability left as a fallback for old events, custom ones or unable to discern.
 */
interface CommitEventIdAware
{
    public function getCommitEventId(): ?string;
}
