<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Companie extends Model
{
    protected $fillable = ['name', 'email', 'logo','website_url'];

    public function employee()
    {
        return $this->hasMany('App\Employee');
    }
}
