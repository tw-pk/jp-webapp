<?php

namespace App\Http\Controllers;

use App\Models\UserProfile;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Contact;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Events\NewNotificationEvent;

class ContactController extends Controller
{

    public function list(Request $request)
    {
        $searchQuery = $request->input('q');
        $options = $request->input('options');

        $query = Contact::with('userProfile:contact_id,avatar')
            ->select('id', 'user_id', 'firstname', 'lastname', 'email', 'phone', 'company_name','shared')
            ->where('user_id', Auth::user()->id);

        if ($searchQuery) {
            $query->where(function ($query) use ($searchQuery) {
                $query->where('firstname', 'like', '%' . $searchQuery . '%')
                    ->orWhere('lastname', 'like', '%' . $searchQuery . '%')
                    ->orWhere('email', 'like', '%' . $searchQuery . '%')
                    ->orWhere('phone', 'like', '%' . $searchQuery . '%');
            });
        }

        $query->when(!empty($options['sortBy']) && is_array($options['sortBy']), function ($query) use ($options) {
            foreach ($options['sortBy'] as $sort) {
                $key = $sort['key'];
                $key = $key === 'fullname' ? 'firstname' : $key;
                $order = strtolower($sort['order']) === 'asc' ? 'asc' : 'desc';
                $query->orderBy($key, $order);
            }
        }, function ($query) {
            $query->orderBy('id', 'desc');
        });

        $totalRecord = $query->count();
        $perPage = 5;
        $currentPage = $options['page'] ?? 1;
        $contacts = $query->paginate($perPage, ['*'], 'page', $currentPage);
        $totalPage = ceil($totalRecord / $perPage);

        foreach ($contacts as &$contact) {
            $contact->contactId = encrypt($contact->id);
            $contact->fullname = $contact->fullName();
            $contact->shared = $contact->shared==1? true:false;
            $contact->avatar = optional($contact->userProfile)->avatar;
            $contact->avatarPath = $contact->avatar ? asset('storage/' . $contact->avatar) : '';
        }

        return response()->json([
            'contacts' => $contacts,
            'totalPage' => $totalPage,
            'totalRecord' => $totalRecord,
            'page' => $currentPage,
        ]);
    }

    public function add_contact(Request $request)
    {
        $requestData = $request->all();
        if (!$request->hasFile('avatar')) {
            $requestData['avatar'] = null;
        }
        $request->merge($requestData);

        $request->validate([
            'avatar' => 'nullable|file|mimes:jpeg,jpg,png,bmp,gif,svg',
            'firstname' => 'required|string',
            'lastname' => 'required|string',
            'email' => 'required|email|unique:contacts,email,' . ($request->all() ? $request->id : 'NULL') . ',id',
            'phone' => 'required',
            'company_name' => 'required|string',
        ]);

        $contact = Contact::find($request->id);
        if (!$contact) {
            $contact = new Contact();
        }
        $contact->user_id = Auth::user()->id;
        $contact->firstname = $request->firstname;
        $contact->lastname = $request->lastname;
        $contact->email = $request->email;
        $contact->phone = $request->phone;
        $contact->company_name = $request->company_name;
        try {
            $contact->save();
            if ($request->hasFile('avatar')) {

                if ($contact->userProfile && $contact->userProfile->avatar) {
                    Storage::delete('public/' . $contact->userProfile->avatar);
                }
                $userProfile = $contact->userProfile;
                if (!$userProfile) {
                    $userProfile = new UserProfile();
                    $userProfile->contact_id = $contact->id;
                }
                $avatarPath = $request->file('avatar')->store('', 'public');
                $userProfile->avatar = $avatarPath;
                $userProfile->save();
            }

            // After adding a contact
            $this->sendNotification('Contact Added/Updated', 'You have added/Updated a new contact', $contact->firstname . ' ' . $contact->lastname, 'primary');

            return response()->json([
                'message' => 'Contact has been saved Successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while adding data'], 500);
        }
    }

    public function sharedContact(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'shared' => 'required|boolean',
        ]);

        try {
            $shared = $request->shared;
            Contact::where('id', $request->id)->update([
                'shared' => $shared,
            ]);
            
            $roleName = Auth::user()->getRoleNames()->first();
            $role = $roleName == 'Admin' ? 'member' : 'admin';
            $message = $shared == 1 ? "Contact has been successfully shared with $role." : "Contact is no longer shared with $role.";
            return response()->json(['message' => $message]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while sharing the contact.'], 500);
        }
    }

    public function findContact(Request $request)
    {
        $request->validate([
            'contact_id' => 'required|string'
        ]);

        $contact_id = decrypt($request->contact_id);

        if (!Contact::find($contact_id)) {
            return response()->json([
                'status' => false,
                'message' => 'Contact does not exist'
            ], 404);
        }

        return response()->json([
            'status' => true,
        ]);
    }

    public function details(Request $request)
    {
        $request->validate([
            'contact_id' => 'required|string'
        ]);

        $contact_id = decrypt($request->contact_id);

        $contact = Contact::find($contact_id);

        $contactDetails = [
            'fullName' => $contact->fullName(),
            'email' => $contact->email,
            'phone' => $contact->phone,
            'company_name' => $contact->company_name,
            'joinedAt' => Carbon::parse($contact->created_at)->format('d M, Y'),
            'avatar' => $contact->userProfile ? ($contact->userProfile->avatar ? asset('storage/' . $contact->userProfile->avatar) : null) : null
        ];

        return response()->json([
            'status' => true,
            'contactData' => $contactDetails
        ]);
    }


    public function delete_contact($id)
    {
        $contactId = $id;
        try {
            $contact = Contact::find($contactId);
            if ($contact) {
                // Delete the associated image
                if ($contact->userProfile && $contact->userProfile->avatar) {
                    Storage::delete('public/' . $contact->userProfile->avatar); // Delete the image file
                }
                $contact->delete();

                // After deleting a contact
                $this->sendNotification('Contact Deleted', 'You have deleted a contact', $contact->firstname . ' ' . $contact->lastname, 'error');
                return response()->json(['message' => 'Contact deleted successfully']);
            } else {
                return response()->json(['message' => 'Contact not found'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete team', 'error' => $e->getMessage()], 500);
        }
    }

    public function sendNotification($title = null, $subtitle = null, $text = null, $color = null)
    {
        $notificationInfo = [
            'title' => $title,
            'subtitle' => $subtitle,
            'text' => $text,
            'color' => $color,
        ];
        broadcast(new NewNotificationEvent(Auth::user()->id, $notificationInfo));
    }
}
