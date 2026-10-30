<?php

declare(strict_types=1);

namespace Doctrine\ORM;

/**
 * The kind of ORM operation that issued a lazy loading query.
 */
enum LazyLoadingKind: string
{
    /** The query initializes a lazy proxy or a lazy to-one association. */
    case ToOne = 'to_one';

    /** The query initializes a lazy or extra-lazy collection. */
    case Collection = 'collection';
}
