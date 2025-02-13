<?php

namespace Notch\Framework;

use Notch\Framework\Commands\CurrencyHydrate;
use Notch\Framework\Commands\CurrencySeed;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FrameworkServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('framework')
            ->hasConfigFile('balances')
            ->hasConfigFile('currency')
            ->hasViews()
            ->hasMigration('create_balances_table')
            ->hasMigration('create_currencies_table')
            ->hasCommand(CurrencyHydrate::class)
            ->hasCommand(CurrencySeed::class);
    }
}
