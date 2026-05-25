<?php

namespace App\Services\Api\Admin;

use App\Jobs\NewUserCreatedJob;
use App\Models\CustomerAddress;
use App\Models\ServiceArea;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class UserAdminService
{
    public function getUsersForAdmin(array $filters): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 10;
        $search = $filters['search'] ?? null;
        $role = $filters['role'] ?? null;

        return User::query()
            ->with('addresses')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($role, function ($query) use ($role) {
                $query->where('role', $role);
            })
            ->latest()
            ->paginate($perPage);
    }
    public function getUserById(User $user): User
    {
        return $user->load('addresses');
    }


    public function updateUserByAdmin(User $user, array $data): User
    {
        $updateData = [];

        foreach (['first_name', 'last_name', 'email', 'phone', 'birthday', 'role'] as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }

        if (!empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $user->update($updateData);

        return $user->load('addresses');
    }
    public function createAdminUser(array $data): User
    {
        return DB::transaction(function () use ($data) {
            return User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'] ?? null,
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'birthday' => $data['birthday'] ?? null,
                'role' => 'admin',
                'password' => Hash::make($data['password']),
            ]);
        });
    }

    public function blockUser(User $user): void
    {
        $user->delete();
    }

    public function getTrashedUsers(array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 10;
        $search = $filters['search'] ?? null;
        $role = $filters['role'] ?? null;

        return User::onlyTrashed()
            ->with('addresses')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($role, function ($query) use ($role) {
                $query->where('role', $role);
            })
            ->latest('deleted_at')
            ->paginate($perPage);
    }

    public function restoreUser(int $userId): User
    {
        $user = User::onlyTrashed()->findOrFail($userId);

        $user->restore();

        return $user->load('addresses');
    }

    public function forceDeleteUser(int $userId): void
    {
        $user = User::onlyTrashed()->findOrFail($userId);

        $user->forceDelete();
    }
 
    private function findServiceArea(float $lat, float $lng): ?ServiceArea
    {
        $serviceAreas = ServiceArea::where('is_active', true)->get();

        foreach ($serviceAreas as $area) {
            if (!$area->polygon) {
                continue;
            }

            $polygon = is_string($area->polygon)
                ? json_decode($area->polygon, true)
                : $area->polygon;

            if (!$polygon || !is_array($polygon)) {
                continue;
            }

            if ($this->pointInPolygon($lat, $lng, $polygon)) {
                return $area;
            }
        }

        return null;
    }

    private function pointInPolygon(float $lat, float $lng, array $polygon): bool
    {
        $inside = false;
        $count = count($polygon);

        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            $latI = $polygon[$i]['lat'];
            $lngI = $polygon[$i]['lng'];
            $latJ = $polygon[$j]['lat'];
            $lngJ = $polygon[$j]['lng'];

            $intersect = (($lngI > $lng) !== ($lngJ > $lng))
                && ($lat < ($latJ - $latI) * ($lng - $lngI) / (($lngJ - $lngI) ?: 0.0000001) + $latI);

            if ($intersect) {
                $inside = !$inside;
            }
        }

        return $inside;
    }
}