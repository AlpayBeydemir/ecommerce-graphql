<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;

class PassportClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clientRepository = new ClientRepository();

        // Check if personal access client already exists
        $existingClient = Client::where('provider', 'users')
            ->where('grant_types', 'like', '%personal_access%')
            ->first();

        if (!$existingClient) {
            $this->command->info('Creating personal access client...');

            $clientRepository->createPersonalAccessClient(
                null,
                'EcommerceAPI Personal Access Client',
                'http://localhost'
            );

            $this->command->info('Personal access client created successfully!');
        } else {
            $this->command->info('Personal access client already exists. Skipping...');
        }
    }
}
