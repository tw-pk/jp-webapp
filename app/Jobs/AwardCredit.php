<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\UserCredit;
use App\Notifications\CreditAwarded;
use App\Notifications\NewSubscriptionCreated;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AwardCredit implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private int $id;

    /**
     * @param int $id
     */
    public function __construct(int $id)
    {
        $this->id = $id;
    }


    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $user = User::find($this->id);

        if ($user) {
            $subscription = $user->subscriptions()->where('name', 'Basic')->first();
            $user->notify(new NewSubscriptionCreated($subscription->id));

            $credit = $this->creditCustomerAccount($user, [
                'amount' => 5,
                'currency' => 'usd'
            ]);

            if ($credit) {
                $user->notify(new CreditAwarded($credit->credit));
            }
        }
    }


    /**
     */
    private function creditCustomerAccount(User $customer, array $payload): ?UserCredit
    {
        if ($customer->credit) {
            $customer->credit->credit += $payload['amount'];
            $customer->credit->save();

            return $customer->credit;
        } else {
            $customer_credit = new UserCredit();
            $customer_credit->user_id = $customer->id;
            $customer_credit->credit += $payload['amount'];
            $customer_credit->save();

            return $customer_credit;
        }
    }
}
