<?php

namespace Modules\Admissions\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ApplicationDocument extends Model
{
    use BelongsToTenant;

    protected $table = 'application_documents';

    protected $fillable = [
        'school_id',
        'application_id',
        'document_type',
        'title',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
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

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function getDocumentTypeLabelAttribute(): string
    {
        if (! empty($this->title)) {
            return $this->title;
        }

        return self::$documentTypes[$this->document_type] ?? ucwords(str_replace('_', ' ', $this->document_type));
    }

    public function getDownloadUrlAttribute(): string
    {
        return route('application.document.download', ['application' => $this->application_id, 'document' => $this->id]);
    }

    public function getViewUrlAttribute(): string
    {
        return route('application.document.view', ['application' => $this->application_id, 'document' => $this->id]);
    }

    public function getExistsAttribute(): bool
    {
        return $this->file_path && Storage::disk('public')->exists($this->file_path);
    }
}
