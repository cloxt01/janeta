<?php
namespace App\Http\Controllers\Olt;

use App\Http\Controllers\Controller;
use App\Models\OltServiceRequest;
use App\Support\OltActivityPresenter;

class OltActivityLogController extends Controller
{
    public function index()
    {
        $initial = OltServiceRequest::with(['olt', 'user'])
            ->latest('id')->limit(100)->get()
            ->map(fn ($t) => OltActivityPresenter::make($t))
            ->values();

        return view('dashboard.activity.olt-activity', compact('initial'));
    }
}
