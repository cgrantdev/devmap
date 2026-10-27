<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TailLog extends Command
{
    protected $signature = 'tail-log {--lines=100}';
    protected $description = 'Dump last N lines of storage/logs/laravel.log.';

    public function handle(): int
    {
        $path = storage_path('logs/laravel.log');
        if (!is_file($path)) {
            $this->warn('No laravel.log found.');
            return self::SUCCESS;
        }
        $lines = (int) $this->option('lines') ?: 100;
        $cmd = sprintf('tail -n %d %s', $lines, escapeshellarg($path));
        $output = @shell_exec($cmd) ?: '';
        $this->line($output);
        return self::SUCCESS;
    }
}
