<?php

namespace App\Services\Api\Admin;

use App\Models\Media;
use App\Models\MenuCategory;
use App\Models\Restaurant;
use App\Models\ServiceArea;
use App\Services\Api\Admin\PointInPolygonService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Illuminate\Support\Facades\Storage;
class RestaurantService
{
    public function __construct(
        private readonly PointInPolygonService $pointInPolygonService
    ) {
    }

    public function list(array $filters = []): LengthAwarePaginator
    {
        return Restaurant::query()
            ->with(['creator:id,first_name,last_name,email', 'serviceArea:id,name,city,postal_code,country'])
            ->when(isset($filters['status']), function ($query) use ($filters) {
                $query->where('status', $filters['status']);
            })
            ->when(isset($filters['service_area_id']), function ($query) use ($filters) {
                $query->where('service_area_id', $filters['service_area_id']);
            })
            ->when(isset($filters['search']), function ($query) use ($filters) {
                $search = $filters['search'];

                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($filters['per_page'] ?? 15);
    }

    public function create(array $data, int $adminId): Restaurant
    {
        return DB::transaction(function () use ($data, $adminId) {
            $serviceArea = $this->getActiveServiceArea((int) $data['service_area_id']);

            $this->validateCoordinatesInsideServiceArea(
                (float) $data['latitude'],
                (float) $data['longitude'],
                $serviceArea
            );

            $restaurant= Restaurant::create([
                'created_by' => $adminId,
                'service_area_id' => $serviceArea->id,

                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,

                'city' => $serviceArea->city,
                'postal_code' => $serviceArea->postal_code,
                'country' => $serviceArea->country,

                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],

                'status' => $data['status'],

                'opening_time' => $data['opening_time'] ?? null,
                'closing_time' => $data['closing_time'] ?? null,
            ]);
                   if (isset($data['photo'])) {
                        $photo = $data['photo'];

                        $originalName = $photo->getClientOriginalName();
                        $mimeType = $photo->getClientMimeType();
                        $size = $photo->getSize();
                        $extension = $photo->getClientOriginalExtension();

                        $folder = public_path('restaurant');

                        if (! file_exists($folder)) {
                            mkdir($folder, 0755, true);
                        }

                        $fileName = time() . '_' . uniqid() . '.' . $extension;

                        $photo->move($folder, $fileName);

                        $path = 'restaurant/' . $fileName;

                        Media::create([
                            'restaurant_id' => $restaurant->id,
                            'menu_item_id' => null,
                            'file_name' => $fileName,
                            'file_path' => $path,
                            'file_url' => asset($path),
                            'mime_type' => $mimeType,
                            'size' => $size,
                            'type' => 'image',
                        ]);
                    }
              return $restaurant->fresh([
                'creator:id,first_name,last_name,email',
                'photo',
            ]);
        });
    }

    public function update(Restaurant $restaurant, array $data): Restaurant
    {
        return DB::transaction(function () use ($restaurant, $data) {
            $serviceArea = $this->getActiveServiceArea((int) $data['service_area_id']);

            $this->validateCoordinatesInsideServiceArea(
                (float) $data['latitude'],
                (float) $data['longitude'],
                $serviceArea
            );
           
            $restaurant->update([
                'service_area_id' => $serviceArea->id,

                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,

                'city' => $serviceArea->city,
                'postal_code' => $serviceArea->postal_code,
                'country' => $serviceArea->country,

                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],

                'status' => $data['status'],

                'opening_time' => $data['opening_time'] ?? null,
                'closing_time' => $data['closing_time'] ?? null,
            ]);
         if (isset($data['photo'])) {
            $photo = $data['photo'];

            $mimeType = $photo->getClientMimeType();
            $size = $photo->getSize();
            $extension = $photo->getClientOriginalExtension();

            $folder = public_path('restaurant');

            if (! file_exists($folder)) {
                mkdir($folder, 0755, true);
            }

            $fileName = time() . '_' . uniqid() . '.' . $extension;

            $photo->move($folder, $fileName);

            $path = 'restaurant/' . $fileName;

            if ($restaurant->photo) {
                if ($restaurant->photo->file_path) {
                    $oldPath = public_path($restaurant->photo->file_path);

                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }

                $restaurant->photo->update([
                    'file_name' => $fileName,
                    'file_path' => $path,
                    'file_url' => asset($path),
                    'mime_type' => $mimeType,
                    'size' => $size,
                    'type' => 'image',
                ]);
            } else {
                Media::create([
                    'restaurant_id' => $restaurant->id,
                    'menu_item_id' => null,
                    'file_name' => $fileName,
                    'file_path' => $path,
                    'file_url' => asset($path),
                    'mime_type' => $mimeType,
                    'size' => $size,
                    'type' => 'image',
                ]);
            }
        }


                return $restaurant->fresh([
                    'creator:id,first_name,last_name,email',
                    'photo',
                ]);
        });
    }

    public function delete(Restaurant $restaurant): void
    {
        $restaurant->delete();
    }
    public function getByRestaurant(int $restaurantId, array $filters = []): LengthAwarePaginator
    {
        return MenuCategory::query()
            ->with([
                'restaurant:id,name,status',
                'menuItems.media',
            ])
            ->where('restaurant_id', $restaurantId)
            ->when(isset($filters['is_active']), function ($query) use ($filters) {
                $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
            })
            ->when(isset($filters['search']), function ($query) use ($filters) {
                $query->where('name', 'like', '%' . $filters['search'] . '%');
            })
            ->orderBy('sort_order')
            ->latest()
            ->paginate((int) ($filters['per_page'] ?? 15));
    }

    public function findById(int $id): Restaurant
    {
        $restaurant = Restaurant::with([
            'creator:id,first_name,last_name,email',
            'serviceArea:id,name,city,postal_code,country',
        ])->find($id);

        if (! $restaurant) {
            throw new ModelNotFoundException('Restaurant not found.');
        }

        return $restaurant;
    }

    private function getActiveServiceArea(int $serviceAreaId): ServiceArea
    {
        $serviceArea = ServiceArea::query()
            ->where('id', $serviceAreaId)
            ->where('is_active', true)
            ->first();

        if (! $serviceArea) {
            throw new InvalidArgumentException('Selected service area is not active or does not exist.');
        }

        return $serviceArea;
    }
    public function trashed(array $filters = []): LengthAwarePaginator
    {
        return Restaurant::query()
            ->onlyTrashed()
            ->with(['creator:id,first_name,last_name,email', 'serviceArea:id,name,city,postal_code,country'])
            ->when(isset($filters['status']), function ($query) use ($filters) {
                $query->where('status', $filters['status']);
            })
            ->when(isset($filters['service_area_id']), function ($query) use ($filters) {
                $query->where('service_area_id', $filters['service_area_id']);
            })
            ->when(isset($filters['search']), function ($query) use ($filters) {
                $search = $filters['search'];

                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->latest('deleted_at')
            ->paginate($filters['per_page'] ?? 15);
    }
    public function restore(int $restaurantId): Restaurant
    {
        $restaurant = Restaurant::onlyTrashed()
            ->findOrFail($restaurantId);

        $restaurant->restore();

        return $restaurant->fresh(['creator', 'serviceArea']);
    }

    public function forceDelete(int $restaurantId): void
    {
        $restaurant = Restaurant::onlyTrashed()
            ->findOrFail($restaurantId);

        $restaurant->forceDelete();
    }
    // private function validateCoordinatesInsideServiceArea(
    // float $latitude,
    // float $longitude,
    // ServiceArea $serviceArea
    // ): void {
    //     $polygonPath = database_path("seeders/data/polygon_{$serviceArea->postal_code}.json");

    //     if (! file_exists($polygonPath)) {
    //         throw new InvalidArgumentException(
    //             "Polygon file for {$serviceArea->postal_code} was not found."
    //         );
    //     }

    //     $polygon = json_decode(file_get_contents($polygonPath), true);

    //     if (! is_array($polygon) || count($polygon) < 3) {
    //         throw new InvalidArgumentException(
    //             "Invalid polygon file for {$serviceArea->postal_code}."
    //         );
    //     }

    //     $isInside = $this->pointInPolygonService->contains(
    //         $latitude,
    //         $longitude,
    //         $polygon
    //     );

    //     if (! $isInside) {
    //         throw new InvalidArgumentException(
    //             "Selected location must be inside {$serviceArea->postal_code} {$serviceArea->city}, {$serviceArea->country}."
    //         );
    //     }
    // }

    private function validateCoordinatesInsideServiceArea(
        float $latitude,
        float $longitude,
        ServiceArea $serviceArea
     ): void {
        if (empty($serviceArea->polygon)) {
            throw new InvalidArgumentException(
                "Service area {$serviceArea->postal_code} {$serviceArea->city} does not have a polygon boundary."
            );
        }

        $isInside = $this->pointInPolygonService->contains(
            $latitude,
            $longitude,
            $serviceArea->polygon
        );

        if (! $isInside) {
            throw new InvalidArgumentException(
                "Selected location must be inside {$serviceArea->postal_code} {$serviceArea->city}, {$serviceArea->country}."
            );
        }
    }
}