<?php

namespace Platform\Sales\Console\Commands;

use Illuminate\Console\Command;
use Platform\Sales\Database\Seeders\SalesLookupSeeder;

class SeedSalesLookupData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sales:seed-lookup-data 
                            {--force : Force the operation to run even in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed the Sales module lookup data (priorities, sources, types)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('force') || $this->confirm('This will seed Sales lookup data. Continue?')) {
            $this->info('Seeding Sales lookup data...');
            
            try {
                $seeder = new SalesLookupSeeder();
                $seeder->run();
                
                $this->info('✅ Sales lookup data seeded successfully!');
                $this->line('');
                $this->line('Seeded data:');
                $this->line('  • Sales Priorities (Niedrig, Mittel, Hoch, Dringend)');
                $this->line('  • Sales Deal Sources (Website, Empfehlung, Kaltakquise, etc.)');
                $this->line('  • Sales Deal Types (Neukunde, Upsell, Cross-Sell, etc.)');
                
            } catch (\Exception $e) {
                $this->error('❌ Failed to seed Sales lookup data: ' . $e->getMessage());
                return 1;
            }
        } else {
            $this->info('Operation cancelled.');
        }

        return 0;
    }
}
