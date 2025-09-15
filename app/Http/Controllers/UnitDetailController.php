<?php

namespace App\Http\Controllers;

use App\Models\Project;

class UnitDetailController extends Controller
{
    public function index()
    {
        // Ambil semua project dengan relasi units dan unit_details
        $projects = Project::with(['units.details'])->get();

        return view('master.unitdetails.list', compact('projects'));
    }
}
