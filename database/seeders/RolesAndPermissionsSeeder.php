<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            'gestionar-usuarios',
            'ver-reportes',
            'gestionar-clientes',
            'borrar-clientes',
            'gestionar-abonos',
            'borrar-abonos',
            'gestionar-lotificaciones'
        ];

        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign created permissions
        $roleAgente = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Agente']);
        $roleAgente->givePermissionTo(['gestionar-clientes', 'gestionar-abonos']);

        $roleGerente = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Gerente']);
        $roleGerente->givePermissionTo([
            'gestionar-clientes', 
            'gestionar-abonos', 
            'ver-reportes'
        ]);

        $roleAdmin = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Administrador']);
        $roleAdmin->givePermissionTo(\Spatie\Permission\Models\Permission::all());

        // Assign Admin role to first user if exists
        $user = \App\Models\User::first();
        if ($user) {
            $user->assignRole($roleAdmin);
        }
    }
}
