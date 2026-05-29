<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Rol;
use App\Models\EstadoUsuario;
use App\Models\SubdivisionArea;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        $roles = Rol::all();
        $estados = EstadoUsuario::all();
        $subdivisiones = SubdivisionArea::where('estado_subdiv_area', 1)->get();
        $query = User::with(['rol', 'estado', 'subdivision']);

        if ($request->filled('buscar')) {
            $termino = $request->buscar;
            $query->where(function($q) use ($termino) {
                $q->where('nombre', 'LIKE', '%' . $termino . '%')
                ->orWhere('apellido', 'LIKE', '%' . $termino . '%')
                ->orWhere(DB::raw("CONCAT(nombre, ' ', apellido)"), 'LIKE', '%' . $termino . '%');
            });
        }

        if ($request->filled('id_estado')) {
            $query->where('estado_usuario_id', $request->id_estado);
        }
        if ($request->filled('id_subdivision')) {
            $query->where('subdivision_id', $request->id_subdivision);
        }
        if ($request->filled('id_rol')) {
            $query->where('rol_id', $request->id_rol);
        }

        $perPage = ($request->has('mostrar_filtros') || $request->filled('filtrado')) ? 5 : 5;
        $usuarios = $query->orderBy('apellido', 'asc')->paginate($perPage)->withQueryString();

        return view('usuarios.index', compact('usuarios', 'roles', 'estados', 'subdivisiones'));
    }

    public function show($id)
    {
        $usuario = User::with(['rol', 'subdivision', 'estado'])->findOrFail($id);
        return view('usuarios.detalles_usuario', compact('usuario'));
    }

    public function edit($id)
    {
        $usuario = User::findOrFail($id);
        $roles = Rol::all();
        $estados = EstadoUsuario::all();
        $subdivisiones = SubdivisionArea::where('estado_subdiv_area', 1)
        ->orWhere('id_subdivision', $usuario->subdivision_id)
        ->orderBy('nombre_subdivision', 'asc')
        ->get();

        return view('usuarios.edit', compact('usuario', 'roles', 'estados', 'subdivisiones'));
    }

    public function update(Request $request, $id)
    {
        // CORRECCIÓN: Estaba buscando el ID del usuario logueado en lugar del ID pasado por parámetro
        $usuario = User::findOrFail($id); 
        
        if (auth()->user()->rol_id != 1) {
            abort(403, 'NO TIENES PERMISOS PARA ESTA ACCIÓN');
        }

        $request->validate([
            'nombre' => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email,' . $id,
            'celular' => 'nullable|string|max:20',
            'subdivision_id' => 'required|exists:subdivision_areas,id_subdivision',
            'rol_id' => 'required|exists:roles,id_rol',
            'estado_usuario_id' => 'required|exists:estado_usuarios,id_estado_usuario',
        ], [
            // MENSAJES PERSONALIZADOS EN ESPAÑOL
            'email.unique' => 'EL CORREO ELECTRÓNICO YA ESTÁ REGISTRADO EN EL SISTEMA.',
            'email.email' => 'EL FORMATO DEL CORREO NO ES VÁLIDO.',
            'email.required' => 'EL CORREO ELECTRÓNICO ES OBLIGATORIO.',
        ]);

        // Impedir que un admin se desactive a sí mismo o se quite el rol de admin
        if ($usuario->id == auth()->id()) {
            if ($request->estado_usuario_id != 1) return back()->withErrors(['estado_usuario_id' => 'NO PUEDES DESACTIVAR TU PROPIO USUARIO']);
            if ($request->rol_id != 1) return back()->withErrors(['rol_id' => 'NO PUEDES CAMBIAR TU PROPIO ROL']);
        }

        $usuario->update([
            'nombre' => mb_strtoupper($request->nombre),
            'apellido' => mb_strtoupper($request->apellido),
            'email' => $request->email,
            'celular' => $request->celular,
            'subdivision_id' => $request->subdivision_id,
            'rol_id' => $request->rol_id,
            'estado_usuario_id' => $request->estado_usuario_id,
        ]);

        return redirect()->route('usuarios.show', $usuario->id)
                        ->with('success', 'USUARIO ACTUALIZADO CORRECTAMENTE');
    }

    public function create()
    {
        if (auth()->user()->rol_id != 1) {
            abort(403);
        }

        $roles = Rol::all();
        $subdivisiones = SubdivisionArea::where('estado_subdiv_area', 1)->get();
        return view('usuarios.nuevo_usuario', compact('roles', 'subdivisiones'));
    }

    // Función para generar username único
    private function generarUsernameUnico($nombre, $apellido)
    {
        $palabras = explode(' ', $nombre . ' ' . $apellido);
        $username = '';

        foreach ($palabras as $palabra) {
            if (strlen($palabra) > 0) {
                $cantidad = rand(1, min(3, strlen($palabra)));
                $parte = substr($palabra, 0, $cantidad);
                $parte = rand(0,1) ? strtolower($parte) : ucfirst(strtolower($parte));
                $username .= $parte;
            }
        }

        $username .= rand(1, 99); // número al final
        $base = $username;

        while(User::where('username', $username)->exists()) {
            $username = $base . rand(1, 9);
        }

        return $username;
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'username' => 'required|unique:users,username', // Criterio: Validar username único
            'email' => 'required|email|unique:users,email', // Criterio 3: Correo electrónico único
            'celular' => 'nullable|string|max:15',
            'rol_id' => 'required|exists:roles,id_rol',
            'subdivision_id' => 'required|exists:subdivision_areas,id_subdivision', 
        ], [
            'email.unique' => 'EL CORREO ELECTRÓNICO YA ESTÁ REGISTRADO EN EL SISTEMA.',
            'username.unique' => 'EL NOMBRE DE USUARIO GENERADO YA EXISTE. INTENTE ESCRIBIR EL NOMBRE NUEVAMENTE PARA GENERAR OTRO.',
            'email.email' => 'EL FORMATO DEL CORREO NO ES VÁLIDO.',
        ]);

        // Criterio 2: La contraseña inicial por defecto debe ser 123456
        $password_temporal = '123456';

        // Crear usuario usando el username enviado desde la vista (Criterio 1 - Vinculación)
        $nuevoUsuario = User::create([
            'username' => $request->username, 
            'nombre' => mb_strtoupper($request->nombre),
            'apellido' => mb_strtoupper($request->apellido),
            'email' => $request->email,
            'celular' => $request->celular,
            'rol_id' => $request->rol_id,
            'subdivision_id' => $request->subdivision_id,
            'estado_usuario_id' => 1, 
            'password' => Hash::make($password_temporal),
            'primer_login' => true,
        ]);

        return redirect()->route('usuarios.index')
        ->with('success', 'EL USUARIO HA SIDO REGISTRADO EN EL SISTEMA.')
        ->with('usuario_creado', [
            'username' => $nuevoUsuario->username, // Coincidencia exacta
            'password' => $password_temporal
        ]);
    }


    public function perfil()
    {
        $usuario = auth()->user()->load(['rol', 'subdivision', 'estado']);
        return view('perfil.index_perfil', compact('usuario'));
    }

    public function updatePerfil(Request $request)
    {
        $user = Auth::user(); // Obtenemos la instancia del usuario autenticado

        $request->validate([
            'nombre'   => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'email'    => [
                'required',
                'email',
                // Importante: Ignorar el ID del usuario actual para que permita guardar el mismo correo
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'celular'  => 'nullable|string|max:9', // Validar máximo 9 en servidor
        ], [
            'email.unique' => 'EL CORREO ELECTRÓNICO YA ESTÁ REGISTRADO EN EL SISTEMA.',
            'email.email'  => 'EL FORMATO DEL CORREO NO ES VÁLIDO.',
            'celular.max'  => 'EL TELÉFONO NO PUEDE TENER MÁS DE 9 DÍGITOS.',
        ]);

        // Actualizamos los campos
        $user->nombre   = mb_strtoupper(trim($request->nombre));
        $user->apellido = mb_strtoupper(trim($request->apellido));
        $user->email    = strtolower(trim($request->email)); // Email siempre en minúsculas
        $user->celular  = trim($request->celular);

        if ($user->save()) {
            return redirect()->route('perfil')
                ->with('success', 'PERFIL ACTUALIZADO CORRECTAMENTE')
                ->with('perfil_actualizado', true);
        }

        return back()->with('error', 'NO SE PUDIERON GUARDAR LOS CAMBIOS.');
    }
    public function updatePassword(Request $request)
    {
        $request->validate([
            'password' => [
                'required',
                'confirmed',
                Password::min(6)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
        ], [
            'password.confirmed' => 'LAS CONTRASEÑAS NO COINCIDEN.',
            'password' => 'LA CONTRASEÑA NO CUMPLE CON LOS REQUISITOS DE SEGURIDAD.'
        ]);

        $user = auth()->user();
        $user->password = Hash::make($request->password);
        $user->primer_login = false;
        $user->save();

        Auth::login($user);

        return redirect()->route('perfil')
            ->with('perfil_actualizado', true)
            ->with('password_actualizado', true);
    }
}