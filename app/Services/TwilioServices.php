<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
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
        $friendlyName = $user['friendlyName'];
        $email = $user['email'];
        $firstName = $user['firstName'];
        $lastName = $user['lastName'];
        $fullName = $firstName.' '.$lastName;
        $businessName = $user['businessName'];
        $socialMediaProfileUrls = $user['socialMediaProfileUrls'];
        $websiteLink = $user['websiteLink'];
        $regionOfOperations = $user['regionOfOperations'];
        $businessType = $user['businessType'];
        $businessRegistrationIdentifer = $user['businessRegistrationIdentifer'];
        $businessIdentity = $user['businessIdentity'];
        $businessIndustry = $user['businessIndustry'];
        $businessRegistrationNumber = $user['businessRegistrationNumber'];
        $jobPosition = $user['jobPosition'];
        $phoneNumber = $user['phoneNumber'];
        $businessTitle = $user['businessTitle'];
        $full_address = !empty($user['addressLine2']) ? $user['addressLine1'].' '.$user['addressLine2']:$user['addressLine1'];
        $city = $user['city'];
        $regionState = $user['regionState'];
        $zipcode = $user['zipcode'];
        $country = $user['country'];

        $registerBusiness = $user['registerBusiness'];
        $status_callback_webhook = 'https://3e3e-72-255-3-126.ngrok-free.app/webhook/ten/dlc';
        
        try {

            $customerProfile = $this->twilio->trusthub->v1->customerProfiles->create(
                $friendlyName, 
                $email, 
                config('app.TWILIO_POLICY_SID'), 
                    [
                        "statusCallback" => $status_callback_webhook,
                        "type" => "business"
                    ]
                );
           
            $secondary_customer_profile_sid = $customerProfile->sid;

            $endUsers = $this->twilio->trusthub->v1->endUsers->create(
                $fullName, 
                    "customer_profile_business_information", // type
                    [
                        "attributes" => [
                            "business_name" => $businessName,
                            "social_media_profile_urls" => $socialMediaProfileUrls,
                            "website_url" => $websiteLink,
                            "business_regions_of_operation" => $regionOfOperations,
                            "business_type" => $businessType,
                            "business_registration_identifier" => $businessRegistrationIdentifer,
                            "business_identity" => $businessIdentity, 
                            "business_industry" => $businessIndustry, 
                            "business_registration_number" => $businessRegistrationNumber
                        ]
                    ]
                );
            echo '<pre>';
            echo 'endUsers </br>';
            print_r($endUsers->sid);
            echo '</pre>';

                
            $representative_1 = $this->twilio->trusthub->v1->endUsers->create(
                $friendlyName,
                    "authorized_representative_1", // type
                    [
                        "attributes" => [
                            "job_position" => $jobPosition, //companyStatus
                            "last_name" => $lastName,
                            "phone_number" => $phoneNumber, 
                            "first_name" => $firstName,
                            "email" => $email,
                            "business_title" => $businessTitle 
                        ]
                    ]
                );
            echo '<pre>';
            echo 'endUsersRepresent </br>';
            print_r($representative_1->sid);
            echo '</pre>';


            $address = $this->twilio->addresses->create(
                $fullName,
                $full_address, // street address
                $city, // city,
                $regionState, // region, 
                $zipcode, // postalCode 
                $country // isoCountry, 
            );
            echo '<pre>';
            echo 'address </br>';
            print_r($address->sid);
            echo '</pre>';
            
            $supportingDocuments = $this->twilio->trusthub->v1->supportingDocuments->create(
                $friendlyName, // friendlyName
                "customer_profile_address", // type
                [
                    "attributes" => [
                        "address_sids" => $address->sid
                    ]
                ]
            );
            echo '<pre>';
            echo 'supportingDocuments </br>';
            print_r($supportingDocuments->sid);
            echo '</pre>';


            $endUsers_entity_assignments = $this->twilio->trusthub->v1->customerProfiles($secondary_customer_profile_sid)
                ->customerProfilesEntityAssignments
                ->create($endUsers->sid);
            echo '<pre>';
            echo 'endUsers_entity_assignments </br>';
            print_r($endUsers_entity_assignments->sid);
            echo '</pre>';

            $supportingDocument_entity_assignment = $this->twilio->trusthub->v1->customerProfiles($secondary_customer_profile_sid)
            ->customerProfilesEntityAssignments
            ->create($supportingDocuments->sid);
            echo '<pre>';
            echo 'supportingDocument_entity_assignment </br>';
            print_r($supportingDocument_entity_assignment->sid);
            echo '</pre>';

            // $address_entity_assignments = $this->twilio->trusthub->v1->customerProfiles($secondary_customer_profile_sid)
            //     ->customerProfilesEntityAssignments
            //     ->create($address->sid);
            // echo '<pre>';
            // echo 'address_entity_assignments </br>';
            // print_r($address_entity_assignments->sid);
            // echo '</pre>'; 

            $representative_1_entity_assignments = $this->twilio->trusthub->v1->customerProfiles($secondary_customer_profile_sid)
                ->customerProfilesEntityAssignments
                ->create($representative_1->sid);
            echo '<pre>';
            echo 'representative_1_entity_assignments </br>';
            print_r($representative_1_entity_assignments->sid);
            echo '</pre>';    

            // Assign Primary Customer Profile to Secondary Customer Profile
            $primaryCustomerProfile_entity_assignment = $this->twilio->trusthub->v1->customerProfiles($secondary_customer_profile_sid)
            ->customerProfilesEntityAssignments
            ->create(config('app.TWILIO_PRIMARY_USER_SID'));

            echo '<pre>';
            echo 'Primary Customer Profile Entity Assignment </br>';
            print_r($primaryCustomerProfile_entity_assignment->sid);
            echo '</pre>';
            


            //successfully created
            $customer_profiles_evaluations = $this->twilio->trusthub->v1->customerProfiles($secondary_customer_profile_sid)
                ->customerProfilesEvaluations
                ->create("RNdfbf3fae0e1107f8aded0e7cead80bf5" // policySid
                );
            echo '<pre>';
            echo 'customer_profiles_evaluations </br>';
            print_r($customer_profiles_evaluations->sid);
            echo '</pre>';

            //successfully created
            $customer_profiles = $this->twilio->trusthub->v1->customerProfiles($secondary_customer_profile_sid)
                ->update([
                    "status" => "pending-review"
                ]
            );
            echo '<pre>';
            echo 'customer_profiles </br>';
            print_r($customer_profiles);
            echo '</pre>';
            
            $customer_profiles_sid = null;
            $message = 'Profile not created! There are some problem.';
            if(isset($customer_profiles->status) && $customer_profiles->status=='in-review'){
                // User::where('id', Auth::user()->id)->update([
                //     'sub_account_sid' => $customerProfile->sid
                // ]);
                $customer_profiles_sid = $customer_profiles->sid;
                $message = 'Your registration form will be successfully saved, or your data will be prefilled and you can access the form by going to setting & Business profile.';
            }
            return [
                'sid' => $customer_profiles_sid,
                'message' => $message,
            ];

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
