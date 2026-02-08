<?php

namespace App\Http\Controllers;

use App\Models\MyClient;
use App\Services\RedisService;
use App\Services\S3Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MyClientController extends Controller
{
    protected RedisService $redisService;
    protected S3Service $s3Service;

    public function __construct(RedisService $redisService, S3Service $s3Service)
    {
        $this->redisService = $redisService;
        $this->s3Service = $s3Service;
    }

    /**
     * Menampilkan semua data client dengan pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $clients = MyClient::orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $clients,
        ]);
    }

    /**
     * Menampilkan detail client berdasarkan ID.
     * Prioritas: ambil dari Redis dulu, kalau tidak ada baru dari database.
     */
    public function show(string $id): JsonResponse
    {
        // Cari di database dulu untuk dapat slug
        $client = MyClient::find($id);

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Client tidak ditemukan',
            ], 404);
        }

        // Coba ambil dari Redis
        $cachedData = $this->redisService->getClient($client->slug);

        if ($cachedData !== null) {
            return response()->json([
                'success' => true,
                'source' => 'cache',
                'data' => $cachedData,
            ]);
        }

        // Kalau tidak ada di Redis, ambil dari database dan simpan ke Redis
        $this->redisService->saveClient($client);

        return response()->json([
            'success' => true,
            'source' => 'database',
            'data' => $client,
        ]);
    }

    /**
     * Membuat client baru.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:250',
            'slug' => 'required|string|max:100|unique:my_client,slug',
            'is_project' => 'nullable|string|max:32',
            'self_capture' => 'nullable|string|max:1',
            'client_prefix' => 'required|string|max:4',
            'client_logo' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
        ]);

        // Handle upload logo ke S3
        $logoPath = null;
        if ($request->hasFile('client_logo')) {
            $logoPath = $this->s3Service->upload($request->file('client_logo'));
            
            if ($logoPath === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal upload logo ke storage',
                ], 500);
            }
        }

        // Buat record baru
        $client = MyClient::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['slug']),
            'is_project' => $validated['is_project'] ?? '0',
            'self_capture' => $validated['self_capture'] ?? '1',
            'client_prefix' => $validated['client_prefix'],
            'client_logo' => $logoPath,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Client berhasil dibuat',
            'data' => $client,
        ], 201);
    }

    /**
     * Update data client.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $client = MyClient::find($id);

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Client tidak ditemukan',
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:250',
            'slug' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('my_client', 'slug')->ignore($client->id),
            ],
            'is_project' => 'nullable|string|max:32',
            'self_capture' => 'nullable|string|max:1',
            'client_prefix' => 'sometimes|required|string|max:4',
            'client_logo' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
        ]);

        // Handle upload logo baru ke S3
        if ($request->hasFile('client_logo')) {
            $oldLogo = $client->client_logo;
            $newLogoPath = $this->s3Service->replace(
                $request->file('client_logo'),
                $oldLogo
            );

            if ($newLogoPath === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal upload logo baru',
                ], 500);
            }

            $validated['client_logo'] = $newLogoPath;
        }

        // Simpan slug lama untuk update Redis
        $oldSlug = $client->slug;

        // Update data
        if (isset($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['slug']);
        }

        $client->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Client berhasil diupdate',
            'data' => $client->fresh(),
        ]);
    }

    /**
     * Soft delete client.
     */
    public function destroy(string $id): JsonResponse
    {
        $client = MyClient::find($id);

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Client tidak ditemukan',
            ], 404);
        }

        // Soft delete akan trigger observer yang menghapus dari Redis
        $client->delete();

        return response()->json([
            'success' => true,
            'message' => 'Client berhasil dihapus',
        ]);
    }

    /**
     * Mendapatkan client berdasarkan slug (via Redis).
     */
    public function showBySlug(string $slug): JsonResponse
    {
        // Coba dari Redis dulu
        $cachedData = $this->redisService->getClient($slug);

        if ($cachedData !== null) {
            return response()->json([
                'success' => true,
                'source' => 'cache',
                'data' => $cachedData,
            ]);
        }

        // Fallback ke database
        $client = MyClient::where('slug', $slug)->first();

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Client tidak ditemukan',
            ], 404);
        }

        // Simpan ke Redis untuk request berikutnya
        $this->redisService->saveClient($client);

        return response()->json([
            'success' => true,
            'source' => 'database',
            'data' => $client,
        ]);
    }
}
