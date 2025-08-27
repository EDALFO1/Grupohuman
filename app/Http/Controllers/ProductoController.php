<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    public function index()
    {
        $titulo = 'Listado de Productos';
        $productos = Producto::all();
        return view('productos.index', compact('titulo', 'productos'));
    }

    public function create()
    {
        $titulo = 'Crear Producto';
        return view('productos.create', compact('titulo'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'codigo' => 'required|unique:productos',
            'nombre' => 'required|string',
            'precio_unitario' => 'required|numeric',
            'iva' => 'nullable|numeric',
            'descripcion' => 'nullable|string',
        ]);

        Producto::create($request->all());

        return redirect()->route('productos')->with('success', 'Producto creado correctamente.');
    }

    public function edit(Producto $producto)
    {
        $titulo = 'Editar Producto';
        return view('productos.edit', compact('titulo', 'producto'));
    }

    public function update(Request $request, Producto $producto)
    {
        $request->validate([
            'codigo' => 'required|unique:productos,codigo,' . $producto->id,
            'nombre' => 'required|string',
            'precio_unitario' => 'required|numeric',
            'iva' => 'nullable|numeric',
            'descripcion' => 'nullable|string',
        ]);

        $producto->update($request->all());

        return redirect()->route('productos')->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(Producto $producto)
    {
        $producto->delete();
        return redirect()->route('productos')->with('success', 'Producto eliminado.');
    }
}
