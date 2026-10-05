<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Satu row = satu temuan. Header diulang pada tiap temuan dalam satu transaksi. */
class QualityInspection extends Model
{
    protected $table = 'Th_QualityInspection';
    protected $primaryKey = 'ID';
    public $timestamps = false;
    protected $guarded = ['ID'];
    protected $casts = ['Status' => 'boolean'];

    public function pictures(): HasMany
    {
        return $this->hasMany(QualityInspectionPicture::class, 'ThQualityInspectionID')->where('Status', 1);
    }

    public function pinpoints(): HasMany
    {
        return $this->hasMany(QualityInspectionPinpoint::class, 'ThQualityInspectionID')->where('Status', 1);
    }
}
