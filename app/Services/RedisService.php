<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

class RedisService
{
    /**
     * Prefix untuk semua key client di Redis.
     */
    private const KEY_PREFIX = 'client:';

    /**
     * Generate Redis key dari slug client.
     *
     * @param string $slug
     * @return string
     */
    public function generateKey(string $slug): string
    {
        return self::KEY_PREFIX . $slug;
    }

    /**
     * Simpan data client ke Redis dalam format JSON.
     * Menggunakan persistent connection untuk data yang tidak expire.
     *
     * @param mixed $client Model atau array data client
     * @return bool
     */
    public function saveClient($client): bool
    {
        try {
            $key = $this->generateKey($client->slug);
            
            // Siapkan data untuk disimpan
            $data = [
                'id' => $client->id,
                'name' => $client->name,
                'slug' => $client->slug,
                'is_project' => $client->is_project,
                'self_capture' => $client->self_capture,
                'client_prefix' => $client->client_prefix,
                'client_logo' => $client->client_logo,
                'created_at' => $client->created_at?->toISOString(),
                'updated_at' => $client->updated_at?->toISOString(),
            ];

            // Simpan ke Redis tanpa expiration (persistent)
            Redis::set($key, json_encode($data));
            
            return true;
        } catch (\Exception $e) {
            report($e);
            return false;
        }
    }

    /**
     * Ambil data client dari Redis berdasarkan slug.
     *
     * @param string $slug
     * @return array|null
     */
    public function getClient(string $slug): ?array
    {
        try {
            $key = $this->generateKey($slug);
            $data = Redis::get($key);

            if ($data === null) {
                return null;
            }

            return json_decode($data, true);
        } catch (\Exception $e) {
            report($e);
            return null;
        }
    }

    /**
     * Hapus data client dari Redis.
     *
     * @param string $slug
     * @return bool
     */
    public function deleteClient(string $slug): bool
    {
        try {
            $key = $this->generateKey($slug);
            Redis::del($key);
            
            return true;
        } catch (\Exception $e) {
            report($e);
            return false;
        }
    }

    /**
     * Cek apakah client ada di Redis.
     *
     * @param string $slug
     * @return bool
     */
    public function exists(string $slug): bool
    {
        $key = $this->generateKey($slug);
        return (bool) Redis::exists($key);
    }

    /**
     * Sync semua data client dari database ke Redis.
     * Berguna untuk initial setup atau recovery.
     *
     * @param iterable $clients Collection of clients
     * @return int Jumlah client yang berhasil di-sync
     */
    public function syncAll(iterable $clients): int
    {
        $count = 0;
        
        foreach ($clients as $client) {
            if ($this->saveClient($client)) {
                $count++;
            }
        }
        
        return $count;
    }
}
