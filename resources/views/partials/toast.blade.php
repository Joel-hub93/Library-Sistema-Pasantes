{{-- Toast de SweetAlert2: aviso breve en la esquina superior derecha --}}
<script>
    mostrarToast({{ \Illuminate\Support\Js::from($icono) }}, {{ \Illuminate\Support\Js::from($titulo) }}, {{ \Illuminate\Support\Js::from($mensaje) }});
</script>