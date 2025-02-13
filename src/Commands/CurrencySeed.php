<?php

namespace Notch\Framework\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Notch\Framework\Currency;

class CurrencySeed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'currency:seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed currency into database';

    /**
     * Currency storage instance
     *
     * @var \RestUniverse\Currency\Contracts\DriverInterface
     */
    protected $storage;

    /**
     * All installable currencies.
     *
     * @var array
     */
    protected $currencies;

    protected $rates;

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        $this->storage = app(Currency::class)->getDriver();

        parent::__construct();
    }

    /**
     * Execute the console command for Laravel 5.4 and below
     *
     * @return void
     */
    public function fire()
    {
        $this->handle();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->info('Seeding Currency');

        $str = file_get_contents(dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'currencies.json');

        switch (config('currency.driver')) {
            case 'model':
                $model = config('currency.drivers.model.class');

                if ($model::count() == 0) {
                    collect(json_decode($str, true))->each(function ($currency) use ($model) {
                        $model::create(Arr::only($currency, ['name', 'code', 'symbol', 'format', 'exchange_rate', 'fraction']));
                    });
                }

                break;

            case 'database':
                $table = config('currency.drivers.database.table');
                $this->currencies = DB::table($table)->get();

                if (count($this->currencies) == 0) {
                    collect(json_decode($str, true))->each(function ($currency) use ($table) {
                        DB::table($table)->insert(Arr::only($currency, ['name', 'code', 'symbol', 'format', 'exchange_rate', 'fraction']));
                    });
                }

                break;
            case 'filesystem':
                $path = config('currency.drivers.filesystem.path');

                Storage::disk(config('currency.drivers.filesystem.disk'))->put($path, json_encode(json_decode($str)));
                break;
        }
    }
}
