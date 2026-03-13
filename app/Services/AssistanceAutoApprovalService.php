<?php

namespace App\Services;

use App\Models\Assistances;
use App\Models\FarmerInformation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AssistanceAutoApprovalService
{
    public function create(int $farmerId, int $assistanceId, string $type)
    {
        return DB::transaction(function () use ($farmerId, $assistanceId, $type) {

            $farmer = FarmerInformation::with('farmProfile.farmParcels')->findOrFail($farmerId);

            $hectares = 0;

            if ($farmer->farmProfile) {
                $hectares = (float) $farmer->farmProfile->farmParcels->sum('total_farm_area');
            }

            $result = match ($type) {
                'seeds' => $this->seedMetric($hectares),
                'fertilizer' => $this->fertilizerMetric($hectares),
                default => throw new \Exception('Invalid assistance type.')
            };

            $assistance = new Assistances();
            $assistance->farmer_id = $farmerId;
            $assistance->assistance_id = $assistanceId;
            $assistance->reference_no = $this->generateReference();
            $assistance->status = 'Approved';

            $assistance->approved_by = Auth::id();
            $assistance->approved_at = now();

            $assistance->created_by = Auth::id();
            $assistance->updated_by = Auth::id();

            $assistance->uuid = Str::random(12);

            $assistance->amount = $result['amount'];
            $assistance->purpose = $result['purpose'];

            $assistance->approved_remarks =
                "Auto approved based on {$hectares} hectare farm size.";

            $assistance->save();

            return $assistance;
        });
    }

    private function generateReference()
    {
        return 'AST-' . date('Ymd') . '-' . rand(1000,9999);
    }

    private function seedMetric($hectares) {
        $bags = ceil($hectares / 0.50);

        return [
            'amount' => $bags,
            'purpose' => "Seeds - {$bags} bag(s)"
        ];
    }

    private function fertilizerMetric($hectares){
        $step = ceil($hectares / 0.20);

        $urea = $step * 10;
        $potash = $step * 10;

        return [
            'amount' => $urea + $potash,
            'purpose' => "Fertilizer - Urea {$urea} kg, Potash {$potash} kg"
        ];
    }
}