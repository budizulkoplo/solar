<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\UnitDetail;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UnitDetailController extends Controller
{
    public function index(Request $request)
    {
        $projects = Project::with(['units' => function($query) use ($request) {
                if ($request->has('unit_id')) {
                    $query->where('id', $request->unit_id);
                }
                $query->with(['details', 'jenisUnit']);
            }])
            ->whereHas('units')
            ->get();
        
        // Get filter data
        $units = Unit::when($request->has('project_id') && $request->project_id != '', function($query) use ($request) {
                return $query->where('idproject', $request->project_id);
            })
            ->orderBy('namaunit')
            ->get();
        
        $selectedUnit = $request->has('unit_id') ? Unit::find($request->unit_id) : null;
        
        return view('master.units.details', compact('projects', 'units', 'selectedUnit'));
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'status' => 'required|in:tersedia,booking,terjual'
            ]);
            
            $unitDetail = UnitDetail::findOrFail($id);
            $oldStatus = $unitDetail->status;
            $unitDetail->update(['status' => $validated['status']]);
            
            return response()->json([
                'success' => true,
                'message' => 'Status berhasil diubah dari ' . $oldStatus . ' menjadi ' . $validated['status']
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getStatistics()
    {
        $totalUnits = UnitDetail::count();
        $tersedia = UnitDetail::where('status', 'tersedia')->count();
        $booking = UnitDetail::where('status', 'booking')->count();
        $terjual = UnitDetail::where('status', 'terjual')->count();
        
        return response()->json([
            'total' => $totalUnits,
            'tersedia' => $tersedia,
            'booking' => $booking,
            'terjual' => $terjual,
            'tersedia_percent' => $totalUnits > 0 ? round(($tersedia / $totalUnits) * 100, 1) : 0,
            'booking_percent' => $totalUnits > 0 ? round(($booking / $totalUnits) * 100, 1) : 0,
            'terjual_percent' => $totalUnits > 0 ? round(($terjual / $totalUnits) * 100, 1) : 0,
        ]);
    }
}