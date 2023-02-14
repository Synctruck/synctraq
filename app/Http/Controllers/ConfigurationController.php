<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Configuration;

class ConfigurationController extends Controller
{
    public function index()
    {
        return view('configuration.index');
    }

    public function UpdateTokenXcelerator($response)
    {
        $configuration = Configuration::first();

        $configuration->access_token_Xcelerator = $response->access_token;

        $configuration->save();
    }
}