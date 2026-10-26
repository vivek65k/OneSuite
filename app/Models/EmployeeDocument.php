<?php

namespace App\Models;

use App\Models\Concerns\ScopedByEmployee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class EmployeeDocument extends Model
{
    use ScopedByEmployee;

    public const TYPES = [
        'offer_letter' => 'Offer Letter',
        'appointment_letter' => 'Appointment Letter',
        'contract' => 'Contract',
        'pan' => 'PAN',
        'aadhar' => 'Aadhar',
        'passport' => 'Passport',
        'visa' => 'Visa',
        'certificate' => 'Certificates',
        'experience_letter' => 'Experience Letter',
        'relieving_letter' => 'Relieving Letter',
        'salary_slip' => 'Salary Slip',
        'other' => 'Other',
    ];

    /**
     * Identity papers, contracts and salary slips are private: they live
     * outside the web folder and are only handed out through the download
     * route, after a login and permission check.
     */
    public const DISK = 'local';

    /** Where uploads went before they were made private; still read so old files keep opening. */
    public const LEGACY_DISK = 'public';

    /** What may be uploaded. Anything else (scripts, web pages, programs) is refused. */
    public const EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'doc', 'docx'];

    public const MAX_KILOBYTES = 10240;

    protected $fillable = ['company_id', 'employee_id', 'name', 'category', 'file_path', 'expiry_date'];

    protected static function booted(): void
    {
        // The file goes when its record goes, instead of being left behind on disk.
        static::deleted(fn (self $document) => $document->deleteFile());
    }

    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
        ];
    }

    /** Validation rules for an uploaded document file. */
    public static function fileRules(bool $required = true): array
    {
        // Both the name and the real content must be an allowed type: a script renamed to .pdf is refused, and so is a PDF named .php.
        return [$required ? 'required' : 'nullable', 'file', 'extensions:'.implode(',', self::EXTENSIONS), 'mimes:'.implode(',', self::EXTENSIONS), 'max:'.self::MAX_KILOBYTES];
    }

    public static function fileMessages(string $field = 'file'): array
    {
        return [
            $field.'.mimes' => 'Upload a PDF, an image (JPG, PNG, WebP) or a Word document.',
            $field.'.extensions' => 'Upload a PDF, an image (JPG, PNG, WebP) or a Word document.',
            $field.'.max' => 'The file can be at most '.(self::MAX_KILOBYTES / 1024).' MB.',
        ];
    }

    /** Keep an upload on the private disk and return the path to save on the record. */
    public static function storeUpload(UploadedFile $file): string
    {
        return $file->store('employee-documents', self::DISK);
    }

    /** The disk this record's file is actually on, or null when the file is missing. */
    public function disk(): ?string
    {
        foreach ([self::DISK, self::LEGACY_DISK] as $disk) {
            if (filled($this->file_path) && Storage::disk($disk)->exists($this->file_path)) {
                return $disk;
            }
        }

        return null;
    }

    public function deleteFile(): void
    {
        foreach ([self::DISK, self::LEGACY_DISK] as $disk) {
            if (filled($this->file_path) && Storage::disk($disk)->exists($this->file_path)) {
                Storage::disk($disk)->delete($this->file_path);
            }
        }
    }

    /** "pdf", "jpg", ... taken from the stored file. */
    public function getExtensionAttribute(): string
    {
        return strtolower(pathinfo((string) $this->file_path, PATHINFO_EXTENSION));
    }

    /** The checked link to open the file; never a direct path into storage. */
    public function getUrlAttribute(): string
    {
        return route('admin.documents.file', $this);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
