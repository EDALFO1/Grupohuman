<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function index(){
        $titulo = 'Login de usuarios';
        return view('modules.auth.login', compact('titulo'));
    }
    public function logear(Request $request){
        // validar datos de as credenciales
        $credenciales = $request->validate([
            'email' =>'required|email',
            'password' => 'required',
        ]);
        // buscar el email    
        $user = User::where('email', $request->email)->first();
        // validar usuario y contraseña
        if(!$user || !Hash::check($request->password, $user->password)){
            return back()->withErrors(['email' => 'Credenciales incorrectas'])->withInput();
        }
       // el usuario este activo
       if(!$user->activo){
        return back()->withErrors(['email' => 'tu cuenta está inactiva']);
       }
       // crear la session de usuarios
       Auth::login($user);
       $request->session()->regenerate();
       return to_route('home');
    }

     
     public function crearAdmin(){
       User::create([
          'name' => 'Edalfo',
          'email' => 'admin@admin.com',
          'password' => Hash::make('admin'),
          'activo' => true,
          'rol' => 'admin',
       ]);
       return 'Administrador creado con éxito';
     }
     public function logout(Request $request)
{
    Auth::logout(); // Cierra sesión del usuario

    // Invalida toda la sesión actual
    $request->session()->invalidate();

    // Regenera el token CSRF por seguridad
    $request->session()->regenerateToken();

    // Elimina la empresa local seleccionada
    $request->session()->forget('empresa_local_id');

    // Redirige al login
    return redirect()->route('login');
}

}