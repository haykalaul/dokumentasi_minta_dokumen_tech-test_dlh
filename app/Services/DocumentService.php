<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Document;
use App\Models\Project;
use Illuminate\Http\Testing\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class DocumentService
{
    /**
     * Allowed MIME types mapped to their expected extensions.
     */
    protected array $allowedMimeTypes = [
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
    ];

    /**
     * Upload and store a document securely.
     *
     * @throws InvalidArgumentException
     */
    public function upload(UploadedFile $file, Project $project): Document
    {
        // 1. Verify actual MIME type using PHP file info (or mock MIME in testing)
        $filePath = $file->getRealPath();
        $actualMime = $file->getClientMimeType();

        if (app()->environment() !== 'testing' || ! $file instanceof File) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $actualMime = finfo_file($finfo, $filePath);
            finfo_close($finfo);
        }

        if (! array_key_exists($actualMime, $this->allowedMimeTypes)) {
            throw new InvalidArgumentException("Tipe file tidak diperbolehkan (MIME: {$actualMime}).");
        }

        // 2. Generate a secure, unique UUID filename
        $extension = $file->getClientOriginalExtension();
        $uuidFilename = (string) Str::uuid().'.'.$extension;

        // 3. Store the file in private disk (never public)
        // We will store it under 'private/documents' folder
        $privatePath = $file->storeAs('private/documents', $uuidFilename);

        // 4. Create document record in database
        return Document::create([
            'project_id' => $project->id,
            'filename' => $uuidFilename,
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $privatePath,
            'file_size' => $file->getSize(),
            'mime_type' => $actualMime,
        ]);
    }

    /**
     * Delete a document file and its database record.
     */
    public function delete(Document $document): void
    {
        if (Storage::exists($document->file_path)) {
            Storage::delete($document->file_path);
        }
        $document->delete();
    }
}
