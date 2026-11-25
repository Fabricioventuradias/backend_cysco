<?php
namespace App\Services\FleetManagement;

use DB;
use App\Models\Fleet;
use App\Models\VehicleMake;
use App\Models\VehicleType;
use App\Models\VehicleModel;
use App\Traits\ImageTrait;
use App\Repositories\FleetManagement\FleetVehicleRepository;
use App\Models\FleetDriver;
use App\Models\FleetDriverRent;
use Carbon\Carbon;

class FleetVehicleService
{
    use ImageTrait;

    protected $fleetVehicleRepository;

    public function __construct(FleetVehicleRepository $fleetVehicleRepository)
    {
        $this->fleetVehicleRepository = $fleetVehicleRepository;
    }

    // ==================== VEHICLE MODEL ====================
    public function saveVehicleModel(array $data, int $merchantId)
    {
        DB::beginTransaction();
        try {
            if (!empty($data['vehicle_model_id'])) {
                // ========== UPDATE ==========
                $updateData = [];
    
                if (isset($data['vehicle_make_id']) && $data['vehicle_make_id']) {
                    $updateData['vehicle_make_id'] = $data['vehicle_make_id'];
                }
                if (isset($data['vehicle_type_id']) && $data['vehicle_type_id']) {
                    $updateData['vehicle_type_id'] = $data['vehicle_type_id'];
                }
                if (isset($data['vehicle_seat']) && $data['vehicle_seat']) {
                    $updateData['vehicle_seat'] = $data['vehicle_seat'];
                }
    
                if (!empty($updateData)) {
                    $this->fleetVehicleRepository->updateVehicleModel(
                        $data['vehicle_model_id'],
                        $updateData
                    );
                }
    
                $languageData = ['locale' => $data['locale'] ?? \App::getLocale()];
                if (!empty($data['vehicle_model_name'])) {
                    $languageData['name'] = $data['vehicle_model_name'];
                }
                if (!empty($data['description'])) {
                    $languageData['description'] = $data['description'];
                }
    
                $this->fleetVehicleRepository->updateVehicleModelLanguage(
                    $data['vehicle_model_id'],
                    $merchantId,
                    $languageData
                );
    
                $model = VehicleModel::with(['LanguageVehicleModelSingle', 'LanguageVehicleModelAny'])
                                     ->find($data['vehicle_model_id']);
    
            } else {
                // ========== CREATE ==========
                $model = $this->fleetVehicleRepository->createVehicleModel([
                    'merchant_id'       => $merchantId,
                    'vehicle_make_id'   => $data['vehicle_make_id'],
                    'vehicle_type_id'   => $data['vehicle_type_id'],
                    'vehicle_seat'      => $data['vehicle_seat'],
                    'vehicleModelStatus' => 1,
                ]);
    
                $this->fleetVehicleRepository->createVehicleModelLanguage(
                    $model->id,
                    $merchantId,
                    [
                        'locale'      => $data['locale'] ?? \App::getLocale(),
                        'name'        => $data['vehicle_model_name'],
                        'description' => $data['description'],
                    ]
                );
                
                $this->fleetVehicleRepository->mapVehicleModelToFleet(
                     $data['fleet_id'],
                     $model->id
                );
                
                $model->load(['LanguageVehicleModelSingle', 'LanguageVehicleModelAny']);
            }
    
            DB::commit();
            $model->append(['vehicle_model_name', 'vehicle_model_description']);
            
            
            return $model;
    
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    
    
    // Get country area documents with upload status
    public function getFleetCountryAreaDocuments(int $fleetId, int $driverVehicleId = null)
    {
        $documents = $this->fleetVehicleRepository->getFleetCountryAreaDocuments(
            $fleetId,
            $driverVehicleId
        );
    
        if ($documents->isEmpty()) {
            return [
                'success' => true,
                'data'    => [
                    'fleet_id'         => $fleetId,
                    'driver_vehicle_id'=> $driverVehicleId,
                    'summary'          => [
                        'total_required' => 0,
                        'total_uploaded' => 0,
                        'total_approved' => 0,
                        'total_pending'  => 0,
                        'total_rejected' => 0,
                        'all_approved'   => false,
                    ],
                    'documents'        => [],
                ]
            ];
        }
    
        $totalRequired = $documents->count();
        $totalUploaded = $documents->where('is_uploaded', true)->count();
        $totalApproved = $documents->where('status', 2)->count();
        $totalPending  = $documents->where('status', 1)->count();
        $totalRejected = $documents->where('status', 3)->count();
    
        return [
            'success' => true,
            'data'    => [
                'fleet_id'          => $fleetId,
                'driver_vehicle_id' => $driverVehicleId,
                'summary'           => [
                    'total_required' => $totalRequired,
                    'total_uploaded' => $totalUploaded,
                    'total_approved' => $totalApproved,
                    'total_pending'  => $totalPending,
                    'total_rejected' => $totalRejected,
                    'all_approved'   => $totalRequired > 0 && $totalApproved === $totalRequired,
                ],
                'documents'         => $documents->values(),
            ]
        ];
    }
    
    public function uploadVehicleDocument(array $data, int $merchantId)
    {
        DB::beginTransaction();
        try {
            $imagePath = null;
            if (!empty($data['document'])) {
                $imagePath = $this->uploadImage('document', 'vehicle_document', $merchantId);
            }
    
            $driverVehicle = \App\Models\DriverVehicle::find($data['driver_vehicle_id']);
            if (!$driverVehicle) {
                return ['success' => false, 'message' => 'Vehicle not found'];
            }
    
    
            // Check existing document
            $existingDoc = \App\Models\DriverVehicleDocument::where([
                ['driver_vehicle_id', '=', $data['driver_vehicle_id']],
                ['document_id',       '=', $data['document_id']],
            ])->first();
    
            // Create or update document
            $doc = \App\Models\DriverVehicleDocument::updateOrCreate(
                [
                    'driver_vehicle_id' => $data['driver_vehicle_id'],
                    'document_id'       => $data['document_id'],
                ],
                [
                    'document'                     => $imagePath,
                    'expire_date'                  => $data['expire_date']     ?? null,
                    'document_number'              => $data['document_number'] ?? null,
                    'document_verification_status' => 1, //pending
                    'status'                       => 1,
                ]
            );
            
            // Check if all required docs uploaded
            $this->checkAndUpdateFleetReadiness($driverVehicle);
    
            //update status only if driver is assigned
            if (!is_null($driverVehicle->driver_id)) {
                $driver           = \App\Models\Driver::find($driverVehicle->driver_id);
                $autoVerifyStatus = get_driver_auto_verify_status($driver->id);
    
                $this->updateDriverStatusDocument(
                    $driver->id,
                    $driverVehicle->id,
                    'vehicle'
                );
            }
    
            DB::commit();
            return ['success' => true, 'data' => $doc];
    
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // =====================================================
    // Update Driver Signup Step After Document Upload
    // =====================================================
    private function updateDriverSignupStepAfterSegment($driver)
    {
        if ($driver->signupStep == 6) {
            $autoVerify = get_driver_auto_verify_status($driver->id);
    
            $signupStatus = $autoVerify == 1 ? 9 : 8;
    
            if (!empty($driver->Merchant->Configuration->driver_training) &&
                $driver->Merchant->Configuration->driver_training == 1
            ) {
                $signupStatus        = 8;
                $driver->in_training = 1;
            }
    
            $driver->signupStep = $signupStatus;
            $driver->save();
    
            if ($autoVerify == 1) {
                $ref = new \App\Http\Controllers\ReferralController();
                $ref->checkReferral([
                    'driver_id'         => $driver->id,
                    'check_referral_at' => 'COMPLETE-SIGNUP',
                ]);
            }
        }
    }
    
    // =====================================================
    // Update Driver Status After Document Upload
    // =====================================================
    private function updateDriverStatusDocument(int $driverId, int $vehicleId, string $docType)
    {
        $driver  = \App\Models\Driver::find($driverId);
        // $status  = get_driver_auto_verify_status($driverId, 'final_status', 'vehicle');
        
        if ($docType == 'vehicle') {
            $alreadyAddedDriverVehicle = get_driver_verified_vehicle($driverId, $vehicleId);
            if ($alreadyAddedDriverVehicle == 0) {
                $checkDriverUploadedDocument = check_driver_document(
                    $driverId,
                    'vehicle',
                    $vehicleId,
                    null,
                    1,
                    'reject'
                );
    
                $rejectCase = $driver->reject_driver == 2 && $checkDriverUploadedDocument == true
                    ? true
                    : false;
                    
                if (($driver->signupStep == 5) && $checkDriverUploadedDocument == true) {
                    $driver->signupStep    = 6; // uploaded all vehicle mandatory documents
                    $driver->online_offline = 2; // put driver offline
    
                    if ($rejectCase == true) {
                        $driver->reject_driver = 1; // remove from reject mode
                    }
                }
    
                $driver->save();
            }
    
            // Update vehicle verification status
            $driverVehicle = \App\Models\DriverVehicle::findOrFail($vehicleId);
            $driverVehicle->vehicle_verification_status = 1;  //currently in pending $status
            $driverVehicle->save();
    
        }
    }
    
    public function saveFleetSegmentConfig(array $data, int $fleetId, int $merchantId)
    {
        DB::beginTransaction();
        try {
            $segmentId  = $data['segment_id'];
            $arrService = json_decode($data['arr_service'], true);
    
            if (empty($arrService)) {
                return ['success' => false, 'message' => 'Invalid service data'];
            }
    
            // Get driver_vehicle
            $driverVehicle = \App\Models\DriverVehicle::find($data['driver_vehicle_id']);
            if (!$driverVehicle) {
                return ['success' => false, 'message' => 'Vehicle not found'];
            }
    
            // Verify driver_vehicle belongs to this fleet
            if ($driverVehicle->fleet_id != $fleetId) {
                return ['success' => false, 'message' => 'Vehicle does not belong to this fleet'];
            }
    
            // Attach service types to vehicle
            $driverVehicle->ServiceTypes()
                ->wherePivot('segment_id', $segmentId)
                ->detach();
    
            foreach ($arrService as $service) {
                $driverVehicle->ServiceTypes()->attach(
                    $service['service_type_id'],
                    [
                        'segment_id'        => $segmentId,
                        'driver_vehicle_id' => $driverVehicle->id,
                    ]
                );
            }
            
            // Mark segment as completed
            $driverVehicle->segment_completed = 1;
            $driverVehicle->save();
    
            // Check overall readiness
            $this->checkAndUpdateFleetReadiness($driverVehicle);
    
            // If driver is assigned → update driver segment too
            if (!is_null($driverVehicle->driver_id) || isset($data['driver_id'])) {
                $driver_id = $data['driver_id'];
                $driver = \App\Models\Driver::find($driver_id);
    
                if ($driver) {
                    // Attach segment to driver
                    $driver->Segment()
                        ->wherePivot('segment_id', $segmentId)
                        ->detach();
                    $driver->Segment()->attach($segmentId);
    
                    // Attach service types to driver
                    $driver->ServiceType()
                        ->wherePivot('segment_id', $segmentId)
                        ->detach();
    
                    foreach ($arrService as $service) {
                        $driver->ServiceType()->attach(
                            $service['service_type_id'],
                            [
                                'segment_id'        => $segmentId,
                                'driver_vehicle_id' => $driverVehicle->id,
                            ]
                        );
                    }
                    
                    $driver->signupStep = 6;
                    $driver->save();
                    // Update driver signup step
                    // $this->updateDriverSignupStepAfterSegment($driver);
                }
            }
    
            DB::commit();
    
            return [
                'success' => true,
                'data'    => [
                    'driver_vehicle_id' => $driverVehicle->id,
                    'driver_id'         => $driverVehicle->driver_id,
                    'segment_id'        => $segmentId,
                    'services'          => $arrService,
                ]
            ];
    
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    public function checkAndUpdateFleetReadiness(\App\Models\DriverVehicle $driverVehicle)
    {
        $fleet = Fleet::find($driverVehicle->fleet_id);
    
        if (!$fleet || !$fleet->country_area_id) {
            return 2;
        }
    
        // Use correct pivot table (same as getFleetVehicleDocuments)
        $country_area_id = $fleet->country_area_id;
        $area_document   = \App\Models\CountryArea::where('id', $country_area_id)->first();
    
        $totalMandatoryDocs = 0;
        if ($area_document) {
            $totalMandatoryDocs = $area_document->VehicleDocuments()
                ->wherePivot('vehicle_type_id', '=', $driverVehicle->vehicle_type_id)
                ->where('documentNeed', 1) // mandatory only
                ->count();
        }
    
        // Only count ADMIN APPROVED docs (verification_status = 2)
        $approvedDocs = \App\Models\DriverVehicleDocument::where('driver_vehicle_id', $driverVehicle->id)
            ->where('status', 1)
            ->where('document_verification_status', 2) // ← approved by admin only
            ->whereNotNull('document_id')
            ->count();
    
        $docsCompleted = ($totalMandatoryDocs >= 0 && $approvedDocs >= $totalMandatoryDocs) ? 1 : 2;
    
        $driverVehicle->docs_completed = $docsCompleted;
        $driverVehicle->is_fleet_ready = ($docsCompleted == 1 && $driverVehicle->segment_completed == 1) ? 1 : 2;
        $driverVehicle->save();
    
        return $driverVehicle->is_fleet_ready;
    }
}