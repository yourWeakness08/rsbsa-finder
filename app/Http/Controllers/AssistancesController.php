<?php

namespace App\Http\Controllers;

use App\Models\Assistances;
use App\Models\Assistance;
use App\Models\FarmerInformation;
use App\Models\MainLivelihood;
use App\Models\AssistancesAttachments as Attachments;
use App\Models\FarmingType;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Services\ActivityLogger;

use Illuminate\Support\Str;
use Inertia\Inertia;

class AssistancesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request){
        $paginate = $request->paginate ? intval($request->paginate): 10;
        $status = $request->status ?? 'All';

        $assistances = Assistances::leftJoin('users as b', 'b.id', '=', 'assistances.created_by')
            ->select(DB::raw("assistances.*, CONCAT(b.firstname, ' ', b.lastname) as created_name, CONCAT(
                    c.firstname, ' ',
                    IF(c.middlename IS NOT NULL AND c.middlename != '', CONCAT(LEFT(c.middlename, 1), '. '), ''),
                    c.lastname,
                    IF(c.suffix IS NOT NULL AND c.suffix != '', CONCAT(' ', c.suffix), '')
                ) AS name, d.name as assistance_name, CONCAT(
                    IF(e.firstname IS NOT NULL AND e.firstname != '', CONCAT(e.firstname, ' '), ''),
                    IF(e.middlename IS NOT NULL AND e.middlename != '', CONCAT(LEFT(e.middlename, 1), '. '), ''),
                    IF(e.lastname IS NOT NULL AND e.lastname != '', CONCAT(e.lastname, ' '), ''),
                    IF(e.suffix IS NOT NULL AND e.suffix != '', CONCAT(e.suffix, ' '), '')
                ) AS approved_name, CONCAT(
                    IF(f.firstname IS NOT NULL AND f.firstname != '', CONCAT(f.firstname, ' '), ''),
                    IF(f.middlename IS NOT NULL AND f.middlename != '', CONCAT(LEFT(f.middlename, 1), '. '), ''),
                    IF(f.lastname IS NOT NULL AND f.lastname != '', CONCAT(f.lastname, ' '), ''),
                    IF(f.suffix IS NOT NULL AND f.suffix != '', CONCAT(f.suffix, ' '), '')
                ) AS cancelled_name, CONCAT(
                    IF(g.firstname IS NOT NULL AND g.firstname != '', CONCAT(g.firstname, ' '), ''),
                    IF(g.middlename IS NOT NULL AND g.middlename != '', CONCAT(LEFT(g.middlename, 1), '. '), ''),
                    IF(g.lastname IS NOT NULL AND g.lastname != '', CONCAT(g.lastname, ' '), ''),
                    IF(g.suffix IS NOT NULL AND g.suffix != '', CONCAT(g.suffix, ' '), '')
                ) AS disapproved_name, CONCAT(
                    IF(h.firstname IS NOT NULL AND e.firstname != '', CONCAT(h.firstname, ' '), ''),
                    IF(h.middlename IS NOT NULL AND e.middlename != '', CONCAT(LEFT(h.middlename, 1), '. '), ''),
                    IF(h.lastname IS NOT NULL AND e.lastname != '', CONCAT(h.lastname, ' '), ''),
                    IF(h.suffix IS NOT NULL AND e.suffix != '', CONCAT(h.suffix, ' '), '')
                ) AS updated_name"))
            ->leftJoin('farmer_information as c', 'c.id', '=', 'assistances.farmer_id')
            ->leftJoin('assistance as d', 'd.id', '=', 'assistances.assistance_id')
            ->leftJoin('farmer_information as e', 'e.id', '=', 'assistances.approved_by')
            ->leftJoin('farmer_information as f', 'f.id', '=', 'assistances.cancelled_by')
            ->leftJoin('farmer_information as g', 'g.id', '=', 'assistances.disapproved_by')
            ->leftJoin('farmer_information as h', 'h.id', '=', 'assistances.updated_by')
            ->where('assistances.is_archived', 0)
            ->orderBy('assistances.created_at', 'desc')
            ->where( function($query) use ($request) {
                if ($request->search) {
                    $query->where('c.firstname', 'like', '%'.$request->search.'%')
                    ->orWhere('c.lastname', 'like', '%'.$request->search.'%')
                    ->orWhere('c.middlename', 'like', '%'.$request->search.'%')
                    ->orWhere('assistances.livelihood', 'like', '%'.$request->search.'%')
                    ->orWhere('assistances.status', 'like', '%'.$request->search.'%');
                }
            })
            ->where(function($query) use ($request) {
                if ($request->status && strtolower($request->status) !== 'all') {
                    $query->whereRaw('LOWER(assistances.status) = ?', [strtolower($request->status)]);
                }
            })
            ->paginate($paginate);
        $assistances->appends(['paginate' => $paginate]);

        if($request->paginate == 'All'){
            $assistances = Assistances::LeftJoin('users as b', 'b.id', '=', 'assistances.created_by')
            ->select(DB::raw("assistances.*, CONCAT(b.firstname, ' ', b.lastname) as created_name, CONCAT(
                    c.firstname, ' ',
                    IF(c.middlename IS NOT NULL AND c.middlename != '', CONCAT(LEFT(c.middlename, 1), '. '), ''),
                    c.lastname,
                    IF(c.suffix IS NOT NULL AND c.suffix != '', CONCAT(' ', c.suffix), '')
                ) AS name, d.name as assistance_name, CONCAT(
                    IF(e.firstname IS NOT NULL AND e.firstname != '', CONCAT(e.firstname, ' '), ''),
                    IF(e.middlename IS NOT NULL AND e.middlename != '', CONCAT(LEFT(e.middlename, 1), '. '), ''),
                    IF(e.lastname IS NOT NULL AND e.lastname != '', CONCAT(e.lastname, ' '), ''),
                    IF(e.suffix IS NOT NULL AND e.suffix != '', CONCAT(e.suffix, ' '), '')
                ) AS approved_name, CONCAT(
                    IF(f.firstname IS NOT NULL AND f.firstname != '', CONCAT(f.firstname, ' '), ''),
                    IF(f.middlename IS NOT NULL AND f.middlename != '', CONCAT(LEFT(f.middlename, 1), '. '), ''),
                    IF(f.lastname IS NOT NULL AND f.lastname != '', CONCAT(f.lastname, ' '), ''),
                    IF(f.suffix IS NOT NULL AND f.suffix != '', CONCAT(f.suffix, ' '), '')
                ) AS cancelled_name, CONCAT(
                    IF(g.firstname IS NOT NULL AND g.firstname != '', CONCAT(g.firstname, ' '), ''),
                    IF(g.middlename IS NOT NULL AND g.middlename != '', CONCAT(LEFT(g.middlename, 1), '. '), ''),
                    IF(g.lastname IS NOT NULL AND g.lastname != '', CONCAT(g.lastname, ' '), ''),
                    IF(g.suffix IS NOT NULL AND g.suffix != '', CONCAT(g.suffix, ' '), '')
                ) AS disapproved_name, CONCAT(
                    IF(h.firstname IS NOT NULL AND e.firstname != '', CONCAT(h.firstname, ' '), ''),
                    IF(h.middlename IS NOT NULL AND e.middlename != '', CONCAT(LEFT(h.middlename, 1), '. '), ''),
                    IF(h.lastname IS NOT NULL AND e.lastname != '', CONCAT(h.lastname, ' '), ''),
                    IF(h.suffix IS NOT NULL AND e.suffix != '', CONCAT(h.suffix, ' '), '')
                ) AS updated_name"))
            ->leftJoin('farmer_information as c', 'c.id', '=', 'assistances.farmer_id')
            ->leftJoin('assistance as d', 'd.id', '=', 'assistances.assistance_id')
            ->leftJoin('farmer_information as e', 'e.id', '=', 'assistances.approved_by')
            ->leftJoin('farmer_information as f', 'f.id', '=', 'assistances.cancelled_by')
            ->leftJoin('farmer_information as g', 'g.id', '=', 'assistances.disapproved_by')
            ->leftJoin('farmer_information as h', 'h.id', '=', 'assistances.updated_by')
            ->where('assistances.is_archived', 0)
            ->where(function($query) use($request){
                if ($request->search) {
                    $query->where('c.firstname', 'like', '%'.$request->search.'%')
                    ->orWhere('c.lastname', 'like', '%'.$request->search.'%')
                    ->orWhere('c.middlename', 'like', '%'.$request->search.'%')
                    ->orWhere('assistances.livelihood', 'like', '%'.$request->search.'%')
                    ->orWhere('assistances.status', 'like', '%'.$request->search.'%');
                }
            })
            ->where(function($query) use ($request) {
                if ($request->status && strtolower($request->status) !== 'all') {
                    $query->whereRaw('LOWER(assistances.status) = ?', [strtolower($request->status)]);
                }
            })
            ->orderBy('assistances.created_at', 'desc')
            ->get();
            $assistances->all();
        }

        foreach($assistances as $key => $rs) {
            $attachments = Attachments::select('filename')
                ->where('assistance_id', $rs->id)
                ->get();

            $attachments->transform(function ($attachment) use ($rs) {
                $attachment->filepath = public_path('uploads/assistances/assistance_'.$rs->id.'/'.$attachment->filename);
                $attachment->url = $attachment->filename && file_exists(public_path('uploads/assistances/assistance_'.$rs->id.'/'.$attachment->filename)) ? asset('uploads/assistances/assistance_'.$rs->id.'/'.$attachment->filename) : null;

                if ($attachment->url) {
                    $extension = pathinfo($attachment->url, PATHINFO_EXTENSION);
                    $attachment->extension = $extension;
                }

                return $attachment;
            });
            $assistances[$key]->attachments = $attachments;
        }

        $farmers = $this->get_all_farmers();
        $assistance = $this->get_all_assistance();

        return Inertia::render(
            'Assistances/Index', [ 'assistances' => $assistances, 'filter' => $request, 'farmer' => $farmers, 'assistance' => $assistance ]
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Assistances $assistances, ActivityLogger $activityLogger) {
        $user_id = $request->user_id;
        $state = false;

        $reference = $this->generateReferenceNo();
        if ($request->farmer && $request->assistance && $request->remarks) {
            $checkAssistance = Assistance::where('id', $request->assistance)->value('name');

            $status = 'Pending';
            $amount = 0;
            $remarks = null;

            // limit cash assistance for farmers only
            if ($checkAssistance && $request->livelihood == 'farmer' && str_contains(strtolower($checkAssistance), 'cash')) {
                $calculateAssistance = $this->calculateCashAssistance($request->farmer);

                $status = $calculateAssistance['status'];
                $amount = $calculateAssistance['amount'];
                $remarks = $calculateAssistance['purpose'];
            }

            // prompts if cash assistance is selected and is not a farmer
            if ($checkAssistance && $request->livelihood != 'farmer' && str_contains(strtolower($checkAssistance), 'cash')) {
                $name = $this->getFullname($request->farmer);
                $activityLogger->log(
                    userId: auth()->id(),
                    table: 'Assistances',
                    message: "User faied to add assistance for `{$name}` because the selected livelihood is not eligible for cash assistance.",
                    action: 'create',
                    status: $state ? 'success' : 'error'
                );
                

                return redirect()->back()->with('response', [
                    'state' => $state,
                    'message' => 'Selected livelihood is not eligible for cash assistance.',
                    'livelihood' => $request->livelihood,
                    'applied_assistance' => $checkAssistance
                ]);
            }

            if ($request->livelihood == 'farm_worker') {
                $name = $this->getFullname($request->farmer);
                $activityLogger->log(
                    userId: auth()->id(),
                    table: 'Assistances',
                    message: "User faied to add assistance for `{$name}` because the selected livelihood is not eligible for any assistances.",
                    action: 'create',
                    status: $state ? 'success' : 'error'
                );
                
                return redirect()->back()->with('response', [
                    'state' => $state,
                    'message' => 'Selected livelihood is not eligible for any assistances.',
                    'livelihood' => $request->livelihood,
                    'applied_assistance' => $checkAssistance,
                    'is_farm_worker' => 1
                ]);
            }
            
            $created = Assistances::create([
                'farmer_id' => $request->farmer,
                'assistance_id' => $request->assistance,
                'reference_no' => $reference,
                'purpose' => trim($request->remarks),
                'status' => $status,
                'amount' => $amount,
                'remarks' => $remarks,
                'livelihood' => $request->livelihood,
                'created_by'=>$user_id,
                'uuid'=>Str::random(12)
            ]);

            if ($created) {
                $id = $created->id;
                $state = true;

                if ($request->file('attachments') !== null) {
                    $attachments = (object) $request->file('attachments');

                    foreach($attachments as $attachment) {
                        $_filename = $attachment->getClientOriginalName();
                        $_destinationPath = "uploads/assistances/assistance_".$id;

                        if(!file_exists(public_path($_destinationPath))){ 
                            File::makeDirectory(public_path($_destinationPath), 0777, true);
                        }

                        $_tempFilePath = $_destinationPath."/".$_filename;

                        if(file_exists(public_path($_tempFilePath))){
                            unlink(public_path($_tempFilePath));
                        }

                        if(!file_exists(public_path($_tempFilePath))){
                            $_fileMoved = $attachment->move($_destinationPath, $_filename);

                            if($_fileMoved) {
                                Attachments::create([
                                    'assistance_id' => $id,
                                    'filename' => $_filename,
                                    'filepath' => $_destinationPath,
                                    'uuid' => Str::random(12)
                                ]);
                            }
                        }
                    }
                }
            }
        }

        $name = $this->getFullname($request->farmer);
        $activityLogger->log(
            userId: auth()->id(),
            table: 'Assistances',
            message: $state ? "User added assistance for `{$name}` with reference number `{$reference}` successfully." : "User failed to add assistance for `{$name}`.",
            action: 'create',
            status: $state ? 'success' : 'error'
        );

        return redirect()->back()->with('response', [
            'state' => $state
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Assistances $assistances)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Assistances $assistances)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id, Assistances $assistances, ActivityLogger $activityLogger) {
        $state = false;

        $changeMessages = [];
        $toUpdate = Assistances::where('id', $id)->first();

        $original = $toUpdate->getOriginal();
        $beforeFiles = Attachments::where('assistance_id', $id)
            ->pluck('filename')
            ->map(fn ($x) => strtolower(trim($x)))
            ->sort()
            ->values()
            ->toArray();

        $toUpdate->farmer_id = $request->farmer;
        $toUpdate->assistance_id = $request->assistance;
        $toUpdate->livelihood = $request->livelihood;
        $toUpdate->purpose = trim($request->remarks);
        $toUpdate->updated_by = $request->user_id;

        $dirty = $toUpdate->getDirty();
        unset($dirty['updated_by']);

        $norm = fn($v) => strtolower(trim((string)($v ?? '')));
        $disp = fn($v) => ($v === null || $v === '') ? '(empty)' : $v;

        $farmerNameById = function ($farmerId) {
            if (!$farmerId) return '(empty)';
            $farmer = DB::table('farmer_information')->where('id', $farmerId)->first();
            if (!$farmer) return (string)$farmerId;

            $mid = (!empty($farmer->middlename)) ? (mb_substr($farmer->middlename, 0, 1) . '. ') : '';
            $suffix = (!empty($farmer->suffix)) ? (' ' . $farmer->suffix) : '';
            return trim($farmer->firstname . ' ' . $mid . $farmer->lastname . $suffix);
        };

        $assistanceTextById = function ($assistanceId) {
            if (!$assistanceId) return '(empty)';
            $a = DB::table('assistance')->where('id', $assistanceId)->first();
            return $a?->name ?? (string)$assistanceId;
        };

        foreach ($dirty as $field => $newValue) {
            $oldValue = $original[$field] ?? null;

            if ($norm($oldValue) === '' && $norm($newValue) === '') continue;
            if ($norm($oldValue) === $norm($newValue)) continue;

            $label = ucfirst(str_replace('_', ' ', $field));

            if ($field === 'farmer_id') {
                $oldDisp = $farmerNameById($oldValue);
                $newDisp = $farmerNameById($newValue);
                if ($norm($oldDisp) !== $norm($newDisp)) {
                    $changeMessages[] = "Farmer changed from '{$oldDisp}' to '{$newDisp}'";
                }
                continue;
            }

            if ($field === 'assistance_id') {
                $oldDisp = $assistanceTextById($oldValue);
                $newDisp = $assistanceTextById($newValue);
                if ($norm($oldDisp) !== $norm($newDisp)) {
                    $changeMessages[] = "Assistance changed from '{$oldDisp}' to '{$newDisp}'";
                }
                continue;
            }

            $changeMessages[] = "{$label} changed from '{$disp($oldValue)}' to '{$disp($newValue)}'";
        }

        $update = $toUpdate->save();

        if ($update) {
            if ($request->file('attachments') !== null) {
                Attachments::where('assistance_id', $id)->delete();

                $attachments = (object) $request->file('attachments');

                foreach($attachments as $attachment) {
                    $_filename = $attachment->getClientOriginalName();
                    $_destinationPath = "uploads/assistances/assistance_".$id;

                    if(!file_exists(public_path($_destinationPath))){ 
                        File::makeDirectory(public_path($_destinationPath), 0777, true);
                    }

                    $_tempFilePath = $_destinationPath."/".$_filename;

                    if(file_exists(public_path($_tempFilePath))){
                        unlink(public_path($_tempFilePath));
                    }

                    if(!file_exists(public_path($_tempFilePath))){
                        $_fileMoved = $attachment->move($_destinationPath, $_filename);

                        if($_fileMoved) {
                            Attachments::create([
                                'assistance_id' => $id,
                                'filename' => $_filename,
                                'filepath' => $_destinationPath,
                                'uuid' => Str::random(12)
                            ]);
                        }
                    }
                }

                $afterFiles = Attachments::where('assistance_id', $id)
                    ->pluck('filename')
                    ->map(fn ($x) => strtolower(trim($x)))
                    ->sort()
                    ->values()
                    ->toArray();

                if ($beforeFiles !== $afterFiles) {
                    $added = array_values(array_diff($afterFiles, $beforeFiles));
                    $removed = array_values(array_diff($beforeFiles, $afterFiles));

                    $fileParts = [];
                    if (!empty($added))   $fileParts[] = "Added: " . implode(', ', $added);
                    if (!empty($removed)) $fileParts[] = "Removed: " . implode(', ', $removed);

                    $changeMessages[] =
                        "Attachments changed from '".count($beforeFiles)."' to '".count($afterFiles)."'"
                        . (!empty($fileParts) ? " (" . implode('; ', $fileParts) . ")" : '');
                }

                $state = true;
            }
        }

        if (!empty($changeMessages)) {

            $message = $state
                ? "User updated assistance successfully. Changes: " . implode('; ', $changeMessages)
                : "Failed to update assistance. Attempted changes: " . implode('; ', $changeMessages);

            $activityLogger->log(
                userId: auth()->id(),
                table: 'Assistances',
                message: $message,
                action: 'update',
                status: $state ? 'success' : 'error'
            );
        }

        return redirect()->back()->with([
            'response' => [
                'state' => $state
            ]
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Assistances $assistances)
    {
        //
    }

    public function get_all_farmers() {
        $farmer = FarmerInformation::
            select(DB::raw("a.id, UPPER(CONCAT(
                a.firstname, ' ',
                IF(a.middlename IS NOT NULL AND a.middlename != '', CONCAT(LEFT(a.middlename, 1), '. '), ''),
                a.lastname,
                IF(a.suffix IS NOT NULL AND a.suffix != '', CONCAT(' ', a.suffix), '')
            )) AS text, b.id as farmer_profile_id, b.main_livelihood"))
            ->leftJoin('farm_profile as b', 'b.farmer_id', '=', 'a.id')
            ->where('is_archived', 0)
            ->from('farmer_information as a')
            ->get();
        $farmerCollection = collect($farmer);

        foreach ($farmerCollection as $farmer) {
            $meta = @unserialize($farmer->main_livelihood);

            if (is_array($meta) && $meta) {
                $main_livelihood = array(
                    'livelihood' => $meta,
                    'contents' => $this->get_main_livelihood_content($farmer->farmer_profile_id, $meta)
                );

                $farmer->main_livelihood = $main_livelihood;
            }
        }

        return $farmerCollection;
    }

    public function get_main_livelihood_content($id, $type){
        $content = MainLivelihood::select(DB::raw('meta, value'))
            ->where('farmer_profile_id', $id)
            ->where('main_livelihood', $type)
            ->get();
        
        $contentCollection = collect($content);

        foreach ($contentCollection as $content) {
            $type = FarmingType::select('name')->where("id", $content->value)->first();
            $content->value = $type->name ?? $content->value;
        }

        return $contentCollection;
    }

    public function get_all_assistance() {
        $allassistance = Assistance::select(DB::raw('livelihoods, id, UPPER(name) as text'))->get();
        $allassistanceCollection = collect($allassistance);

        foreach($allassistanceCollection as $key => $rs) {
            $meta = @unserialize($rs->livelihoods) ?? array();
            $rs->livelihoods = $meta;
        }

        return $allassistanceCollection;
    }

    function generateReferenceNo() {
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

    public function archive_assistance(Request $request, $id, Assistances $assistances, ActivityLogger $activityLogger) {
        $state = false;

        if ($id) {
            $toArchive = Assistances::where('id',$id)->first();
            $toArchive->is_archived = 1;
            $toArchive->archived_by = $request->id;
            $toArchive->archived_at = date('Y-m-d H:i:s');
            $state = $toArchive->save();

            $name = $this->getFullname($toArchive->farmer_id);
            $activityLogger->log(
                userId: auth()->id(),
                table: 'Assistances',
                message: $state ? "User archived assistance for `$name` successfully." : "User failed to archive assistance for `$name`.",
                action: 'delete',
                status: $state ? 'success' : 'error'
            );
        }

        return back()->with('response', [
            'state' => $state,
        ]);
    }

    public function update_status(Request $request, $id, Assistances $assistances, ActivityLogger $activityLogger) {
        $request->validate([
            'status' => 'required|in:pending,approved,disapproved,cancelled',
            'remarks' => 'required|string'
        ]);

        $assistances = Assistances::where('id', $id)->first();
        $oldStatus = $assistances->status;
        $oldRemarks = null;

        $assistances->status = ucfirst($request->status);
        
        $_tempStatus = strtolower($request->status);
        $_remarks = trim($request->remarks);
        $_date = date('Y-m-d H:i:s');
        $userId = auth()->id();
        
        if ($_tempStatus == 'approved') {
            $oldRemarks = $assistances->approved_remarks;
            $assistances->approved_by = $userId;
            $assistances->approved_remarks = $_remarks;
            $assistances->approved_at = $_date;

            if ($assistances->livelihood == 'farmer') {
                $calculatedAmount = $this->calculateAssistance($id);

                $assistances->amount = $calculatedAmount['metric']['amount'] ?? 0;
                $assistances->remarks = $calculatedAmount['metric']['purpose'] ?? 'no purpose specified';
            }

            if ($assistances->livelihood == 'fisherfolks') {
                $calculateHectares = $this->calculateHectares($id);

                if (floatval($calculateHectares) <= 0) {
                    $name = $this->getFullname($id);
                    $activityLogger->log(
                        userId: auth()->id(),
                        table: 'Assistance',
                        message: "Failed to approve assistance of `{$name}`. Applicant current total area is `{$calculateHectares}` Hectares.",
                        action: 'update',
                        status: 'error'
                    );

                    return redirect()->back()->with([
                        'response' => [
                            'state' => 'false',
                            'message' => "Applicant current total area is `{$calculateHectares}` Hectares. Unable to proceed approval of assistance.",
                            'livelihood' => 'fisherfolks',
                            'status' => 'approved'
                        ]
                    ]);
                }
                $getAssistanceName = 'Fingerlings';
                if ($assistances->assistance && !$assistances->assistance->is_archived) {
                    $getAssistanceName = $assistances->assistance->name;
                }
                $assistances->amount = 5000;
                $assistances->remarks = 'Amount of 5000 '.$getAssistanceName;
            }
        }

        if ($_tempStatus == 'disapproved') {
            $oldRemarks = $assistances->disapproved_remarks;
            $assistances->disapproved_by = $userId;
            $assistances->disapproved_remarks = $_remarks;
            $assistances->disapproved_at = $_date;
        }

        if ($_tempStatus == 'cancelled') {
            $oldRemarks = $assistances->cancelled_remarks;
            $assistances->cancelled_by = $userId;
            $assistances->cancelled_remarks = $_remarks;
            $assistances->cancelled_at = $_date;
        }

        if ($_tempStatus == 'pending') {
            $assistances->approved_by = 0;
            $assistances->approved_remarks = null;
            $assistances->approved_at = null;
            $assistances->disapproved_by = 0;
            $assistances->disapproved_remarks = null;
            $assistances->disapproved_at = null;
            $assistances->cancelled_by = 0;
            $assistances->cancelled_remarks = null;
            $assistances->cancelled_at = null;
            $assistances->amount = 0;
            $assistances->remarks = null;
        }

        $state = $assistances->save();

        $changeMessages = [];

        $newStatus = $assistances->status;
        $newRemarks = $_remarks;

        $norm = fn($v) => strtolower(trim((string)($v ?? '')));

        if ($norm($oldStatus) !== $norm($newStatus)) {
            $changeMessages[] = "Status changed from '{$oldStatus}' to '{$newStatus}'";
        }

        if (!empty($changeMessages)) {

            $message = $state
                ? "User updated assistance status successfully. Changes: " . implode('; ', $changeMessages) .' for assistance with reference number `'.$assistances->reference_no.'` with remarks `'.$newRemarks.'`.'
                : "Failed to update assistance status.";

            $activityLogger->log(
                userId: auth()->id(),
                table: 'Assistance',
                message: $message,
                action: 'update',
                status: $state ? 'success' : 'error'
            );
        }

        return redirect()->back()->with([
            'response' => [
                'state' => $state
            ]
        ]);
    }

    function getFullname ($value) {
        if (is_numeric($value)) {
            $type = FarmerInformation::select(DB::raw("UPPER(CONCAT(firstname, ' ', IF(middlename IS NOT NULL AND middlename != '', CONCAT(LEFT(middlename, 1), '. '), ''), lastname, IF(suffix IS NOT NULL AND suffix != '', CONCAT(' ', suffix), ''))) AS name"))->where('id', $value)->first();
            return $type ? $type->name : $value;
        }
        return $value;
    }

    public function calculateAssistance($id) {
        $assistances = Assistances::where('id', $id)->first();

        $getFarmParcelTotalHectares = function ($id) {
            $total = DB::table('assistances as a')
                ->leftJoin('farm_profile as b', 'b.farmer_id', '=', 'a.farmer_id')
                ->leftJoin('farm_parcels as c', 'c.farmer_profile_id', '=', 'b.id')
                ->where('a.id', $id)
                ->sum('c.total_farm_area');

            return $total;
        };

        $totalHectares = $getFarmParcelTotalHectares($id);

        $metric = [];

        if ($assistances && $assistances->assistance && !$assistances->assistance->is_archived) {
            $name = strtolower($assistances->assistance->name);
            if (str_contains($name, 'seed')) {
                $metric = $this->seedMetric($totalHectares);
            }
            elseif (str_contains($name, 'fertilizer')) {
                $metric = $this->fertilizerMetric($totalHectares);
            }
        }

        return array(
            'total_hectares' => $totalHectares,
            'metric' => $metric
        );
    }

    private function seedMetric($hectares) {
        $hectares = (float) $hectares;

        if ($hectares < 0.10) {
            return [
                'amount' => 0,
                'purpose' => '0 bag(s)'
            ];
        }
        
        //commented for now to accomodate more than 10 hectares
        // $hectares = min($hectares, 10);

        $bags = (int) ceil($hectares / 0.50);

        return [
            'amount' => $bags,
            'purpose' => "{$bags} bag(s) with total area of {$hectares} hectares"
        ];
    }

    private function fertilizerMetric($hectares) {
        $hectares = (float) $hectares;

        if ($hectares < 0.10) {
            return [
                'amount' => 0,
                'purpose' => 'Urea 0 kg, Potash 0 kg'
            ];
        }

        //commented for now to accomodate more than 10 hectares
        // $hectares = min($hectares, 10);

        $step = (int) ceil($hectares / 0.20);

        $urea = $step * 10;
        $potash = $step * 10;

        return [
            'amount' => $urea + $potash,
            'purpose' => "Urea {$urea} kg, Potash {$potash} kg with total area of {$hectares} hectares"
        ];
    }

    public function calculateCashAssistance($id) {
        $getFarmParcelTotalHectares = function ($id) {
            $total = DB::table('farm_profile as a')
                ->leftJoin('farm_parcels as b', 'b.farmer_profile_id', '=', 'a.id')
                ->where('a.farmer_id', $id)
                ->sum('b.total_farm_area');

            return $total;
        };

        $totalHectares = $getFarmParcelTotalHectares($id);
        $metric = [];

        if (floatval($totalHectares) <= 0.10 || floatval($totalHectares) > 2.01) {
            $metric = [
                'amount' => 0,
                'status' => 'Disapproved',
                'purpose' => "Cash assistance is only applicable for total area between 0.10 and 2 hectares. Current total area: `{$totalHectares}` hectares"
            ];
        } else {
            $metric = [
                'amount' => 7000, //flat 7000 based on the metrics that 0.10 to 2 hectares is eligible for cash assistance, this can be adjusted as needed
                'status' => 'Approved',
                'purpose' => "Cash assistance for total area of `{$totalHectares}` hectares"
            ];
        }

        return $metric;
    }

    public function calculateHectares($id) {
        $getFarmParcelTotalHectares = function ($id) {
            $total = DB::table('farm_profile as a')
                ->leftJoin('farm_parcels as b', 'b.farmer_profile_id', '=', 'a.id')
                ->where('a.farmer_id', $id)
                ->sum('b.total_farm_area');

            return $total;
        };

        $totalHectares = $getFarmParcelTotalHectares($id);

        return $totalHectares;
    }
}
