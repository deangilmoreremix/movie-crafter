<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovieAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'answer_raw',
        'scenario',
        'story_boards',
        'title',
        'short_description',
        'metadata',
        'error',
        'is_successful',
        'movie_id',
    ];

    protected $casts = [
        "story_boards" => "array",
        "metadata" => "json"
    ];
}
