<?php

declare(strict_types=1);

namespace Padosoft\Rebel\Recovery;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

/**
 * Skeleton iniziale di padosoft/laravel-rebel-recovery. Implementazione in arrivo.
 */
final class RebelRecoveryServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('laravel-rebel-recovery');
    }
}
