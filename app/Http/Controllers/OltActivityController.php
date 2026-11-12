<?php
namespace App\Http\Controllers;

use App\Models\OltServiceRequest;
use App\Support\OltActivityPresenter;

class OltActivityController extends Controller
{
    public function index()
    {
        $initial = OltServiceRequest::with(['olt', 'user'])
            ->latest('id')->limit(100)->get()
            ->map(fn ($t) => OltActivityPresenter::make($t))
            ->values();

        return view('dashboard.olt-activity', compact('initial'));
    }
}