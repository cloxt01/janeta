<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class BillingService
{
    public function ontByDesc($cid): ?array {
        return (array) DB::connection('billing')->table('ont')->where('customer_id', '=', $cid)->first() ?? null;
    }
}
