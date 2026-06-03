<?php

declare(strict_types=1);

namespace Padosoft\Rebel\Recovery;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

/**
 * High-assurance account recovery for Laravel Rebel: single-use, HMAC-hashed recovery
 * (backup) codes generated once at enrolment.
 */
final class RebelRecoveryServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-rebel-recovery')
            ->hasConfigFile('rebel-recovery')
            ->hasMigration('create_rebel_recovery_codes_table');
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(RecoveryCodeGenerator::class);
        $this->app->singleton(RecoveryCodeManager::class);
    }
}
