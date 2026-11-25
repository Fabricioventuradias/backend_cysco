<?php

namespace App\Http\Controllers\Integrations;

use App\Http\Controllers\Controller;
use App\Traits\DriverTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\DriverDocument;
use App\Models\HandymanOrder;
use App\Models\DriverDetail;
use App\Models\Checkr\CheckrCandidate;
use Illuminate\Support\Facades\Log;
use App\Traits\ApiResponseTrait;
use App\Models\Checkr\CheckrVerification;


class CheckrController extends Controller
{
    
    use DriverTrait,ApiResponseTrait;
    protected $payload;
    
    public function __construct($data=[])
    {
        
        $this->payload = $data;
        
    }
    public function CheckrCase($data)
    {
        if (!empty($data['calling_for'])) 
        {
            $calling_for = $data['calling_for'];
            $config = $data['config'];
            switch ($calling_for) 
            {
                case 'cretaeCandidate':
                    $response=$this->cretaeCandidate($data,$config);
                    return $response;
                    break;
                case "createDriverLicense":
                    $response=$this->createDriverLicense($data, $config);
                    return $response;
                    break;
                case "uploadDoc":
                    $response=$this->uploadDoc($data, $config);
                    return $response;
                    break;
                case "updateCandidate":
                    $response=$this->updateCandidate($data, $config);
                    return $response;
                    break;
                case "createIdvInvitation":
                    $response=$this->createIdvInvitation($data, $config);
                    return $response;
                    break;
                case "getInvitationStatus":
                    $response=$this->getInvitationStatus($data, $config);
                    return $response;
                    break;
                    
                    
            }
        }  
        
    }

    public function cretaeCandidate($request, $config)
    {
        $secretKey  = $request['config']['api_secret'];
        
        $merchant_id = $request['driver']->merchant_id;
        $driverId   = $request['driver']->id;
    
        $data['first_name'] = $request['driver']->first_name;
        $data['last_name']  = $request['driver']->last_name;
        $data['email']      = $request['driver']->email; // required
    
        try {
            $curl = curl_init();
    
            curl_setopt_array($curl, array(
                CURLOPT_URL            => 'https://api.checkr-staging.com/v1/candidates',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => '',
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 30, // was 0 (no timeout) — risky in prod, request could hang forever
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_POSTFIELDS     => json_encode($data),
                CURLOPT_HTTPHEADER     => array(
                    'Content-Type: application/json',
                    'Authorization: Basic ' . base64_encode($secretKey . ':'), // fixed
                ),
            ));
    
            $response = curl_exec($curl);
            
    
            $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
           
            $decoded = json_decode($response, true);
            // Store the RAW response — no re-encoding
            CheckrCandidate::create([
                'merchant_id' => $merchant_id,
                'driver_id'   => $driverId,
                'response'    => $response,
                'candidate_id'=>$decoded['id'],
            ]);
    
            
    
            // Checkr returned an application-level error (2xx or not)
            if ($httpStatus < 200 || $httpStatus >= 300 || isset($decoded['error'])) {
                 
                $message = $this->extractCheckrError($decoded);
    
                \Log::error('Checkr candidate creation failed', [
                    'merchant_id' => $merchant_id,
                    'driver_id'   => $driverId,
                    'http_status' => $httpStatus,
                    'error'       => $message,
                ]);
    
                return [
                    'success' => false,
                    'message' => $message,
                ];
            }
            
    
            return [
                'success' => true,
                'data'    => $decoded,
            ];
    
        } catch (\Throwable $e) {
            \Log::error('Checkr candidate creation exception', [
                'merchant_id' => $merchant_id ?? null,
                'driver_id'   => $driverId ?? null,
                'message'     => $e->getMessage(),
            ]);
    
            return [
                'success' => false,
                'message' => 'Something went wrong while creating the candidate.',
            ];
        }
    }

    public function updateCandidate($request, $config)
    {
        try {
            $secretKey   = $request['config']['api_secret'];
            $merchant_id = $request['driver']->merchant_id;
            $driverId    = $request['driver']->id;

            $checkrCandidate = CheckrCandidate::where('merchant_id', $merchant_id)
                ->where('driver_id', $driverId)
                ->first();

            if (!$checkrCandidate) {
                \Log::error('Checkr candidate update: no candidate record found', [
                    'merchant_id' => $merchant_id,
                    'driver_id'   => $driverId,
                ]);
                return ['success' => false, 'message' => 'No Checkr candidate exists for this driver yet.'];
            }

            // response is stored as a single raw JSON string — decode once (see note above)
            $candidateData = json_decode($checkrCandidate->response);

            if (!isset($checkrCandidate->candidate_id)) {
                \Log::error('Checkr candidate update: candidate id missing from stored response', [
                    'merchant_id'     => $merchant_id,
                    'driver_id'       => $driverId,
                    'stored_response' => $checkrCandidate->response,
                ]);
                return ['success' => false, 'message' => 'Checkr candidate ID could not be found for this driver.'];
            }

            $checkrCandidateId = $checkrCandidate->candidate_id;

            // The fields the caller actually wants to update — see call-site note below
            $fields = $request['request'] ?? [];

            // Build the payload with ONLY the keys that were actually passed in.
            // array_key_exists (not isset) so an intentional empty string — e.g. "ssn": "" to clear it — still counts.
            $data = [];

            foreach (['first_name', 'last_name', 'middle_name', 'email', 'phone', 'zipcode', 'dob', 'ssn'] as $field) {
                if (array_key_exists($field, $fields)) {
                    $data[$field] = $fields[$field];
                }
            }

            if (array_key_exists('postal_address', $fields) && is_array($fields['postal_address'])) {
                $address = [];
                foreach (['name', 'street', 'street2', 'city', 'state', 'zipcode'] as $addrField) {
                    if (array_key_exists($addrField, $fields['postal_address'])) {
                        $address[$addrField] = $fields['postal_address'][$addrField];
                    }
                }
                if (!empty($address)) {
                    $data['postal_address'] = $address;
                }
            }

            if (empty($data)) {
                return ['success' => false, 'message' => 'No fields provided to update.'];
            }

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL            => 'https://api.checkr-staging.com/v1/candidates/' . $checkrCandidateId,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => '',
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_POSTFIELDS     => json_encode($data),
                CURLOPT_HTTPHEADER     => array(
                    'Content-Type: application/json',
                    'Authorization: Basic ' . base64_encode($secretKey . ':'),
                ),
            ));

            $response = curl_exec($curl);

            if ($response === false) {
                $curlError = curl_error($curl);
                curl_close($curl);

                \Log::error('Checkr candidate update: cURL transport error', [
                    'merchant_id' => $merchant_id,
                    'driver_id'   => $driverId,
                    'error'       => $curlError,
                ]);

                return ['success' => false, 'message' => 'Unable to reach Checkr. Please try again.'];
            }

            $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            $decoded = json_decode($response, true);
            

            

            if ($httpStatus < 200 || $httpStatus >= 300 || isset($decoded['error'])) {
                $message = $this->extractCheckrError($decoded);

                \Log::error('Checkr candidate update failed', [
                    'merchant_id' => $merchant_id,
                    'driver_id'   => $driverId,
                    'http_status' => $httpStatus,
                    'error'       => $message,
                ]);

                return ['success' => false, 'message' => $message];
            }
            // Refresh the stored response with the latest candidate state
            $checkrCandidate->update([
                'ssn_number_response' => $response,
            ]);

            return ['success' => true, 'data' => $decoded];

        } catch (\Throwable $e) {
            \Log::error('Checkr candidate update exception', [
                'merchant_id' => $merchant_id ?? null,
                'driver_id'   => $driverId ?? null,
                'message'     => $e->getMessage(),
                'file'        => $e->getFile(),
                'line'        => $e->getLine(),
            ]);

            return ['success' => false, 'message' => 'Something went wrong while updating the candidate.'];
        }
    }


    public function retrieveCandidate(Request $request)
    {
        $secretKey=$request['config']['api_secret'];
        $order_id="WORK".$request['order']->id;
        $customer_name=$request['order']['User']->first_name." ".$request['order']['User']->last_name;
        $customer_email=$request['order']['User']->email;
        $merchant_id=$request['order']->merchant_id;
        $orders=$request['order']['HandymanOrderDetail'];
        $timezone=$request['order']['CountryArea']['timezone'];
        $tax=$request['tax_per'];
        $businessName = strtoupper(str_replace(' ', '', $request['order']['Merchant']->BusinessName));
        
        $lines = [];
        $expectedTotal = 0;
        date_default_timezone_set($timezone);
        $timestamp = time();


        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.checkr-staging.com/v1/candidates/f093caecf4b2b7bc0533d4f0',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Authorization: Basic MWZjZTliZjIwZGQ0MjIwMTBiYTU1NGQ0OWQyM2E5NWE3MDc2NzBiZjo='
        ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
 
        return $response;
        

    }

    public function createDriverLicense($request, $config)
    {
        $secretKey  = $request['config']['api_secret'];
        $driverId   = $request['driver']->id;
        $merchant_id = $request['driver']->merchant_id;
    
        $data['number']      = $request['request']['document_number'];
        $data['state']       = $request['request']['state'] ?? "CA";
        $data['issued_date'] = $request['request']['issued_date'] ?? "2010-01-30";
        $data['current']     = true;
    
        try {
            $checkrCandidate = CheckrCandidate::where('merchant_id', $merchant_id)
                ->where('driver_id', $driverId)
                ->first();
           
            if (!$checkrCandidate) {
                \Log::error('Checkr driver license: no candidate record found', [
                    'merchant_id' => $merchant_id,
                    'driver_id'   => $driverId,
                ]);
    
                return [
                    'success' => false,
                    'message' => 'No Checkr candidate exists for this driver yet.',
                ];
            }
             
            // Existing double-JSON-decode convention for the response column
            // $candidateData = json_decode($checkrCandidate->response);
            if (!isset($checkrCandidate->candidate_id)) {
                \Log::error('Checkr driver license: candidate id missing from stored response', [
                    'merchant_id' => $merchant_id,
                    'driver_id'   => $driverId,
                    'stored_response' => $checkrCandidate->response,
                ]);
    
                return [
                    'success' => false,
                    'message' => 'Checkr candidate ID could not be found for this driver.',
                ];
            }
            
            $checkrCandidateId = $checkrCandidate->candidate_id;
            
    
            $postData = $data;
            $postData['current'] = $data['current'] ? 'true' : 'false'; // preserve literal "true"/"false" string
            $postFields = http_build_query($postData);
    
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL            => 'https://api.checkr-staging.com/v1/candidates/' . $checkrCandidateId . '/driver_licenses',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => '',
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 30, // was 0 (no timeout)
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_POSTFIELDS     => $postFields,
                CURLOPT_HTTPHEADER     => array(
                    'Content-Type: application/x-www-form-urlencoded',
                    'Authorization: Basic ' . base64_encode($secretKey . ':'), // fixed
                ),
            ));
    
            $response = curl_exec($curl);
            
            if ($response === false) {
                $curlError = curl_error($curl);
                curl_close($curl);
    
                \Log::error('Checkr driver license: cURL transport error', [
                    'merchant_id' => $merchant_id,
                    'driver_id'   => $driverId,
                    'error'       => $curlError,
                ]);
    
                return [
                    'success' => false,
                    'message' => 'Unable to reach Checkr. Please try again.',
                ];
            }
    
            $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
    
            // Store the raw response as-is (this part was already correct — no double-encoding)
            DriverDetail::where('driver_id', $driverId)->update([
                'candidate_driver_license' => $response,
            ]);
            $checkrCandidate->candidate_driver_license=$response;
            $checkrCandidate->save();
            
    
            $decoded = json_decode($response, true);
    
            if ($httpStatus < 200 || $httpStatus >= 300 || isset($decoded['error'])) {
                $message = $this->extractCheckrError($decoded);
    
                \Log::error('Checkr driver license creation failed', [
                    'merchant_id' => $merchant_id,
                    'driver_id'   => $driverId,
                    'http_status' => $httpStatus,
                    'error'       => $message,
                ]);
    
                return [
                    'success' => false,
                    'message' => $message,
                ];
            }
    
            return [
                'success' => true,
                'data'    => $decoded,
            ];
    
        } catch (\Throwable $e) {
            \Log::error('Checkr driver license exception', [
                'merchant_id' => $merchant_id ?? null,
                'driver_id'   => $driverId ?? null,
                'message'     => $e->getMessage(),
            ]);
    
            return [
                'success' => false,
                'message' => 'Something went wrong while adding the driver license.',
            ];
        }
    }
    public function retrieveDriverLicense(Request $request)
    {
        $apiKey=$request['config']['api_key'];
        $secretKey=$request['config']['api_secret'];
        $order_id="WORK".$request['order']->id;
        $customer_name=$request['order']['User']->first_name." ".$request['order']['User']->last_name;
        $customer_email=$request['order']['User']->email;
        $merchant_id=$request['order']->merchant_id;
        $orders=$request['order']['HandymanOrderDetail'];
        $timezone=$request['order']['CountryArea']['timezone'];
        $tax=$request['tax_per'];
        $businessName = strtoupper(str_replace(' ', '', $request['order']['Merchant']->BusinessName));
        
        $lines = [];
        $expectedTotal = 0;
        date_default_timezone_set($timezone);
        $timestamp = time();


        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.checkr-staging.com/v1/candidates/f093caecf4b2b7bc0533d4f0/driver_licenses/b9cc446b3f3944f26ac66826',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Authorization: Basic MWZjZTliZjIwZGQ0MjIwMTBiYTU1NGQ0OWQyM2E5NWE3MDc2NzBiZjo='
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;
        

    }
    public function updateDriverLicense(Request $request)
    {
        $apiKey=$request['config']['api_key'];
        $secretKey=$request['config']['api_secret'];
        $order_id="WORK".$request['order']->id;
        $customer_name=$request['order']['User']->first_name." ".$request['order']['User']->last_name;
        $customer_email=$request['order']['User']->email;
        $merchant_id=$request['order']->merchant_id;
        $orders=$request['order']['HandymanOrderDetail'];
        $timezone=$request['order']['CountryArea']['timezone'];
        $tax=$request['tax_per'];
        $businessName = strtoupper(str_replace(' ', '', $request['order']['Merchant']->BusinessName));
        
        $lines = [];
        $expectedTotal = 0;
        date_default_timezone_set($timezone);
        $timestamp = time();


        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.checkr-staging.com/v1/candidates/f093caecf4b2b7bc0533d4f0/driver_licenses/b9cc446b3f3944f26ac66826',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => 'number=F2222222&current=true',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Basic MWZjZTliZjIwZGQ0MjIwMTBiYTU1NGQ0OWQyM2E5NWE3MDc2NzBiZjo='
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;
        

    }

    public function uploadDoc($request, $config)
    {
        $secretKey  = $request['config']['api_secret'];
        $driverId   = $request['driver']->id;
        $merchant_id = $request['driver']->merchant_id;
    
        try {
            $checkrCandidate = CheckrCandidate::where('merchant_id', $merchant_id)
                ->where('driver_id', $driverId)
                ->first();
    
            if (!$checkrCandidate) {
                \Log::error('Checkr document upload: no candidate record found', [
                    'merchant_id' => $merchant_id,
                    'driver_id'   => $driverId,
                ]);
    
                return [
                    'success' => false,
                    'message' => 'No Checkr candidate exists for this driver yet.',
                ];
            }
    
            $candidateData = json_decode($checkrCandidate->response);
            if (!isset($checkrCandidate->candidate_id)) {
                \Log::error('Checkr document upload: candidate id missing from stored response', [
                    'merchant_id'      => $merchant_id,
                    'driver_id'        => $driverId,
                    'stored_response'  => $checkrCandidate->response,
                ]);
    
                return [
                    'success' => false,
                    'message' => 'Checkr candidate ID could not be found for this driver.',
                ];
            }
    
            $checkrCandidateId = $checkrCandidate->candidate_id;
    
            $documentImage = $request['request']['document_image'];
    
            // works whether $documentImage is an UploadedFile instance or a plain path string
            if (is_object($documentImage) && method_exists($documentImage, 'getMimeType')) {
                $filePath = $documentImage->getRealPath();
                $mimeType = $documentImage->getMimeType();
            } else {
                $filePath = $documentImage;
                $mimeType = mime_content_type($documentImage) ?: 'application/octet-stream';
            }
    
            if (!$filePath || !is_readable($filePath)) {
                \Log::error('Checkr document upload: file not readable', [
                    'merchant_id' => $merchant_id,
                    'driver_id'   => $driverId,
                    'file_path'   => $filePath,
                ]);
    
                return [
                    'success' => false,
                    'message' => 'The uploaded document could not be read.',
                ];
            }
    
            $curl = curl_init();
    
            curl_setopt_array($curl, array(
                CURLOPT_URL            => 'https://api.checkr-staging.com/v1/candidates/' . $checkrCandidateId . '/documents',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => '',
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 30, // was 0 (no timeout)
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_POSTFIELDS     => array(
                    'file' => new \CURLFile($filePath, $mimeType, basename($filePath)),
                    'type' => 'driver_license',
                ),
                CURLOPT_HTTPHEADER     => array(
                    // No Content-Type header — cURL sets multipart/form-data with the correct boundary itself
                    'Authorization: Basic ' . base64_encode($secretKey . ':'), // fixed
                ),
            ));
    
            $response = curl_exec($curl);
    
            if ($response === false) {
                $curlError = curl_error($curl);
                curl_close($curl);
    
                \Log::error('Checkr document upload: cURL transport error', [
                    'merchant_id' => $merchant_id,
                    'driver_id'   => $driverId,
                    'error'       => $curlError,
                ]);
    
                return [
                    'success' => false,
                    'message' => 'Unable to reach Checkr. Please try again.',
                ];
            }
    
            $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
    
            DriverDetail::where('driver_id', $driverId)->update([
                'candidate_driver_license_doc' => $response,
            ]);
            
            $checkrCandidate->candidate_driver_license_doc=$response;
            $checkrCandidate->save();
    
            $decoded = json_decode($response, true);
    
            if ($httpStatus < 200 || $httpStatus >= 300 || isset($decoded['error'])) {
                $message = $this->extractCheckrError($decoded);
    
                \Log::error('Checkr document upload failed', [
                    'merchant_id' => $merchant_id,
                    'driver_id'   => $driverId,
                    'http_status' => $httpStatus,
                    'error'       => $message,
                ]);
    
                return [
                    'success' => false,
                    'message' => $message,
                ];
            }
    
            return [
                'success' => true,
                'data'    => $decoded,
            ];
    
        } catch (\Throwable $e) {
            \Log::error('Checkr document upload exception', [
                'merchant_id' => $merchant_id ?? null,
                'driver_id'   => $driverId ?? null,
                'message'     => $e->getMessage(),
            ]);
    
            return [
                'success' => false,
                'message' => 'Something went wrong while uploading the document.',
            ];
        }
    }
    public function getStates()
    {
        $states = [
            [
                'key' => 'AL',
                'value' => 'Alabama'
            ],
            [
                'key' => 'AK',
                'value' => 'Alaska'
            ],
            [
                'key' => 'AZ',
                'value' => 'Arizona'
            ],
            [
                'key' => 'AR',
                'value' => 'Arkansas'
            ],
            [
                'key' => 'CA',
                'value' => 'California'
            ],
            [
                'key' => 'CO',
                'value' => 'Colorado'
            ],
            [
                'key' => 'CT',
                'value' => 'Connecticut'
            ],
            [
                'key' => 'DE',
                'value' => 'Delaware'
            ],
            [
                'key' => 'DC',
                'value' => 'District of Columbia'
            ],
            [
                'key' => 'FL',
                'value' => 'Florida'
            ],
            [
                'key' => 'GA',
                'value' => 'Georgia'
            ],
            [
                'key' => 'HI',
                'value' => 'Hawaii'
            ],
            [
                'key' => 'ID',
                'value' => 'Idaho'
            ],
            [
                'key' => 'IL',
                'value' => 'Illinois'
            ],
            [
                'key' => 'IN',
                'value' => 'Indiana'
            ],
            [
                'key' => 'IA',
                'value' => 'Iowa'
            ],
            [
                'key' => 'KS',
                'value' => 'Kansas'
            ],
            [
                'key' => 'KY',
                'value' => 'Kentucky'
            ],
            [
                'key' => 'LA',
                'value' => 'Louisiana'
            ],
            [
                'key' => 'ME',
                'value' => 'Maine'
            ],
            [
                'key' => 'MD',
                'value' => 'Maryland'
            ],
            [
                'key' => 'MA',
                'value' => 'Massachusetts'
            ],
            [
                'key' => 'MI',
                'value' => 'Michigan'
            ],
            [
                'key' => 'MN',
                'value' => 'Minnesota'
            ],
            [
                'key' => 'MS',
                'value' => 'Mississippi'
            ],
            [
                'key' => 'MO',
                'value' => 'Missouri'
            ],
            [
                'key' => 'MT',
                'value' => 'Montana'
            ],
            [
                'key' => 'NE',
                'value' => 'Nebraska'
            ],
            [
                'key' => 'NV',
                'value' => 'Nevada'
            ],
            [
                'key' => 'NH',
                'value' => 'New Hampshire'
            ],
            [
                'key' => 'NJ',
                'value' => 'New Jersey'
            ],
            [
                'key' => 'NM',
                'value' => 'New Mexico'
            ],
            [
                'key' => 'NY',
                'value' => 'New York'
            ],
            [
                'key' => 'NC',
                'value' => 'North Carolina'
            ],
            [
                'key' => 'ND',
                'value' => 'North Dakota'
            ],
            [
                'key' => 'OH',
                'value' => 'Ohio'
            ],
            [
                'key' => 'OK',
                'value' => 'Oklahoma'
            ],
            [
                'key' => 'OR',
                'value' => 'Oregon'
            ],
            [
                'key' => 'PA',
                'value' => 'Pennsylvania'
            ],
            [
                'key' => 'RI',
                'value' => 'Rhode Island'
            ],
            [
                'key' => 'SC',
                'value' => 'South Carolina'
            ],
            [
                'key' => 'SD',
                'value' => 'South Dakota'
            ],
            [
                'key' => 'TN',
                'value' => 'Tennessee'
            ],
            [
                'key' => 'TX',
                'value' => 'Texas'
            ],
            [
                'key' => 'UT',
                'value' => 'Utah'
            ],
            [
                'key' => 'VT',
                'value' => 'Vermont'
            ],
            [
                'key' => 'VA',
                'value' => 'Virginia'
            ],
            [
                'key' => 'WA',
                'value' => 'Washington'
            ],
            [
                'key' => 'WV',
                'value' => 'West Virginia'
            ],
            [
                'key' => 'WI',
                'value' => 'Wisconsin'
            ],
            [
                'key' => 'WY',
                'value' => 'Wyoming'
            ],
        ];
    
        return $this->successResponse("success", $states);
    }

    /**
     * Checkr's "error" field is inconsistent — normalize it to a single string.
     */
    private function extractCheckrError($decoded)
    {
        if (!isset($decoded['error'])) {
            return 'Unknown error from Checkr.';
        }
    
        $error = $decoded['error'];
    
        // e.g. {"error":["Email '...' is not a valid email address"]}
        if (is_array($error)) {
            return implode(' ', $error);
        }
    
        // e.g. {"error":"Bad authentication error"}
        return $error;
    }
     public function createIdvInvitation($request, $config)
    {
        try {
            $secretKey   = $request['config']['api_secret'];
            $merchant_id = $request['driver']->merchant_id;
            $driverId    = $request['driver']->id;

            $checkrCandidate = CheckrCandidate::where('merchant_id', $merchant_id)
                ->where('driver_id', $driverId)
                ->first();

            if (!$checkrCandidate) {
                
                \Log::error('Checkr IDV invitation: no candidate record found', [
                    'merchant_id' => $merchant_id,
                    'driver_id'   => $driverId,
                ]);
                return ['success' => false, 'message' => 'No Checkr candidate exists for this driver yet.'];
            }

            $candidateData = json_decode($checkrCandidate->candidate_driver_license);

            if (!isset($checkrCandidate->candidate_id)) {
                return ['success' => false, 'message' => 'Checkr candidate ID could not be found for this driver.'];
            }

            $checkrCandidateId = $checkrCandidate->candidate_id;
            $state = $candidateData->driver_license_state?? "TX";

            // The IDV-enabled package slug from YOUR Checkr account's Packages page —
            // pass it in via $config, never hardcode a slug copied from docs.
            $idvPackage = $config['idv_package_slug'] ?? "stagingdriverbackgroungcheck";

            if (empty($idvPackage)) {
                \Log::error('Checkr IDV invitation: no idv_package_slug configured', [
                    'merchant_id' => $merchant_id,
                ]);
                return ['success' => false, 'message' => 'No IDV package configured for this merchant.'];
            }

            $data = [
                'candidate_id'    => $checkrCandidateId,
                'package'         => $idvPackage,
                'node'            =>"zpy8orej4r614ize",
                'work_locations'  => [
                    [
                        'country' => 'US',
                        'state'   => $state ?? null, // confirm real source below
                    ],
                ],
            ];

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL            => 'https://api.checkr-staging.com/v1/invitations',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => '',
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_POSTFIELDS     => json_encode($data),
                CURLOPT_HTTPHEADER     => array(
                    'Content-Type: application/json',
                    'Authorization: Basic ' . base64_encode($secretKey . ':'),
                ),
            ));

            $response = curl_exec($curl);
            $decoded = json_decode($response, true);
            
            if ($response === false) {
                $curlError = curl_error($curl);
                curl_close($curl);

                \Log::error('Checkr IDV invitation: cURL transport error', [
                    'merchant_id' => $merchant_id,
                    'driver_id'   => $driverId,
                    'error'       => $curlError,
                ]);
                return ['success' => false, 'message' => 'Unable to reach Checkr. Please try again.'];
            }

            $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            $decoded = json_decode($response, true);

            if ($httpStatus < 200 || $httpStatus >= 300 || isset($decoded['error'])) {
                $message = $this->extractCheckrError($decoded);

                \Log::error('Checkr IDV invitation failed', [
                    'merchant_id' => $merchant_id,
                    'driver_id'   => $driverId,
                    'http_status' => $httpStatus,
                    'error'       => $message,
                ]);
                return ['success' => false, 'message' => $message];
            }
            
            
            $invitation_id = $decoded['id'];
            $status = $decoded['status'];
            $invitation_url = $decoded['invitation_url'];
            $package = $decoded['package'];
            $candidate_id = $decoded['candidate_id'];
            
            $verification = CheckrVerification::create([
                'merchant_id'    => $merchant_id,
                'driver_id'      => $driverId,
                'candidate_id'   => $candidate_id,
                'package'        =>$package,
                'invitation_id'  => $invitation_id,
                'status'         => $status,
                'url'            => $invitation_url,
                'response'       => $response,
            ]);

            return ['success' => true, 'data' => $decoded];

        } catch (\Throwable $e) {
            \Log::error('Checkr IDV invitation exception', [
                'merchant_id' => $merchant_id ?? null,
                'driver_id'   => $driverId ?? null,
                'message'     => $e->getMessage(),
                'file'        => $e->getFile(),
                'line'        => $e->getLine(),
            ]);
            return ['success' => false, 'message' => 'Something went wrong while starting identity verification.'];
        }
    }

    /**
     * Re-verification: per Checkr's own guidance, reorder the same IDV invitation flow
     * for the EXISTING candidate rather than deciding full-ID-vs-selfie-only yourself —
     * Checkr's server determines that based on the candidate's verification history.
     */
    public function reverifyIdentity($request, $config)
    {
        return $this->createIdvInvitation($request, $config);
    }
    public function getInvitationStatus($request, $config)
    {
        $invitationId = $request['request']['invitation_id'] ?? null;

        try {
            $secretKey = $request['config']['api_secret'];

            if (!$invitationId) {
                return ['success' => false, 'message' => 'invitation_id is required.'];
            }

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL            => 'https://api.checkr-staging.com/v1/invitations/' . $invitationId,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => '',
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST  => 'GET',
                CURLOPT_HTTPHEADER     => array(
                    'Content-Type: application/json',
                    'Authorization: Basic ' . base64_encode($secretKey . ':'),
                ),
            ));

            $response = curl_exec($curl);

            if ($response === false) {
                $curlError = curl_error($curl);
                curl_close($curl);

                \Log::error('Checkr invitation status: cURL transport error', [
                    'invitation_id' => $invitationId,
                    'error'         => $curlError,
                ]);

                return ['success' => false, 'message' => 'Unable to reach Checkr. Please try again.'];
            }

            $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            $decoded = json_decode($response, true);

            if ($httpStatus < 200 || $httpStatus >= 300 || isset($decoded['error'])) {
                $message = $this->extractCheckrError($decoded);

                \Log::error('Checkr invitation status fetch failed', [
                    'invitation_id' => $invitationId,
                    'http_status'   => $httpStatus,
                    'error'         => $message,
                ]);

                return ['success' => false, 'message' => $message];
            }

            $invitationRecord = CheckrVerification::where('invitation_id', $invitationId)->first();

            if ($invitationRecord) {
                
                if($invitationRecord->status!="completed")
                {
                    $invitationRecord->status  = $decoded['status'] ?? $invitationRecord->status;
                }
                $invitationRecord->report_id = $decoded['report_id'] ?? $invitationRecord->report_id;

                // completed_at from Checkr is when the driver actually finished the live capture —
                // that's what "verification_time" records here.
                if (!empty($decoded['completed_at'])) {
                    $invitationRecord->verification_time = Carbon::parse($decoded['completed_at']);
                }

                $invitationRecord->status_response = $response;
                $invitationRecord->save();
            } else {
                \Log::warning('Checkr invitation status: no local invitation record found to update', [
                    'invitation_id' => $invitationId,
                ]);
            }

            return ['success' => true, 'data' => $decoded];

        } catch (\Throwable $e) {
            \Log::error('Checkr invitation status exception', [
                'invitation_id' => $invitationId ?? null,
                'message'       => $e->getMessage(),
                'file'          => $e->getFile(),
                'line'          => $e->getLine(),
            ]);

            return ['success' => false, 'message' => 'Something went wrong while checking verification status.'];
        }
    }
    

   
    


}
