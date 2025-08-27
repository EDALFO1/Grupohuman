<form action="{{ $asesor->exists ? route('asesores.update', $asesor) : route('asesores.store') }}" method="POST">
    @csrf
    @if($asesor->exists)
        @method('PUT')
    @endif

    <div class="mb-3">
        <label for="documento_id">Tipo de Documento</label>
        <select name="documento_id" class="form-control" required>
            <option value="">-- Seleccionar --</option>
            @foreach($documentos as $doc)
                <option value="{{ $doc->id }}" {{ $asesor->documento_id == $doc->id ? 'selected' : '' }}>{{ $doc->nombre }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label for="numero_documento">Número de Documento</label>
        <input type="text" name="numero_documento" value="{{ old('numero_documento', $asesor->numero_documento) }}" class="form-control" required>
    </div>

    <div class="mb-3">
        <label for="nombre">Nombre</label>
        <input type="text" name="nombre" value="{{ old('nombre', $asesor->nombre) }}" class="form-control" required>
    </div>

    <div class="mb-3">
        <label for="direccion">Dirección</label>
        <input type="text" name="direccion" value="{{ old('direccion', $asesor->direccion) }}" class="form-control" required>
    </div>

    <div class="mb-3">
        <label for="telefono">Teléfono</label>
        <input type="text" name="telefono" value="{{ old('telefono', $asesor->telefono) }}" class="form-control" required>
    </div>

    <div class="mb-3">
        <label for="email">Email</label>
        <input type="email" name="email" value="{{ old('email', $asesor->email) }}" class="form-control">
    </div>
    <button type="submit" class="btn btn-success">{{ $asesor->exists ? 'Actualizar' : 'Guardar' }}</button>
    <a href="{{ route('asesores') }}" class="btn btn-secondary">Cancelar</a>
   
</form>
