<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Importer;
use App\Exceptions\ImportException;
use Illuminate\Support\Facades\DB;
class ImportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $milestones = \App\Milestone::orderBy('name')->where('end_at', null)->pluck('name', 'id');
        return view('import.index', compact('milestones'));
    }
    
    public function create(Request $request)
    {
        DB::beginTransaction();
        try {
            (new Importer())->call(
                (int) $request->milestone_id,
                $request->csv->path(),
                (string) $request->hasHeader,
            );
        } catch (ImportException $e) {
            DB::rollBack();
            $errors = [
                $e->getMessage()
            ];
            return redirect('/tickets/import')->withErrors($errors)->withInput();
        }
        DB::commit();
        return redirect('/milestone/show/' . $request->milestone_id)
            ->with("info_message", "Tickets Successfully Imported");
    }
}
