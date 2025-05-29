<?php

namespace App\Http\Controllers;

use App\Models\Assistance;
use App\Models\AssistanceHistory;
use App\Models\FarmerInformation;
use App\Models\OthersFarmerInformation;
use App\Models\FarmParcel;
use App\Models\FarmParcelInformation;
use App\Models\User;
use App\Models\FarmProfile;
use App\Models\MainLivelihood;
use App\Models\FarmingType;
use App\Models\Attachments;
use App\Models\CorrectedVerified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

use Illuminate\Support\Str;
use Inertia\Inertia;

class ReportController extends Controller{
    public function activities(Request $request) {
        return Inertia::render(
            'Reports/Activities', ['reports' => array()]
        );
    }

    public function registered(Request $request) {
        return Inertia::render(
            'Reports/Registered', ['reports' => array()]
        );
    }

    public function farming(Request $request) {
        return Inertia::render(
            'Reports/Farming', ['reports' => array()]
        );
    }

    public function livelihood(Request $request) {
        return Inertia::render(
            'Reports/Livelihood', ['reports' => array()]
        );
    }
}
