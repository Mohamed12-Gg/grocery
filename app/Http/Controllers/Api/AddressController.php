<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddressRequest;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use App\Traits\V1\ApiResponse;


class AddressController extends Controller
{
    use ApiResponse;
    /**
     * Get all user addresses
     */
    public function index(Request $request): JsonResponse
    {
        if ($request->allFiles() !== []) {
            return self::errorResponse('This endpoint does not accept file uploads.',
                ['files' => ['Remove file attachments from the request.']], 422
            );
        }

        $user = $request->user();

        $addresses = $user->addresses()
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($address) {
                return $this->formatAddress($address);
            });

        return self::successResponse(
            'Addresses retrieved successfully',
            [
                'addresses' => $addresses,
                'total_count' => $addresses->count(),
            ],
            200
        );
    }

    /**
     * Get single address
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $address = $user->addresses()->findOrFail($id);

        return self::successResponse(
            'Address retrieved successfully',
            $this->formatAddress($address)
        );
    }

    /**
     * Create new address
     */
    public function store(AddressRequest $request): JsonResponse
    {
        $user = $request->user();

        $address = DB::transaction(function () use ($request, $user) {
            $isFirstAddress = $user->addresses()->count() === 0;
            $data = array_merge(
                $request->validated(),
                ['is_default' => $request->boolean('is_default') || $isFirstAddress]
            );

            $phone = trim($data['phone'] ?? '');
            $code = trim($data['country_code'] ?? '');
            if ($code !== '' && str_starts_with($phone, $code)) {
                $data['phone'] = substr($phone, strlen($code));
            }

            return $user->addresses()->create($data);
        });

        return self::successResponse(
            'Address created successfully',
            $this->formatAddress($address),
            201
        );
    }

    /**
     * Update address
     */
    public function update(AddressRequest $request, string $id): JsonResponse
    {
        $user = $request->user();
        $address = $user->addresses()->findOrFail($id);

        $address = DB::transaction(function () use ($request, $address) {
            $updateData = $request->validated();
            if (isset($updateData['phone'], $updateData['country_code']) && $updateData['country_code'] !== '' && str_starts_with(trim($updateData['phone']), $updateData['country_code'])) {
                $updateData['phone'] = substr(trim($updateData['phone']), strlen($updateData['country_code']));
            }

            $address->fill($updateData)->save();

            return $address->fresh();
        });

        return self::successResponse(
            'Address updated successfully',
            $this->formatAddress($address)
        );
    }

    /**
     * Delete address
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $address = $user->addresses()->findOrFail($id);

        DB::transaction(function () use ($user, $address) {
            $wasDefault = $address->is_default;
            $address->delete();

            if ($wasDefault) {
                $newDefault = $user->addresses()->first();
                if ($newDefault) {
                    $newDefault->update(['is_default' => true]);
                }
            }
        });

        return self::successResponse('Address deleted successfully');
    }

    /**
     * Set address as default
     */
    public function setDefault(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $address = $user->addresses()->findOrFail($id);

        if ($address->is_default) {
            return self::successResponse(
                'This address is already your default.',
                [
                    'already_default' => true,
                    'address' => $this->formatAddress($address),
                ]
            );
        }

        $address = DB::transaction(function () use ($user, $address) {
            $user->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
            $address->update(['is_default' => true]);

            return $address->fresh();
        });

        return self::successResponse(
            'Default address updated successfully',
            $this->formatAddress($address)
        );
    }

    /**
     * Format address data for response
     */
    private function formatAddress(Address $address): array
    {
        return [
            'id' => $address->id,
            'label' => $address->label,
            'full_name' => $address->full_name,
            'phone' => $address->phone,
            'country_code' => $address->country_code,
            'formatted_phone' => $address->formatted_phone,
            'street_address' => $address->street_address,
            'building_number' => $address->building_number,
            'floor' => $address->floor,
            'apartment' => $address->apartment,
            'landmark' => $address->landmark,
            'city' => $address->city,
            'state' => $address->state,
            'postal_code' => $address->postal_code,
            'country' => $address->country,
            'notes' => $address->notes,
            'is_default' => $address->is_default,
            'latitude' => $address->latitude,
            'longitude' => $address->longitude,
            'full_address' => $address->full_address,
            'created_at' => $address->created_at,
            'updated_at' => $address->updated_at,
        ];
    }
}
