<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Release;

class ReleasesController extends Controller
{
    public function index(Request $request)
    {

        $releases = Release::paginate(15);
        return view('releases.index', compact('releases'));
    }

    public function create(Request $request)
    {
        return view('releases.create');
    }

    public function view(Request $request)
    {
        # code...
    }

    public function post(Request $request)
    {
        # code...
    }

    public function put(Request $request)
    {
        # code...
    }

    public function delete(Request $request)
    {
        # code...
    }
}
