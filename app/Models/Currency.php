<?php

namespace App\Models;

use App\Services\Central\LandlordService;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    // some traits from BaseModel


    protected $fillable = [
        'code',
        'name',
        'symbol'
    ];


    public function currentRate()
    {
        $landlordService = app(LandlordService::class);
        return $landlordService->getCurrencyRateByCode($this->code);
    }

    public function getRateAttribute()
    {
        return $this->currentRate();
    }

}