<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QualityInspectionPinpoint extends Model
{
    protected $table = 'Td_QualityInspection_Pinpoint';
    protected $primaryKey = 'ID';
    public $timestamps = false;
    protected $guarded = ['ID'];
}
