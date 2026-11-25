<?php

namespace App\Models\POS;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\HasApiTokens;


class PosStaffUser extends Authenticatable
{
    use HasApiTokens; // Laravel\Passport\HasApiTokens — gives createToken() + token()

    protected $table = 'users';

    protected $hidden = ['password'];
}