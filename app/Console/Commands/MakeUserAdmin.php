<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class MakeUserAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:make-user-admin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->ask('Enter email of user');
        $user = User::where('email', $email)->first();

        if($user){
            $role = Role::where('name', 'Admin')->first();
            $user->assignRole($role);
        }else{
            $this->info('User does not exists');
        }
    }
}
