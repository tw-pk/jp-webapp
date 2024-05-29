<?php

namespace App\Models;

use App\Http\Resources\ConversationResource;
use App\Http\Resources\TwilioConversationResource;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Laravel\Cashier\Billable;
use Laravel\Cashier\Subscription;
use Laravel\Sanctum\HasApiTokens;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Twilio\Exceptions\ConfigurationException;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, Billable, HasRoles, SoftDeletes;

    protected $gaurd_name = 'api';
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'firstname',
        'lastname',
        'email',
        'password',
        'is_owner',
        'phone_number_verified',
        'terms_agreed',
        'stripe_id',
        'privacy_policy_agreed',
        'last_login_at',
        'pm_type',
        'pm_last_four',
        'trial_ends_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function paymentMethods(): HasMany
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function defaultPaymentMethod()
    {
        return $this->paymentMethods()->where('default', true)->first();
    }

    public function fullName(): string
    {
        return $this->firstname . " " . $this->lastname;
    }

    public function credit(): HasOne
    {
        return $this->hasOne(UserCredit::class);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function numbers(): HasMany
    {
        return $this->hasMany(UserNumber::class, 'user_id')->orderBy('id', 'desc');
    }

    public function otps(): HasMany
    {
        return $this->hasMany(PasswordVerification::class, 'user_id');
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class)->orderBy('created_at', 'desc');
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    public function userInvitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class, 'user_id');
    }

    public function invitationsMember(): hasOne
    {
        return $this->hasOne(Invitation::class, 'member_id');
    }

    public function calls(): HasMany
    {
        return $this->hasMany(Call::class);
    }

    public function note(): HasOne
    {
        return $this->hasOne(Note::class);
    }

    public function twoFactorProfile(): HasOne
    {
        return $this->hasOne(TwoFactorProfiles::class);
    }

    /**
     * @throws ConfigurationException
     * @throws TwilioException
     */
    public function fetchConversations($query = null): TwilioConversationResource
    {

        $sid = config('app.TWILIO_CLIENT_ID');
        $authToken = config('app.TWILIO_AUTH_TOKEN');
        $twilio = new Client($sid, $authToken);

        $chats = [];
        $filteredContacts = [];

        if ($query) {
            $conversations = $this->conversations
                ->whereHas('contact', function ($q) use ($query) {
                    $q->whereRaw("CONCAT(firstname, ' ', lastname) = ?", [$query])
                        ->orWhere('phone_number', 'iLIKE', '%' . $query . '%');
                });

            $contacts = $this->contacts
                ->whereHas('contact', function ($q) use ($query) {
                    $q->whereRaw("CONCAT(firstname, ' ', lastname) = ?", [$query])
                        ->orWhere('phone_number', 'iLIKE', '%' . $query . '%');
                });
        } else {
            $conversations = $this->conversations;
            $contacts = $this->contacts;
        }

        // Count unread messages
        $unreadCount = 0;
        $chat = [];
        foreach ($conversations as $conversation) {
            $twilio_conversation = $twilio->conversations->v1->conversations($conversation->sid)->fetch();
            $messages = $twilio->conversations->v1->conversations($conversation->sid)->messages->read([], 20);
            if (count($messages)) {
                $messages = new Collection($messages);
                $lastMessage = $messages->last();
                if (is_null($lastMessage->delivery)) {
                    ++$unreadCount;
                } else {
                    if ($lastMessage->delivery["read"] === "all" || $lastMessage->delivery["read"] === "some" || $lastMessage->delivery["read"] === "none") {
                        ++$unreadCount;
                    }
                }

                $chat = [
                    'id' => $lastMessage->index,
                    'lastMessage' => [
                        'message' => $lastMessage->body,
                        "time" => Carbon::createFromDate($lastMessage->dateCreated)->setTimezone('Asia/Karachi')->toDateTimeString(),
                        "senderId" => $this->id,
                        "feedback" => [
                            "isSent" => is_null($lastMessage->delivery) || $lastMessage->delivery["sent"] === "all" ?? false,
                            "isDelivered" => is_null($lastMessage->delivery) ||  $lastMessage->delivery["delivered"] === "all" ?? false,
                            "isSeen" => is_null($lastMessage->delivery) || $lastMessage->delivery["read"] === "all" || $lastMessage->delivery["read"] === "some" ?? false
                        ]
                    ]
                ];
            }

            $chats[] = [
                'id' => $conversation->contact?->id,
                'sid' => $twilio_conversation->sid,
                'phone_number' => $conversation->contact?->phone,
                'contact_id' => $conversation->contact_id,
                'fullName' => $conversation->contact?->fullName(),
                'chat' => $chat,
                'role' => 'contact',
                'about' => "",
                'avatar' => '',
                'status' => '',
                'unseenMsgs' => $unreadCount,
            ];
        }

        foreach ($contacts as $contact) {
            $filteredContacts[] = [
                'id' => $contact->id,
                'fullName' => $contact->fullName(),
                'role' => 'contact',
                'about' => $contact->phone,
                'avatar' => '',
                'status' => 'online',
                'phone_number' => $contact->phone
            ];
        }

        $data = [
            'chatContacts' => $chats,
            'contacts' => $filteredContacts,
            'profileUser' => [
                'id' => $this->id,
                'avatar' => $this->profile?->avatar,
                'fullName' => $this->name,
                'role' => 'admin',
                'about' => 'Dessert chocolate cake lemon drops jujubes. Biscuit cupcake ice cream bear claw brownie marshmallow.',
                'status' => 'online',
                'settings' => [
                    'isTwoStepAuthVerificationEnabled' => true,
                    'isNotificationsOn' => true,
                ]
            ]
        ];

        return new TwilioConversationResource($data);
    }

    /**
     * @throws ConfigurationException
     * @throws TwilioException
     */
    public function fetchChat($id): ConversationResource
    {
        $sid = config('app.TWILIO_CLIENT_ID');
        $authToken = config('app.TWILIO_AUTH_TOKEN');
        $twilio = new Client($sid, $authToken);

        $conversation = Auth::user()->conversations->where('contact_id', $id)->first();

        if (!$conversation) {
            $contact = Contact::find($id);
            $data = [
                'chat' => "",
                'contact' => [
                    'id' => $contact->id,
                    'fullName' => $contact->fullName(),
                    'role' => 'contact',
                    'about' => $contact->phone,
                    'avatar' => '',
                    'status' => 'online',
                    'phone_number' => $contact->phone
                ],
            ];

            return new ConversationResource($data);
        } else {

            $twilio_conversation = $twilio->conversations->v1->conversations($conversation->sid)->fetch();
            $unreadCount = 0;

            $messages = $twilio->conversations->v1->conversations($conversation->sid)->messages->read(['order', 'asc'], 10);
            $messages = new Collection($messages);

            $chat_messages = [];

            if (count($messages)) {
                foreach ($messages as $message) {
                    $chat_messages[] = [
                        'id' => $message->index,
                        'message' => $message->body,
                        "time" => Carbon::createFromDate($message->dateCreated)->setTimezone('Asia/Karachi')->toDateTimeString(),
                        "senderId" => $this->id,
                        "feedback" => [
                            "isSent" => is_null($message->delivery) || $message->delivery["sent"] === "all" ?? false,
                            "isDelivered" => is_null($message->delivery) || $message->delivery["delivered"] === "all" ?? false,
                            "isSeen" => is_null($message->delivery) || $message->delivery["read"] === "all" || $message->delivery["read"] === "some" ?? false
                        ],
                    ];
                }
            }

            $chat = [
                'id' => $conversation->contact->id,
                'userId' => $this->id,
                'sid' => $twilio_conversation->sid,
                'phone_number' => $conversation->contact->phone,
                'contact_id' => $conversation->contact_id,
                'fullName' => $conversation->contact->fullName(),
                'messages' => $chat_messages,
                'role' => 'contact',
                'about' => "",
                'avatar' => '',
                'status' => '',
            ];

            $data = [
                'chat' => $chat,
                'contact' => [
                    'id' => $conversation->contact->id,
                    'fullName' => $conversation->contact->fullName(),
                    'role' => 'contact',
                    'about' => $conversation->contact->phone,
                    'avatar' => '',
                    'status' => 'online',
                    'phone_number' => $conversation->contact->phone
                ],
            ];
            return new ConversationResource($data);
        }
    }
}
