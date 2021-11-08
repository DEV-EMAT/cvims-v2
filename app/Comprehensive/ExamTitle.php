<?php

namespace App\Comprehensive;

use Illuminate\Database\Eloquent\Model;

class ExamTitle extends Model
{
    protected $connection = "comprehensive";

    protected $hidden = ["created_at", "updated_at"];
}
