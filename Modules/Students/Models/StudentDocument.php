<?php

namespace Modules\Students\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class StudentDocument extends Model
{
    use BelongsToTenant;

    protected $table = 'student_documents';

    protected $fillable = [
        'school_id',
        'student_id',
        'document_type',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
        'notes',
    ];

    public static array $documentTypes = [
        'birth_certificate' => 'Birth Certificate',
        'grade7_certificate' => 'Grade 7 Certificate',
        'olevel_certificate' => 'O-Level Certificate',
        'result_slip' => 'Result Slip',
        'previous_report' => 'Previous Report Card',
        'transfer_letter' => 'Transfer Letter',
        'passport_photo' => 'Passport Photo',
        'other' => 'Other',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function getDocumentTypeLabelAttribute(): string
    {
        return self::$documentTypes[$this->document_type] ?? ucwords(str_replace('_', ' ', $this->document_type));
    }

    public function getDownloadUrlAttribute(): string
    {
        return route('student.document.download', ['student' => $this->student_id, 'document' => $this->id]);
    }

    public function getExistsAttribute(): bool
    {
        return $this->file_path && Storage::disk('public')->exists($this->file_path);
    }
}
