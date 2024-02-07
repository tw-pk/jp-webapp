<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class PopulateRolesTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:populate-roles-table';

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
        $role = new Role();
        $role->name = 'Admin';
        $role->guard_name = 'api';
        $role->created_by = 'admin';
        $role->last_updated_by = 'admin';
        $role->save();

        $memberRole = new Role();
        $memberRole->name = 'Member';
        $memberRole->guard_name = 'api';
        $memberRole->created_by = 'admin';
        $memberRole->last_updated_by = 'admin';
        $memberRole->save();

    }
}
