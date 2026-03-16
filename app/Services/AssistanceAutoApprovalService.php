<?php

namespace App\Services;

use App\Models\Assistances;
use App\Models\FarmerInformation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

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

            $assistance = false;
            if (isset($result['amount']) && $result['amount'] > 0) {
                $assistance = new Assistances();
                $assistance->farmer_id = $farmerId;
                $assistance->assistance_id = $assistanceId;
                $assistance->livelihood = 'farmer';
                $assistance->reference_no = $this->generateReference();
                $assistance->status = 'Approved';
    
                $assistance->approved_by = Auth::id();
                $assistance->approved_at = now();
    
                $assistance->created_by = Auth::id();
    
                $assistance->uuid = Str::random(12);
    
                $assistance->amount = $result['amount'];
                $assistance->purpose = $result['purpose'];
                $assistance->is_generated = 1;
    
                $assistance->approved_remarks =
                    "Auto generated and approved assistance after registration based on `{$hectares}` hectare farm size.";
    
                $assistance->save();
            }

            return $assistance;
        });
    }

    private function generateReference() {
        return DB::transaction(function () {

            $year = Carbon::now()->format('y');
            $lastRecord = DB::table('assistances')
                ->whereYear('created_at', Carbon::now()->year)
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            if ($lastRecord && preg_match('/RSS-\d{2}-(\d+)/', $lastRecord->reference_no, $matches)) {
                $nextNumber = (int)$matches[1] + 1;
            } else {
                $nextNumber = 1;
            }

            $sequence = str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            return "RSS-$year-$sequence";
        });
    }

    private function seedMetric($hectares) {
        $hectares = floatval($hectares);
        if ($hectares < 0.10) {
            return [
                'amount' => 0,
                'purpose' => "Seeds - No assistance due to zero or negative farm size"
            ];
        }

        $bags = ceil($hectares / 0.50);

        return [
            'amount' => $bags,
            'purpose' => "Seeds - {$bags} bag(s)"
        ];
    }

    private function fertilizerMetric($hectares){
        $hectares = floatval($hectares);
        if ($hectares < 0.10) {
            return [
                'amount' => 0,
                'purpose' => "Fertilizer - No assistance due to zero or negative farm size"
            ];
        }

        $step = ceil($hectares / 0.20);

        $urea = $step * 10;
        $potash = $step * 10;

        return [
            'amount' => $urea + $potash,
            'purpose' => "Fertilizer - Urea {$urea} kg, Potash {$potash} kg"
        ];
    }

    //limit metrics to max of 10 hectares
    private function seedMetric_limit($hectares) {
        // cap to 10 hectares
        $hectares = min($hectares, 10);

        // each 0.50 ha = 1 bag
        $bags = ceil($hectares / 0.50);

        return [
            'amount' => $bags,
            'purpose' => "Seeds - {$bags} bag(s)"
        ];
    }

    private function fertilizerMetric_limit($hectares) {
        // cap to 10 hectares
        $hectares = min($hectares, 10);

        // each 0.20 ha = 10kg step
        $step = ceil($hectares / 0.20);

        $urea = $step * 10;
        $potash = $step * 10;

        return [
            'amount' => $urea + $potash,
            'purpose' => "Fertilizer - Urea {$urea} kg, Potash {$potash} kg"
        ];
    }
}