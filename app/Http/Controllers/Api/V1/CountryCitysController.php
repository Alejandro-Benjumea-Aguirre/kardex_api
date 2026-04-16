<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\{JsonResponse, Request};
use App\Http\Controllers\Controller;
use App\Http\Resources\CountryResource;
use App\Http\Resources\CityResource;
use App\Repositories\Interfaces\CountryRepositoryExtendedInterface;
use App\Repositories\Interfaces\CityRepositoryExtendedInterface;

class CountryCitysController extends Controller
{
    public function __construct(
        private readonly CountryRepositoryExtendedInterface $countryRepository,
        private readonly CityRepositoryExtendedInterface $cityRepository,
    ) {}

    // GET /country?search=&is_active=
    public function indexCountry(Request $request): JsonResponse
    {
        $country = $this->countryRepository->paginate(
            filters: [
                'search'     => $request->input('search'),
                'is_active'  => $request->input('is_active'),
            ],
            perPage: (int) $request->input('per_page', 20),
        );

        return response()->json([
            'success' => true,
            'data'    => CountryResource::collection($country),
            'meta'    => [
                'current_page' => $country->currentPage(),
                'per_page'     => $country->perPage(),
                'total'        => $country->total(),
                'last_page'    => $country->lastPage(),
            ],
        ]);
    }

    // GET /country/{country}
    public function showCountry(string $countryId): JsonResponse
    {
        $country = $this->countryRepository->findById($countryId);

        if (! $country) {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'COUNTRY_NOT_FOUND', 'message' => 'Pais no encontrado.'],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => new CountryResource($country),
        ]);
    }

    // GET /city?search=&is_active=
    public function indexCity(Request $request): JsonResponse
    {
        $cities = $this->cityRepository->paginate(
            filters: [
                'search'     => $request->input('search'),
                'is_active'  => $request->input('is_active'),
            ],
            perPage: (int) $request->input('per_page', 20),
        );

        return response()->json([
            'success' => true,
            'data'    => CityResource::collection($cities),
            'meta'    => [
                'current_page' => $cities->currentPage(),
                'per_page'     => $cities->perPage(),
                'total'        => $cities->total(),
                'last_page'    => $cities->lastPage(),
            ],
        ]);
    }

    // GET /city/{city}
    public function showCity(string $cityId): JsonResponse
    {
        $city = $this->cityRepository->findById($cityId);

        if (! $city) {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'CITY_NOT_FOUND', 'message' => 'Ciudad no encontrada.'],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => new CityResource($city),
        ]);
    }

}
