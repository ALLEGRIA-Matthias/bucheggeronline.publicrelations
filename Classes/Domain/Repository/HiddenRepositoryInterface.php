<?php

declare(strict_types=1);

namespace BucheggerOnline\Publicrelations\Domain\Repository;

/*
 * Interface to identify Repositories which can find hidden objects
 * Currently used in HiddenObjectsHelper
 */
interface HiddenRepositoryInterface
{
    /**
     * Find object by a given property value whether it is hidden or not.
     *
     * @param mixed $value The Value to compare against $property
     */
    public function findHiddenObject($value, string $property = 'uid'): ?object;
}
