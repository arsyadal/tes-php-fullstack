<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class S3Service
{
    /**
     * Folder default untuk menyimpan logo client.
     */
    private const LOGO_PATH = 'client-logos';

    /**
     * Upload file ke S3 bucket.
     *
     * @param UploadedFile $file File yang akan diupload
     * @param string|null $customPath Custom path (opsional)
     * @return string|null Path file di S3
     */
    public function upload(UploadedFile $file, ?string $customPath = null): ?string
    {
        try {
            // Generate nama file unik untuk menghindari konflik
            $filename = $this->generateFilename($file);
            $path = ($customPath ?? self::LOGO_PATH) . '/' . $filename;

            // Upload ke S3
            Storage::disk('s3')->put($path, file_get_contents($file));

            return $path;
        } catch (\Exception $e) {
            report($e);
            return null;
        }
    }

    /**
     * Hapus file dari S3 bucket.
     *
     * @param string $path Path file di S3
     * @return bool
     */
    public function delete(string $path): bool
    {
        try {
            if (empty($path)) {
                return true;
            }

            Storage::disk('s3')->delete($path);
            return true;
        } catch (\Exception $e) {
            report($e);
            return false;
        }
    }

    /**
     * Mendapatkan URL publik file dari S3.
     *
     * @param string|null $path Path file di S3
     * @return string|null
     */
    public function getUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        try {
            return Storage::disk('s3')->url($path);
        } catch (\Exception $e) {
            report($e);
            return null;
        }
    }

    /**
     * Cek apakah file ada di S3.
     *
     * @param string $path Path file di S3
     * @return bool
     */
    public function exists(string $path): bool
    {
        try {
            return Storage::disk('s3')->exists($path);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Generate nama file unik untuk upload.
     *
     * @param UploadedFile $file
     * @return string
     */
    private function generateFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->format('Ymd_His');
        $random = Str::random(8);

        return "{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Upload dengan mengganti file lama.
     *
     * @param UploadedFile $file File baru
     * @param string|null $oldPath Path file lama yang akan dihapus
     * @param string|null $customPath Custom path untuk file baru
     * @return string|null Path file baru di S3
     */
    public function replace(UploadedFile $file, ?string $oldPath = null, ?string $customPath = null): ?string
    {
        // Upload file baru dulu
        $newPath = $this->upload($file, $customPath);

        // Kalau berhasil, hapus file lama
        if ($newPath !== null && $oldPath !== null) {
            $this->delete($oldPath);
        }

        return $newPath;
    }
}
