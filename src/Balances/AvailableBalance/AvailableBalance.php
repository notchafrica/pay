<?php

namespace Notch\Framework\Balances\AvailableBalance;

use Illuminate\Database\Eloquent\Model;

class AvailableBalance extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'balanceable_type',
        'balanceable_id',
        'amount',
        'ref_type',
        'ref_id',
        'description',
    ];

    /**
     * Balance constructor.
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('balances.available_table', 'available_balance_history'));
        $this->setConnection(config('balances.connection', config('database.default')));
    }

    /**
     * Get the balance amount transformed to currency.php.
     *
     * @return float|int
     */
    public function getAmountAttribute()
    {
        return $this->attributes['amount'];
    }

    /**
     * Get the parent of the balance record.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function balanceable()
    {
        return $this->morphTo();
    }

    /**
     * Obtain the model for which the balance sheet movement was made
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function referenceable()
    {
        return $this->morphTo();
    }
}
