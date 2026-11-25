<?php
namespace App\Repositories\FleetManagement;

use App\Models\VehicleType;
use App\Models\VehicleMake;
use App\Models\VehicleModel;
use App\Models\FleetVehicleType;
use App\Models\FleetVehicleMake;
use App\Models\FleetVehicleModel;
use App\Models\LanguageVehicleType;
use App\Models\LanguageVehicleMake;
use App\Models\LanguageVehicleModel;
use App\Models\FleetVehicle;
use App\Models\DriverVehicleAssignment;
use App\Models\Driver;
use App\Models\Fleet;

class FleetVehicleRepository
{
    // ==================== VEHICLE TYPE ====================

    public function createVehicleType(array $data)
    {
        return VehicleType::create($data);
    }

    public function updateVehicleType(int $vehicleTypeId, array $data)
    {
        VehicleType::where('id', $vehicleTypeId)->update($data);
        return VehicleType::find($vehicleTypeId);
    }

    public function createVehicleTypeLanguage(int $vehicleTypeId, int $merchantId, array $data)
    {
        return LanguageVehicleType::create([
            'merchant_id'            => $merchantId,
            'vehicle_type_id'        => $vehicleTypeId,
            'locale'                 => $data['locale'],
            'vehicleTypeName'        => $data['vehicle_type_name'],
            'vehicleTypeDescription' => $data['description'],
        ]);
    }

    public function updateVehicleTypeLanguage(int $vehicleTypeId, int $merchantId, array $data)
    {
        $updateData = [];
    
        if (isset($data['vehicle_type_name'])) {
            $updateData['vehicleTypeName'] = $data['vehicle_type_name'];
        }
    
        if (isset($data['description'])) {
            $updateData['vehicleTypeDescription'] = $data['description'];
        }
    
        if (!empty($updateData)) {
            return LanguageVehicleType::updateOrCreate(
                [
                    'vehicle_type_id' => $vehicleTypeId,
                    'merchant_id'     => $merchantId,
                    'locale'          => $data['locale'],
                ],
                $updateData
            );
        }
    
        return null;
    }

    public function mapVehicleTypeToFleet(int $fleetId, int $vehicleTypeId)
    {
        return FleetVehicleType::firstOrCreate([
            'fleet_id'        => $fleetId,
            'vehicle_type_id' => $vehicleTypeId,
        ]);
    }

    public function getVehicleTypesByFleet(int $fleetId, int $merchantId)
    {
        return VehicleType::with(['LanguageVehicleTypeSingle', 'LanguageVehicleTypeAny'])
            ->whereHas('fleets', function ($query) use ($fleetId) {
                $query->where('fleet_id', $fleetId);
            })
            ->where('merchant_id', $merchantId)
            ->where('vehicleTypeStatus', 1)
            ->get()
            ->map(function ($type) {
                $type->append(['vehicle_type_name', 'vehicle_type_description']);
                return $type;
            });
    }

    // ==================== VEHICLE MAKE ====================

   public function createVehicleMake(array $data)
    {
        return VehicleMake::create($data);
    }
    
    public function updateVehicleMake(int $vehicleMakeId, array $data)
    {
        VehicleMake::where('id', $vehicleMakeId)->update($data);
        return VehicleMake::find($vehicleMakeId);
    }
    
    public function createVehicleMakeLanguage(int $vehicleMakeId, int $merchantId, array $data)
    {
        return LanguageVehicleMake::create([
            'merchant_id'            => $merchantId,
            'vehicle_make_id'        => $vehicleMakeId,
            'locale'                 => $data['locale'],
            'vehicleMakeName'        => $data['name'],
            'vehicleMakeDescription' => $data['description'],
        ]);
    }
    
    public function updateVehicleMakeLanguage(int $vehicleMakeId, int $merchantId, array $data)
    {
        $updateData = [];
    
        if (isset($data['name'])) {
            $updateData['vehicleMakeName'] = $data['name'];
        }
        if (isset($data['description'])) {
            $updateData['vehicleMakeDescription'] = $data['description'];
        }
    
        if (!empty($updateData)) {
            return LanguageVehicleMake::updateOrCreate(
                [
                    'vehicle_make_id' => $vehicleMakeId,
                    'merchant_id'     => $merchantId,
                    'locale'          => $data['locale'],
                ],
                $updateData
            );
        }
    
        return null;
    }

    public function mapVehicleMakeToFleet(int $fleetId, int $vehicleMakeId)
    {
        return FleetVehicleMake::firstOrCreate([
            'fleet_id'        => $fleetId,
            'vehicle_make_id' => $vehicleMakeId,
        ]);
    }

    public function getVehicleMakesByFleet(int $fleetId, int $merchantId)
    {
        return VehicleMake::with(['LanguageVehicleMakeAny', 'LanguageVehicleMakeSingle'])
                ->whereHas('fleets', function ($query) use ($fleetId) {
                    $query->where('fleet_id', $fleetId);
                })
                ->where('merchant_id', $merchantId)
                ->where('vehicleMakeStatus', 1)
                ->get()
                ->map(function ($make) {
                    $make->append(['vehicle_make_name', 'vehicle_make_description']);
                    return $make;
                });
    }

    // ==================== VEHICLE MODEL ====================

    public function createVehicleModel(array $data)
    {
        return VehicleModel::create($data);
    }

    public function updateVehicleModel(int $vehicleModelId, array $data)
    {
        VehicleModel::where('id', $vehicleModelId)->update($data);
        return VehicleModel::find($vehicleModelId);
    }
    
    public function createVehicleModelLanguage(int $vehicleModelId, int $merchantId, array $data)
    {
        return LanguageVehicleModel::create([
            'merchant_id'             => $merchantId,
            'vehicle_model_id'        => $vehicleModelId,
            'locale'                  => $data['locale'],
            'vehicleModelName'        => $data['name'],
            'vehicleModelDescription' => $data['description'],
        ]);
    }
    
    public function updateVehicleModelLanguage(int $vehicleModelId, int $merchantId, array $data)
    {
        $updateData = [];
    
        if (isset($data['name'])) {
            $updateData['vehicleModelName'] = $data['name'];
        }
        if (isset($data['description'])) {
            $updateData['vehicleModelDescription'] = $data['description'];
        }
    
        if (!empty($updateData)) {
            return LanguageVehicleModel::updateOrCreate(
                [
                    'vehicle_model_id' => $vehicleModelId,
                    'merchant_id'      => $merchantId,
                    'locale'           => $data['locale'],
                ],
                $updateData
            );
        }
    
        return null;
    }

    public function mapVehicleModelToFleet(int $fleetId, int $vehicleModelId)
    {
        return FleetVehicleModel::firstOrCreate([
            'fleet_id'         => $fleetId,
            'vehicle_model_id' => $vehicleModelId,
        ]);
    }
    
    public function createFleetVehicle(array $data)
    {
        // Avoid duplicate
        return FleetVehicle::firstOrCreate(
            [
                'fleet_id'         => $data['fleet_id'],
                'vehicle_type_id'  => $data['vehicle_type_id'],
                'vehicle_make_id'  => $data['vehicle_make_id'],
                'vehicle_model_id' => $data['vehicle_model_id'],
            ],
            [
                'vehicle_status' => $data['vehicle_status'] ?? 1,
            ]
        );
    }

    public function getVehicleModelsByFleet(int $fleetId, int $merchantId, int $makeId = null, int $typeId = null)
{
    // Pre-fetch pivot IDs for this fleet
    $fleetVehicleTypes  = FleetVehicleType::where('fleet_id', $fleetId)
                            ->pluck('id', 'vehicle_type_id');
    $fleetVehicleModels = FleetVehicleModel::where('fleet_id', $fleetId)
                            ->pluck('id', 'vehicle_model_id');
    $fleetVehicleMakes  = FleetVehicleMake::where('fleet_id', $fleetId)
                            ->pluck('id', 'vehicle_make_id');

    // Pre-fetch fleet_vehicles for this fleet
    $fleetVehicles = FleetVehicle::where('fleet_id', $fleetId)
                        ->where('vehicle_status', 1)
                        ->get()
                        ->keyBy('vehicle_model_id');

    $fleetVehicleIds = $fleetVehicles->pluck('id')->toArray();

    // Get driver_vehicles linked to fleet_vehicles
    $driverVehicles = \App\Models\DriverVehicle::whereIn('fleet_vehicle_id', $fleetVehicleIds)
                    ->whereHas('Drivers', function ($query) {
                        $query->where('is_detached', 2); // 2 = NOT detached 
                    })
                    ->get()
                    ->keyBy('fleet_vehicle_id');

    $driverVehicleIds = $driverVehicles->pluck('id')->toArray();

    // Get all uploaded documents for these driver_vehicles
    $vehicleDocuments = \App\Models\DriverVehicleDocument::whereIn('driver_vehicle_id', $driverVehicleIds)
                            ->get()
                            ->groupBy('driver_vehicle_id');

    //  Get fleet's country_area_id → required documents
    $fleet = Fleet::find($fleetId);
    $requiredDocuments = collect();
    if ($fleet && $fleet->country_area_id) {
        $requiredDocuments = \App\Models\CountryAreaDocument::where('country_area_id', $fleet->country_area_id)
            ->where('document_type', 1) // 1 = vehicle document
            ->with('Document')
            ->get();
    }
    $totalRequiredDocs = $requiredDocuments->count();

    return VehicleModel::with([
                'VehicleMake.LanguageVehicleMakeSingle',
                'VehicleMake.LanguageVehicleMakeAny',
                'VehicleType.LanguageVehicleTypeSingle',
                'VehicleType.LanguageVehicleTypeAny',
                'LanguageVehicleModelSingle',
                'LanguageVehicleModelAny',
            ])
            ->whereHas('fleets', function ($query) use ($fleetId) {
                $query->where('fleet_id', $fleetId);
            })
            ->where('merchant_id', $merchantId)
            ->when($makeId, fn($q) => $q->where('vehicle_make_id', $makeId))
            ->when($typeId, fn($q) => $q->where('vehicle_type_id', $typeId))
            ->where('vehicleModelStatus', 1)
            ->get()
            ->map(function ($model) use (
                $fleetVehicleTypes,
                $fleetVehicleModels,
                $fleetVehicleMakes,
                $fleetVehicles,
                $driverVehicles,
                $vehicleDocuments,
                $requiredDocuments,
                $totalRequiredDocs
            ) {
                // Append names
                $model->append(['vehicle_model_name', 'vehicle_model_description']);
                $model->fleet_vehicle_model_id = $fleetVehicleModels[$model->id] ?? null;

                if ($model->VehicleMake) {
                    $model->VehicleMake->append(['vehicle_make_name', 'vehicle_make_description']);
                    $model->VehicleMake->fleet_vehicle_make_id = $fleetVehicleMakes[$model->vehicle_make_id] ?? null;
                }

                if ($model->VehicleType) {
                    $model->VehicleType->append(['vehicle_type_name', 'vehicle_type_description']);
                    $model->VehicleType->fleet_vehicle_type_id = $fleetVehicleTypes[$model->vehicle_type_id] ?? null;
                }

                // Get fleet_vehicle & driver_vehicle
                $fleetVehicle  = $fleetVehicles[$model->id]        ?? null;
                $driverVehicle = $fleetVehicle
                                    ? ($driverVehicles[$fleetVehicle->id] ?? null)
                                    : null;

                // Check is_detached from pivot
                $isDetached = false;
                if ($driverVehicle) {
                    $pivotDriver = $driverVehicle->Drivers->first();
                    // is_detached: 1=detached(hide), 2=not detached(show)
                    $isDetached = $pivotDriver
                                    ? $pivotDriver->pivot->is_detached == 1
                                    : true;
                }
                
                $model->fleet_vehicle_id  = $fleetVehicle->id ?? null;
                
                // Only show driver_vehicle_id if NOT detached
                $model->driver_vehicle_id = ($driverVehicle && !$isDetached)
                                                ? $driverVehicle->id
                                                : null;

                //  Get uploaded docs for this driver_vehicle (keyed by document_id)
                $uploadedDocs = $driverVehicle
                    ? ($vehicleDocuments[$driverVehicle->id] ?? collect())->keyBy('document_id')
                    : collect();

                //  Map required documents with upload status
                $documentList = $requiredDocuments->map(function ($reqDoc) use ($uploadedDocs) {
                    $uploaded = $uploadedDocs[$reqDoc->document_id] ?? null;

                    return [
                        'document_id'     => $reqDoc->document_id,
                        'document_name'   => $reqDoc->Document->documentName ?? null,
                        'is_required'     => $reqDoc->Document->documentNeed ?? 1,
                        'is_uploaded'     => $uploaded ? true : false,
                        'document_number' => $uploaded->document_number              ?? null,
                        'expire_date'     => $uploaded->expire_date                  ?? null,
                        'document_file'   => $uploaded->document                     ?? null,
                        'status'          => $uploaded->document_verification_status ?? null,
                        'status_label'    => $this->documentStatusLabel(
                                                $uploaded->document_verification_status ?? null
                                            ),
                    ];
                });

                //  Counts based on required documents
                $totalUploaded = $documentList->where('is_uploaded', true)->count();
                $totalApproved = $documentList->where('status', 2)->count();
                $totalPending  = $documentList->where('status', 1)->count();
                $totalRejected = $documentList->where('status', 3)->count();

                $model->document_status = [
                    'total_required' => $totalRequiredDocs,
                    'total_uploaded' => $totalUploaded,
                    'total_approved' => $totalApproved,
                    'total_pending'  => $totalPending,
                    'total_rejected' => $totalRejected,
                    'is_approved'    => $totalRequiredDocs > 0 && $totalApproved === $totalRequiredDocs,
                    'documents'      => $documentList->values(),
                ];

                return $model;
            });
}
    
    // Get required documents for country area
    // public function getCountryAreaDocuments(int $countryAreaId, int $driverVehicleId = null)
    // {
    //     $areaDocuments = \App\Models\CountryAreaDocument::where('country_area_id', $countryAreaId)
    //         ->where('document_type', 1) // 1 = vehicle document
    //         ->with(['Document'])
    //         ->get();
    
    //     // Get uploaded docs if driver_vehicle_id provided
    //     $uploadedDocs = collect();
    //     if ($driverVehicleId) {
    //         $uploadedDocs = \App\Models\DriverVehicleDocument::where('driver_vehicle_id', $driverVehicleId)
    //             ->get()
    //             ->keyBy('document_id');
    //     }
    
    //     return $areaDocuments->map(function ($areaDoc) use ($uploadedDocs) {
    //         $doc      = $areaDoc->Document;
    //         $uploaded = $uploadedDocs[$areaDoc->document_id] ?? null;
    
    //         return [
    //             'document_id'     => $areaDoc->document_id,
    //             'document_name'   => $doc->documentName    ?? null,
    //             'is_required'     => $doc->documentNeed    ?? 1,
    //             'is_uploaded'     => $uploaded ? true : false,
    //             'document_number' => $uploaded->document_number              ?? null,
    //             'expire_date'     => $uploaded->expire_date                  ?? null,
    //             'document_file'   => $uploaded->document                     ?? null,
    //             'status'          => $uploaded->document_verification_status ?? null,
    //             'status_label'    => $this->documentStatusLabel(
    //                                     $uploaded->document_verification_status ?? null
    //                                  ),
    //             'reject_reason'   => $uploaded->reject_reason_id             ?? null,
    //         ];
    //     });
    // }
    
    public function getFleetCountryAreaDocuments(int $fleetId, int $driverVehicleId = null)
    {
        $fleet = Fleet::find($fleetId);

        if (!$fleet || !$fleet->country_area_id) {
            return collect();
        }

        $country_area_id = $fleet->country_area_id;

        $query = \App\Models\CountryArea::where('id', $country_area_id);
        $query->with(['VehicleDocuments' => function ($q) {
            $q->addSelect(
                'documents.id',
                'expire_date as expire_status',
                'documentNeed as document_mandatory',
                'document_number_required'
            );
            $q->where('documentStatus', 1);
        }]);
        $area_document = $query->first();

        if (empty($area_document)) {
            return collect();
        }

        // Get vehicle_type_id from driver_vehicle
        $driver_vehicle = \App\Models\DriverVehicle::find($driverVehicleId);
        $vehicle_type   = $driver_vehicle?->vehicle_type_id;

        // ✅ wherePivot used directly — same as getFleetVehicleDocuments
        $vehicle_documents = $area_document->VehicleDocuments()
            ->wherePivot('vehicle_type_id', '=', $vehicle_type)
            ->get();

        // Get uploaded docs
        $uploadedDocs = collect();
        if ($driverVehicleId) {
            $uploadedDocs = \App\Models\DriverVehicleDocument::where('driver_vehicle_id', $driverVehicleId)
                ->whereNotNull('document_id')
                ->where('status', 1)
                ->get()
                ->keyBy('document_id');
        }

        return $vehicle_documents->map(function ($doc) use ($uploadedDocs) {
            $uploaded = $uploadedDocs[$doc->id] ?? null;

            return [
                'document_id'              => $doc->id,
                'document_name'            => $doc->DocumentName,
                'is_required'              => $doc->documentNeed,
                'expire_status'            => $doc->expire_date,
                'document_number_required' => $doc->document_number_required,
                'is_uploaded'              => $uploaded ? true : false,
                'document_number'          => $uploaded->document_number              ?? null,
                'expire_date'              => $uploaded->expire_date                  ?? null,
                'document_file'            => $uploaded->document                     ?? null,
                'status'                   => $uploaded->document_verification_status ?? null,
                'status_label'             => $this->documentStatusLabel($uploaded->document_verification_status ?? null),
                'reject_reason'            => $uploaded->reject_reason_id             ?? null,
            ];
        });
    }
    
    private function documentStatusLabel($status)
    {
        return match((int) $status) {
            1       => 'Pending',
            2       => 'Approved',
            3       => 'Rejected',
            4       => 'Removed',
            default => 'Not Uploaded',
        };
    }
}