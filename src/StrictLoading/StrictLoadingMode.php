<?php

declare(strict_types=1);

namespace Doctrine\ORM\StrictLoading;

/**
 * How strictly the ORM should react to lazy loading.
 *
 * @see StrictLoading
 */
enum StrictLoadingMode
{
    /**
     * Lazy loading is allowed. This is the historical (and default) behavior.
     */
    case Disabled;

    /**
     * Only report lazy loads that repeat the same query shape.
     *
     * The first lazy load of a given association (or of a given entity class,
     * for to-one references) is allowed, every following one is reported. This
     * is the mode to run in an existing application: it only complains about
     * loads that actually degrade into an N+1 query.
     */
    case NPlusOneOnly;

    /**
     * Report every lazy load. Comparable to Rails' `strict_loading` default
     * mode: all data has to be fetched explicitly, up front.
     */
    case All;
}
