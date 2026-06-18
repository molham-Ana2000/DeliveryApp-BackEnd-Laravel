<?php

namespace App\Services\Api\User;

use App\Jobs\NewUserCreatedJob;
use App\Models\CustomerAddress;
use App\Models\ServiceArea;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
class UserService
{
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'birthday' => $data['birthday'],
                'password' => Hash::make($data['password']),
                'role' => 'customer',
            ]);

            // foreach ($data['addresses'] as $index => $addressData) {
            //     $this->saveAddress($user, $addressData, $index);
            // }

            NewUserCreatedJob::dispatch($user);

            // return $user->load('addresses');
            return $user;
        });
    }

    public function updateProfile(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $updateData = [];

            foreach (['first_name', 'last_name', 'phone', 'birthday'] as $field) {
                if (array_key_exists($field, $data)) {
                    $updateData[$field] = $data[$field];
                }
            }

            if (!empty($data['password'])) {
                $updateData['password'] = Hash::make($data['password']);
            }

            if (!empty($updateData)) {
                $user->update($updateData);
            }

            if (!empty($data['addresses'])) {
                foreach ($data['addresses'] as $index => $addressData) {
                    $this->saveAddress($user, $addressData, $index);
                }
            }

            return $user->load('addresses');
        });
    }
 

    private function saveAddress(User $user, array $addressData, int $index): CustomerAddress
    {
        $serviceArea = $this->findServiceArea(
            $addressData['latitude'],
            $addressData['longitude']
        );

        if (!$serviceArea) {
            throw new Exception("Address at index {$index} is outside our service area.");
        }

        $payload = [
            'user_id' => $user->id,
            'service_area_id' => $serviceArea->id,
            'label' => $addressData['label'] ?? null,
            'full_address' => $addressData['full_address'],
            'city' => $addressData['city'],
            'postal_code' => $addressData['postal_code'],
            'country' => $addressData['country'] ?? 'Germany',
            'latitude' => $addressData['latitude'],
            'longitude' => $addressData['longitude'],
            'is_default' => $addressData['is_default'] ?? $index === 0,
        ];

        if (!empty($addressData['id'])) {
            $address = CustomerAddress::where('id', $addressData['id'])
                ->where('user_id', $user->id)
                ->first();

            if (!$address) {
                throw new Exception('Address not found for this user.');
            }

            $address->update($payload);

            return $address;
        }

        return CustomerAddress::create($payload);
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