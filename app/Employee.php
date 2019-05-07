<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = ['first_name', 'last_name', 'phone','email','companie_id'];

    public function companie()
    {
       return $this->belongsTo('App\Companie', 'companie_id');
    }
}