<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QualityInspectionPicture extends Model
{
    protected $table = 'Td_QualityInspection_Picture';
    protected $primaryKey = 'ID';
    public $timestamps = false;
    protected $guarded = ['ID'];
}
