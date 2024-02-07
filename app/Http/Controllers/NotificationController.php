<?php

namespace App\Http\Controllers;

use App\Events\Hello;
use App\Events\TestHello;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;

class NotificationController extends Controller
{

    public function get_notifications()
    {
        event(new TestHello(1, 'This is a test message'));

        $notifications = [
            [
                'id' => 1,
                'img' => 'avatar4', // Replace with the actual path or URL to the image
                'title' => 'Congratulation Flora! 🎉',
                'subtitle' => 'Won the monthly best seller badge',
                'time' => 'Today',
                'isSeen' => true,
            ],
            [
                'id' => 2,
                'text' => 'Tom Holland',
                'title' => 'New user registered.',
                'subtitle' => '5 hours ago',
                'time' => 'Yesterday',
                'isSeen' => false,
            ],
            [
                'id' => 3,
                'img' => 'avatar5', // Replace with the actual path or URL to the image
                'title' => 'New message received 👋🏻',
                'subtitle' => 'You have 10 unread messages',
                'time' => '11 Aug',
                'isSeen' => true,
            ],
            [
                'id' => 4,
                'img' => 'paypal', // Replace with the actual path or URL to the image
                'title' => 'Paypal',
                'subtitle' => 'Received Payment',
                'time' => '25 May',
                'isSeen' => false,
                'color' => 'error',
            ],
            [
                'id' => 5,
                'img' => 'avatar3', // Replace with the actual path or URL to the image
                'title' => 'Received Order 📦',
                'subtitle' => 'New order received from john',
                'time' => '19 Mar',
                'isSeen' => true,
            ],
        ];

         return response()->json([
             'notifications' => $notifications,
         ]);


    }
}
