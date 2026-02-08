<?php

namespace App\Models;

use App\Services\RedisService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MyClient extends Model
{
    use SoftDeletes;

    protected $table = 'my_client';

    protected $fillable = [
        'name',
        'slug',
        'is_project',
        'self_capture',
        'client_prefix',
        'client_logo',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Boot method untuk menangani event model.
     * Sinkronisasi data ke Redis setiap kali ada perubahan.
     */
    protected static function boot()
    {
        parent::boot();

        // Simpan ke Redis saat data baru dibuat
        static::created(function ($client) {
            $redisService = app(RedisService::class);
            $redisService->saveClient($client);
        });

        // Update Redis saat data diupdate
        static::updated(function ($client) {
            $redisService = app(RedisService::class);
            
            // Cek apakah slug berubah
            if ($client->isDirty('slug')) {
                // Hapus key lama dengan slug sebelumnya
                $oldSlug = $client->getOriginal('slug');
                $redisService->deleteClient($oldSlug);
            }
            
            // Simpan dengan slug baru/current
            $redisService->saveClient($client);
        });

        // Hapus dari Redis saat soft delete
        static::deleted(function ($client) {
            $redisService = app(RedisService::class);
            $redisService->deleteClient($client->slug);
        });
    }

    /**
     * Accessor untuk mendapatkan URL lengkap logo dari S3.
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (empty($this->client_logo)) {
            return null;
        }
        
        return config('filesystems.disks.s3.url') . '/' . $this->client_logo;
    }

    /**
     * Scope untuk filter client yang aktif (non-deleted).
     */
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }
}
