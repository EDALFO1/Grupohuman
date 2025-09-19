@extends('layouts.main')

@section('titulo', $titulo)

@section('contenido')
<main id="main" class="main">
  <div class="pagetitle"><h1>{{ $titulo }}</h1></div>

  <div class="card">
    <div class="card-body">
      <form class="mt-3" method="post" action="{{ route('incapacidades.update', $incapacidad) }}">
        @csrf @method('PUT')
        @include('incapacidades.form', [
          'mode' => 'edit',
          'incapacidad' => $incapacidad,
          'estados' => $estados,
          'entidadTipos' => $entidadTipos,
          'epsList' => $epsList,
          'arlList' => $arlList,
        ])
      </form>

      <hr>
      <h5>Observaciones</h5>
      <ul id="lista-observaciones">
        @foreach($incapacidad->observaciones as $obs)
          <li><small>{{ $obs->created_at->format('Y-m-d H:i') }}</small> — {{ $obs->nota }}</li>
        @endforeach
      </ul>
      <div class="input-group my-2">
        <input type="text" id="nueva_observacion_text" class="form-control" placeholder="Agregar observación...">
        <button type="button" id="btnAgregarObs" class="btn btn-outline-primary">Agregar</button>
      </div>
    </div>
  </div>
</main>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
document.getElementById('btnAgregarObs')?.addEventListener('click', async () => {
  const txt = document.getElementById('nueva_observacion_text');
  const nota = (txt.value || '').trim();
  if(!nota) return;

  try {
    const url = "{{ route('incapacidades.observaciones.agregar', $incapacidad) }}";
    const { data } = await axios.post(url, { nota });
    if(data.ok){
      const li = document.createElement('li');
      li.innerHTML = `<small>${data.observacion.created_at}</small> — ${data.observacion.nota}`;
      document.getElementById('lista-observaciones').prepend(li);
      txt.value = '';
    }
  } catch (e) { alert('Error guardando observación'); }
});
</script>
@endpush
@endsection
