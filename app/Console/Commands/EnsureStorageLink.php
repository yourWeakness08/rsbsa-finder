<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class EnsureStorageLink extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ensure:storage-link';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ensure the public/storage symlink exists';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $link = public_path('storage');
        $target = storage_path('app/public');

        if (!file_exists($link)) {
            symlink($target, $link);
            $this->info('Symlink created: public/storage → storage/app/public');
        } else {
            $this->info('Symlink already exists.');
        }
    }
}
