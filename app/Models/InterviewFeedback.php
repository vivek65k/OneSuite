<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterviewFeedback extends Model
{
    public const RECOMMENDATIONS = ['hire', 'no_hire', 'maybe'];

    protected $table = 'interview_feedback';

    protected $fillable = ['interview_id', 'interviewer_name', 'rating', 'recommendation', 'comments'];

    public function interview(): BelongsTo
    {
        return $this->belongsTo(Interview::class);
    }
}
