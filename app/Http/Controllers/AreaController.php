<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SubdivisionArea;

class AreaController extends Controller
{
    public function gestionAreas()
    {
        $areas = SubdivisionArea::orderBy('nombre_subdivision', 'asc')->get();
        $codigosExistentes = $areas->pluck('cod_subdivision')->toArray();
    
        return view('usuarios.gestion_areas', compact('areas', 'codigosExistentes'));
    }

    public function storeArea(Request $request)
    {
        $request->validate([
            'cod_subdivision' => 'required|unique:subdivision_areas,cod_subdivision',
            'nombre_subdivision' => 'required|string|max:100|unique:subdivision_areas,nombre_subdivision',
        ], [
            'cod_subdivision.unique' => 'EL CÓDIGO GENERADO YA PERTENECE A OTRA ÁREA.',
            'nombre_subdivision.unique' => 'EL NOMBRE DE ESTA ÁREA YA ESTÁ REGISTRADO.'
        ]);

        SubdivisionArea::create([
            'cod_subdivision' => $request->cod_subdivision,
            'nombre_subdivision' => mb_strtoupper($request->nombre_subdivision),
            'estado_subdiv_area' => 1 
        ]);

        return redirect()->route('usuarios.areas')->with('success', 'ÁREA REGISTRADA CORRECTAMENTE');
    }

    public function updateArea(Request $request, $id)
    {
        $area = SubdivisionArea::findOrFail($id);

        $request->validate([
            'nombre_subdivision' => 'required|string|max:100|unique:subdivision_areas,nombre_subdivision,' . $id . ',id_subdivision',
            'estado_subdiv_area' => 'required|boolean'
        ]);

        $area->update([
            'nombre_subdivision' => mb_strtoupper($request->nombre_subdivision),
            'estado_subdiv_area' => $request->estado_subdiv_area
        ]);

        return redirect()->route('usuarios.areas')->with('success', 'ÁREA ACTUALIZADA CORRECTAMENTE');
    }
}