<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = [
        'student_id',
        'name',
        'email',
        'gpa',
        'major',
    ];

    protected function casts(): array
    {
        return [
            'gpa' => 'float',
        ];
    }
}
