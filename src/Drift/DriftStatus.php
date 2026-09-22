<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Scaffold\Drift;

/**
 * The comparison outcome for one framework-neutral file.
 */
enum DriftStatus: string
{
    case Identical = 'identical';
    case Drifted = 'drifted';
    case Missing = 'missing';
}
