<?php

namespace App\Console\Commands;

use App\Services\OltService;
use Illuminate\Console\Command;

class JanetaTestOltCommand extends Command
{
    protected $signature = 'janeta:test-olt {olt? : Nama OLT, atau kosong untuk menguji semua}';
    protected $description = 'Test koneksi SSH dan command OLT JANETA';

    public function handle(OltService $olt): int
    {
        $names = $this->argument('olt')
            ? [strtoupper($this->argument('olt'))]
            : $olt->names();
        $failed = false;

        foreach ($names as $name) {
            $this->line("Menguji {$name}...");
            $result = $olt->testConnection($name);
            $this->line(($result['ok'] ? '<info>OK</info>' : '<error>GAGAL</error>') . " - {$result['message']}");
            $failed = $failed || !$result['ok'];
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
