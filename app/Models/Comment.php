<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'episode_id',
        'user_id',
        'user_name',
        'user_email',
        'user_avatar',
        'rating',
        'content',
        'likes_count',
    ];

    protected $casts = [
        'rating' => 'integer',
        'likes_count' => 'integer',
    ];

    public function episode(): BelongsTo
    {
        return $this->belongsTo(Episode::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
