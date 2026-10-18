<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TailoringComplaintEvidence extends Model
{
    protected $table = 'tailoring_complaint_evidence';

    protected $fillable = ['complaint_id', 'uploaded_by_user_id', 'disk', 'path', 'original_name', 'mime_type', 'size'];

    public function complaint() { return $this->belongsTo(CounterOrderComplaint::class, 'complaint_id'); }
}
