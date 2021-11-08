<?php

namespace App\Covid19VaccineOnline;

use Illuminate\Database\Eloquent\Model;

class Survey extends Model
{
    protected $connection = "covid19vaccineonline";

    protected $hidden = ["created_at", "updated_at"];
}
