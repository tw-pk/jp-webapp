<?php

namespace App\Services;

use Twilio\Rest\Client;
use App\Models\User;

class TwilioServices
{
    protected $twilio;

    public function __construct()
    {
        $sid = config('app.TWILIO_CLIENT_ID');
        $token = config('app.TWILIO_AUTH_TOKEN');
        $this->twilio = new Client($sid, $token);
    }

    public function makeTwilioCall($callTypeTab)
    {
        try {
            $calls = $this->twilio->calls->read($callTypeTab, 100);
            // Handle the Twilio API response as needed
            return $calls;
        } catch (\Exception $e) {
            // Handle exceptions and errors
            return null;
        }
    }


    public function createTwilioTenDLC($user)
    {
    
        $status_callback_webhook = 'https://94ca-72-255-3-126.ngrok-free.app/webhook/ten/dlc';
        
        try {
    
            // $customerProfile = $this->twilio->trusthub->v1->customerProfiles->create(
            //     $user['firstname'] . ' ' . $user['lastname'],
            //     $user['email'],
            //     "RN806dd6cd175f314e1f96a9727ee271f4"
            // );
            
            $customerProfile = $this->twilio->trusthub->v1->customerProfiles->create(
                $user['firstname'] . ' ' . $user['lastname'], 
                $user['email'], 
                "RN806dd6cd175f314e1f96a9727ee271f4", 
                    [
                        "statusCallback" => $status_callback_webhook
                    ]
                );
            
            echo '<pre>';
            echo 'customerProfile </br>';
            print_r($customerProfile->sid);
            echo '</pre>';

            $endUsers = $this->twilio->trusthub->v1->endUsers->create(
                $user['firstname'] . ' ' . $user['lastname'], 
                    "customer_profile_business_information", // type
                    [
                        "attributes" => [
                            "business_name" => "JotPhone biz",
                            "social_media_profile_urls" => "@JotPhone_biz",
                            "website_url" => "www.acme.com",
                            "business_regions_of_operation" => "ASIA",
                            "business_type" => "Limited Liability Corporation",
                            "business_registration_identifier" => "CIN",
                            "business_identity" => "direct_customer",
                            "business_industry" => "PROFESSIONAL_SERVICES",
                            "business_registration_number" => "344-7431371"
                        ]
                    ]
                );
            echo '<pre>';
            echo 'endUsers </br>';
            print_r($endUsers->sid);
            echo '</pre>';

                
            $representative_1 = $this->twilio->trusthub->v1->endUsers->create(
                $user['firstname'] . ' ' . $user['lastname'],
                    "authorized_representative_1", // type
                    [
                        "attributes" => [
                            "job_position" => "VP",
                            "last_name" => $user['lastname'],
                            "phone_number" => "+923447431371",
                            "first_name" => $user['firstname'],
                            "email" => $user['email'],
                            "business_title" => "JotPhone biz"
                        ]
                    ]
                );
            echo '<pre>';
            echo 'endUsersRepresent </br>';
            print_r($representative_1->sid);
            echo '</pre>';


            $address = $this->twilio->addresses->create(
                $user['firstname'] . ' ' . $user['lastname'],
                    "1234 Market St", // street
                    "Bahawalpur", // city
                    "PK", // region
                    "63100", // postalCode
                    "PK" // isoCountry
                );
            echo '<pre>';
            echo 'address </br>';
            print_r($address->sid);
            echo '</pre>';
            
            $endUsers_entity_assignments = $this->twilio->trusthub->v1->customerProfiles($customerProfile->sid)
                ->customerProfilesEntityAssignments
                ->create($endUsers->sid);
            echo '<pre>';
            echo 'endUsers_entity_assignments </br>';
            print_r($endUsers_entity_assignments);
            echo '</pre>';

            $representative_1_entity_assignments = $this->twilio->trusthub->v1->customerProfiles($customerProfile->sid)
                ->customerProfilesEntityAssignments
                ->create($representative_1->sid);
            echo '<pre>';
            echo 'representative_1_entity_assignments </br>';
            print_r($representative_1_entity_assignments);
            echo '</pre>';    

            $address_entity_assignments = $this->twilio->trusthub->v1->customerProfiles($customerProfile->sid)
                ->customerProfilesEntityAssignments
                ->create($address->sid);
            echo '<pre>';
            echo 'address_entity_assignments </br>';
            print_r($address_entity_assignments);
            echo '</pre>'; 
            
            //successfully created
            $customer_profiles_evaluations = $this->twilio->trusthub->v1->customerProfiles($customerProfile->sid)
                ->customerProfilesEvaluations
                ->create("RNdfbf3fae0e1107f8aded0e7cead80bf5" // policySid
                );
            echo '<pre>';
            echo 'customer_profiles_evaluations </br>';
            print_r($customer_profiles_evaluations);
            echo '</pre>';

            //successfully created
            $customer_profiles = $this->twilio->trusthub->v1->customerProfiles($customerProfile->sid)
                ->update([
                    "status" => "pending-review"
                ]
            );
            echo '<pre>';
            echo 'customer_profiles </br>';
            print_r($customer_profiles);
            echo '</pre>';
            print($customer_profiles->friendlyName);

            // User::where('id', $user->id)->update([
            //     'sub_account_sid' => $customerProfile->sid
            // ]);
            
            return true;
        } catch (\Exception $e) {
            echo 'Error creating customer profile: ', $e->getMessage();
            //return false; // Corrected typo
        }
    }

    public function deleteTwilioTenDLC($user)
    {
        try {
            $profileSid = $user['sid'];
            return $this->twilio->trusthub->v1->customerProfiles($profileSid)->delete();
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
