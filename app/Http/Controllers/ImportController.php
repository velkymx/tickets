<?php

namespace App\Http\Controllers;

use App\Models\Milestone;
use App\Services\Importer;
use Exception;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    public function index(Request $request)
    {
        $milestones = Milestone::orderBy('name')->whereNull('end_at')->pluck('name', 'id');

        return view('import.index', compact('milestones'));
    }

    public function create(Request $request)
    {
        $request->validate([
            'milestone_id' => 'required|integer|exists:milestones,id',
            'csv' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $csv = $request->file('csv');
        $maxRows = 1000;

        try {
            $handle = fopen($csv->path(), 'r');
            $rowCount = 0;
            while (($row = fgetcsv($handle)) !== false) {
                $rowCount++;
                if ($rowCount > $maxRows) {
                    fclose($handle);
                    throw new Exception("CSV file exceeds maximum of $maxRows rows.");
                }
            }
            fclose($handle);

            $content = file_get_contents($csv->path());
            if (strlen($content) !== strlen(utf8_encode($content)) && ! mb_check_encoding($content, 'UTF-8')) {
                throw new Exception('CSV file must be UTF-8 encoded.');
            }
        } catch (Exception $e) {
            return redirect('/tickets/import')->withErrors([$e->getMessage()])->withInput();
        }

        try {
            (new Importer)->call(
                (int) $request->milestone_id,
                $csv->path(),
                (bool) $request->hasHeader,
            );
        } catch (Exception $e) {
            return redirect('/tickets/import')->withErrors([$e->getMessage()])->withInput();
        }

        return redirect('/milestone/show/'.$request->milestone_id)
            ->with('info_message', 'Tickets Successfully Imported');
    }
}
