<?php

namespace Notch\Framework\Currency\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $guarded = ['id'];

    public function __construct()
    {
        $this->setConnection(config('currency.connection', config('database.default')));
    }
}
