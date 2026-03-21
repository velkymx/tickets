<?php

namespace App\Http\Controllers;

use App\Models\Milestone;
use App\Services\Importer;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ImportController extends Controller
{

    public function index(Request $request)
    {
        $milestones = Milestone::orderBy('name')->where('end_at', null)->pluck('name', 'id');

        return view('import.index', compact('milestones'));
    }

    public function create(Request $request)
    {
        DB::beginTransaction();
        try {
            (new Importer)->call(
                (int) $request->milestone_id,
                $request->csv->path(),
                (bool) $request->hasHeader,
            );
        } catch (Exception $e) {
            DB::rollBack();
            $errors = [
                $e->getMessage(),
            ];

            return redirect('/tickets/import')->withErrors($errors)->withInput();
        }
        DB::commit();

        return redirect('/milestone/show/'.$request->milestone_id)
            ->with('info_message', 'Tickets Successfully Imported');
    }
}
