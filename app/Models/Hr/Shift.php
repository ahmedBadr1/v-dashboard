<?php

namespace App\Models\Hr;

use App\Models\MainModelSoft;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends MainModelSoft
{
    use HasFactory;

     protected $fillable = ['name','distance','offline','latitude','longitude','address','type',    'late_cost' ,'overtime_cost','note','active','timezone'];

    public function days() {
        return $this->hasMany(\App\Models\Hr\ShiftDay::class);
    }
}
