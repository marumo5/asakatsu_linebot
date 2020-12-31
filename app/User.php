<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
	protected $fillable = [
		'user_identifier', 'name', 'oversleeping_times', 'oversleep_check', 'affiliation_id',
	];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
}
