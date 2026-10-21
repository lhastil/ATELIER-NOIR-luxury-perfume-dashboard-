<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

/**
 * Twenty-four fictional customers used as sample data (see README). Email
 * addresses use the reserved .test domain, and phone numbers come from the
 * 06 39 98 block that Arcep reserves for fiction, stored in international
 * format (+33639980001). Customers are matched by email, so running the seeder
 * again neither duplicates them nor overwrites their details.
 */
class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->warn('Skipped: sample customers are never created in production.');

            return;
        }

        foreach ($this->customers() as [$name, $email, $phone]) {
            Customer::firstOrCreate(['email' => $email], ['name' => $name, 'phone' => $phone]);
        }
    }

    /**
     * @return list<array{string, string, string}>
     */
    private function customers(): array
    {
        return [
            ['Amélie Laurent', 'amelie.laurent@example.test', '+33639980001'],
            ['Julien Moreau', 'julien.moreau@example.test', '+33639980002'],
            ['Clara Fontaine', 'clara.fontaine@example.test', '+33639980003'],
            ['Matteo Bianchi', 'matteo.bianchi@example.test', '+33639980004'],
            ['Sofia Lindqvist', 'sofia.lindqvist@example.test', '+33639980005'],
            ['Oliver Hart', 'oliver.hart@example.test', '+33639980006'],
            ['Inès Duval', 'ines.duval@example.test', '+33639980007'],
            ['Lukas Brenner', 'lukas.brenner@example.test', '+33639980008'],
            ['Nora Haddad', 'nora.haddad@example.test', '+33639980009'],
            ['Theo Marchetti', 'theo.marchetti@example.test', '+33639980010'],
            ['Elena Petrova', 'elena.petrova@example.test', '+33639980011'],
            ['Hugo Lefèvre', 'hugo.lefevre@example.test', '+33639980012'],
            ['Maya Okafor', 'maya.okafor@example.test', '+33639980013'],
            ['Sebastian Wolf', 'sebastian.wolf@example.test', '+33639980014'],
            ['Léa Rousseau', 'lea.rousseau@example.test', '+33639980015'],
            ['Daniel Kovač', 'daniel.kovac@example.test', '+33639980016'],
            ['Freya Nielsen', 'freya.nielsen@example.test', '+33639980017'],
            ['Gabriel Santos', 'gabriel.santos@example.test', '+33639980018'],
            ['Iris van Dijk', 'iris.van.dijk@example.test', '+33639980019'],
            ['Arthur Bennett', 'arthur.bennett@example.test', '+33639980020'],
            ['Camille Girard', 'camille.girard@example.test', '+33639980021'],
            ['Ravi Mehta', 'ravi.mehta@example.test', '+33639980022'],
            ['Zoé Martin', 'zoe.martin@example.test', '+33639980023'],
            ['Henrik Sørensen', 'henrik.sorensen@example.test', '+33639980024'],
        ];
    }
}
