<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MoveImagesToR2 extends Command
{
    protected $signature = 'images:move-to-r2';

    protected $description = 'Migrate image files from the public disk to Cloudflare R2';

    public function handle(): int
    {
        // Jalankan command ini hanya setelah .env R2 terisi dan bucket public domain aktif.
        $publicDisk = Storage::disk('public');
        $r2Disk = Storage::disk('r2');
        $imageColumns = [
            ['table' => 'products', 'column' => 'image'],
            ['table' => 'stores', 'column' => 'logo_path'],
            ['table' => 'app_settings', 'column' => 'app_logo'],
            ['table' => 'app_settings', 'column' => 'favicon'],
            ['table' => 'expenses', 'column' => 'attachment'],
        ];

        foreach ($imageColumns as $imageColumn) {
            $table = $imageColumn['table'];
            $column = $imageColumn['column'];

            foreach (DB::table($table)->whereNotNull($column)->get([$column]) as $record) {
                $path = $record->{$column};

                if (! $publicDisk->exists($path)) {
                    $this->info("Skipped {$table}.{$column}: {$path}");

                    continue;
                }

                $r2Disk->put($path, $publicDisk->get($path), 'public');
                $this->info("Migrated {$table}.{$column}: {$path}");
            }
        }

        return self::SUCCESS;
    }
}
