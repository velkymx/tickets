<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Symfony\Component\Yaml\Yaml;

class ApiDocsController extends Controller
{
    public function index()
    {
        $specPath = storage_path('api/openapi.yaml');
        $spec = File::exists($specPath) ? Yaml::parseFile($specPath) : [];

        return view('api.docs', ['spec' => $spec]);
    }

    public function spec()
    {
        $specPath = storage_path('api/openapi.yaml');

        if (! File::exists($specPath)) {
            abort(404);
        }

        return response()->file($specPath, [
            'Content-Type' => 'text/yaml',
        ]);
    }
}
