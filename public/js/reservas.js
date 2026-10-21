'use strict';

(() => {
  const formulario = document.querySelector('#formulario-filtros');
  const tabla = document.querySelector('#tabla-reservas');
  const mensaje = document.querySelector('#mensaje');
  const botonLimpiar = document.querySelector('#limpiar-filtros');
  const estadoReserva = document.querySelector('#estado-reserva');
  const historialEventos = document.querySelector('#historial-eventos');

  const esListado = Boolean(formulario && tabla && botonLimpiar);
  const esDetalle = Boolean(estadoReserva && historialEventos);

  // La plantilla base también carga este archivo en las páginas de error.
  if (!mensaje || (!esListado && !esDetalle)) {
    return;
  }

  let ocupado = false;

  const formatoImporte = new Intl.NumberFormat('es-ES', {
    style: 'currency',
    currency: 'EUR',
  });

  function mostrarMensaje(texto, tipo = 'informacion') {
    mensaje.textContent = texto;
    mensaje.dataset.tipo = tipo;
    mensaje.hidden = false;
  }

  function obtenerMensajeError(error) {
    if (error instanceof TypeError) {
      return 'No se ha podido conectar con el servidor. Comprueba la conexión.';
    }

    return error instanceof Error ? error.message : 'Se ha producido un error inesperado.';
  }

  function establecerCarga(cargando) {
    ocupado = cargando;

    if (formulario) {
      formulario.querySelectorAll('input, select, button').forEach((control) => {
        control.disabled = cargando;
      });
    }

    document.querySelectorAll('.boton-cancelar').forEach((boton) => {
      boton.disabled = cargando;
    });

    if (tabla) {
      tabla.setAttribute('aria-busy', String(cargando));
    }

    if (estadoReserva) {
      estadoReserva.closest('.detalle')?.setAttribute('aria-busy', String(cargando));
    }
  }

  /**
   * Centraliza las peticiones y convierte los errores HTTP
   * en errores que podemos mostrar en la interfaz.
   */
  async function solicitarJson(url, opciones = {}) {
    const respuesta = await fetch(url, {
      ...opciones,
      headers: {
        Accept: 'application/json',
        ...opciones.headers,
      },
    });

    let contenido;

    try {
      contenido = await respuesta.json();
    } catch {
      throw new Error(
        `El servidor ha devuelto una respuesta no válida (HTTP ${respuesta.status}).`
      );
    }

    if (!respuesta.ok) {
      throw new Error(
        contenido?.error || `No se ha podido completar la petición (HTTP ${respuesta.status}).`
      );
    }

    return contenido;
  }

  function obtenerParametros() {
    const parametros = new URLSearchParams();
    const datos = new FormData(formulario);

    // Los filtros vacíos no se envían a la API.
    for (const [campo, valor] of datos.entries()) {
      const texto = String(valor).trim();

      if (texto !== '') {
        parametros.set(campo, texto);
      }
    }

    return parametros;
  }

  function validarRango(parametros) {
    const fechaDesde = parametros.get('fecha_desde');
    const fechaHasta = parametros.get('fecha_hasta');

    if (fechaDesde && fechaHasta && fechaDesde > fechaHasta) {
      throw new Error('La fecha desde no puede ser posterior a la fecha hasta.');
    }
  }

  function formatearFecha(valor) {
    const partes = String(valor ?? '').split('-');

    if (partes.length !== 3) {
      return String(valor ?? '');
    }

    return `${partes[2]}/${partes[1]}/${partes[0]}`;
  }

  function formatearFechaHora(valor) {
    const texto = String(valor ?? '');
    const [fecha, hora] = texto.split(' ');

    return hora ? `${formatearFecha(fecha)} ${hora.slice(0, 5)}` : texto;
  }

  function capitalizar(valor) {
    const texto = String(valor ?? '');

    return texto.charAt(0).toUpperCase() + texto.slice(1);
  }

  function agregarCelda(fila, texto) {
    const celda = document.createElement('td');

    // Los datos de la API se insertan como texto, no como HTML.
    celda.textContent = String(texto ?? '');
    fila.append(celda);

    return celda;
  }

  function crearFila(reserva) {
    const fila = document.createElement('tr');
    fila.dataset.reservaId = String(reserva.id);

    agregarCelda(fila, reserva.nombre_huesped);
    agregarCelda(fila, reserva.alojamiento);
    agregarCelda(fila, formatearFecha(reserva.fecha_entrada));
    agregarCelda(fila, formatearFecha(reserva.fecha_salida));
    agregarCelda(fila, reserva.num_personas);
    agregarCelda(fila, formatoImporte.format(Number(reserva.importe_total)));
    agregarCelda(fila, reserva.estado);

    const celdaAcciones = agregarCelda(fila, '');
    const acciones = document.createElement('div');

    acciones.className = 'acciones-reserva';

    const enlace = document.createElement('a');

    enlace.href = `/reservas/${encodeURIComponent(String(reserva.id))}`;
    enlace.className = 'boton-detalle';
    enlace.textContent = 'Ver detalle';

    acciones.append(enlace);

    if (reserva.estado === 'pendiente') {
      const botonCancelar = document.createElement('button');

      botonCancelar.type = 'button';
      botonCancelar.className = 'boton-cancelar';
      botonCancelar.dataset.id = String(reserva.id);
      botonCancelar.textContent = 'Cancelar';
      botonCancelar.disabled = ocupado;

      acciones.append(botonCancelar);
    }

    celdaAcciones.append(acciones);

    return fila;
  }

  function renderizarReservas(reservas) {
    const fragmento = document.createDocumentFragment();

    if (reservas.length === 0) {
      const fila = document.createElement('tr');
      const celda = agregarCelda(fila, 'No hay reservas que coincidan con los filtros.');

      celda.colSpan = 8;
      fragmento.append(fila);
    } else {
      reservas.forEach((reserva) => {
        fragmento.append(crearFila(reserva));
      });
    }

    tabla.replaceChildren(fragmento);
  }

  /**
   * Consulta el listado sin gestionar mensajes ni bloqueos.
   * Así se puede reutilizar después de una cancelación.
   */
  async function actualizarListado(parametros) {
    const consulta = parametros.toString();
    const url = consulta ? `/api/reservas?${consulta}` : '/api/reservas';

    const contenido = await solicitarJson(url);

    if (!Array.isArray(contenido?.datos)) {
      throw new Error('La respuesta del servidor no contiene un listado válido.');
    }

    renderizarReservas(contenido.datos);

    return contenido.datos.length;
  }

  async function cargarReservas() {
    if (ocupado) {
      return;
    }

    try {
      // Se recogen los valores antes de deshabilitar el formulario.
      const parametros = obtenerParametros();
      validarRango(parametros);

      establecerCarga(true);
      mostrarMensaje('Cargando reservas…');

      const total = await actualizarListado(parametros);

      mostrarMensaje(`Reservas encontradas: ${total}.`, 'exito');
    } catch (error) {
      // Si falla la consulta, se conserva la tabla anterior.
      mostrarMensaje(obtenerMensajeError(error), 'error');
    } finally {
      establecerCarga(false);
    }
  }

  function renderizarHistorial(eventos) {
    const fragmento = document.createDocumentFragment();

    if (eventos.length === 0) {
      const elemento = document.createElement('li');
      elemento.className = 'timeline__evento';

      const texto = document.createElement('p');
      texto.textContent = 'No hay eventos registrados para esta reserva.';

      elemento.append(texto);
      fragmento.append(elemento);
    } else {
      eventos.forEach((evento) => {
        const elemento = document.createElement('li');
        elemento.className = 'timeline__evento';

        const cabecera = document.createElement('div');
        cabecera.className = 'timeline__cabecera';

        const tipo = document.createElement('strong');
        tipo.textContent = capitalizar(evento.tipo_evento);

        const fecha = document.createElement('time');
        fecha.dateTime = String(evento.fecha_evento ?? '').replace(' ', 'T');
        fecha.textContent = formatearFechaHora(evento.fecha_evento);

        const descripcion = document.createElement('p');
        descripcion.textContent = String(evento.descripcion ?? '');

        cabecera.append(tipo, fecha);
        elemento.append(cabecera, descripcion);
        fragmento.append(elemento);
      });
    }

    historialEventos.replaceChildren(fragmento);
  }

  function actualizarDetalle(reserva, eventos, boton) {
    estadoReserva.textContent = capitalizar(reserva.estado);

    estadoReserva.classList.remove('estado--pendiente', 'estado--confirmada', 'estado--cancelada');
    estadoReserva.classList.add(`estado--${reserva.estado}`);

    renderizarHistorial(eventos);

    const acciones = boton.closest('.detalle__acciones');

    if (acciones) {
      acciones.remove();
    } else {
      boton.remove();
    }
  }

  async function cancelarReserva(boton) {
    if (ocupado) {
      return;
    }

    const id = boton.dataset.id;

    if (!id || !/^[1-9]\d*$/.test(id)) {
      mostrarMensaje('El identificador de la reserva no es válido.', 'error');
      return;
    }

    const confirmado = window.confirm(`¿Quieres cancelar la reserva #${id}?`);

    if (!confirmado) {
      return;
    }

    let cancelacionConfirmada = false;
    const textoOriginal = boton.textContent;

    try {
      const token = document.querySelector('meta[name="csrf-token"]')?.content;

      if (!token) {
        throw new Error('No se ha encontrado el token de seguridad. Recarga la página.');
      }

      // Guardamos los filtros antes de deshabilitar los controles.
      const parametros = esListado ? obtenerParametros() : null;

      establecerCarga(true);
      boton.textContent = 'Cancelando…';
      mostrarMensaje('Cancelando reserva…');

      const contenido = await solicitarJson(`/api/reservas/${encodeURIComponent(id)}/estado`, {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': token,
        },
        body: JSON.stringify({
          estado: 'cancelada',
        }),
      });

      // Desde aquí la API ha confirmado que la operación se ha completado.
      cancelacionConfirmada = true;

      if (
        !contenido?.datos ||
        contenido.datos.estado !== 'cancelada' ||
        !Array.isArray(contenido.eventos)
      ) {
        throw new Error('La respuesta de cancelación no tiene el formato esperado.');
      }

      if (esListado) {
        // Actualiza inmediatamente la fila con la respuesta del PATCH.
        // Si después falla la consulta del listado, no dejamos un botón
        // que permita volver a cancelar una reserva ya cancelada.
        const fila = boton.closest('tr');

        if (fila) {
          fila.replaceWith(crearFila(contenido.datos));
        }

        // Vuelve a aplicar los filtros activos.
        await actualizarListado(parametros);
      } else {
        actualizarDetalle(contenido.datos, contenido.eventos, boton);
      }

      mostrarMensaje(`La reserva #${id} se ha cancelado correctamente.`, 'exito');
    } catch (error) {
      if (cancelacionConfirmada) {
        // No confundimos un fallo al actualizar la pantalla con
        // un fallo en una cancelación que el servidor ya confirmó.
        boton.remove();

        mostrarMensaje(
          'El servidor ha confirmado la cancelación, pero no se ha podido ' +
            'actualizar completamente la pantalla. Recarga la página.',
          'error'
        );
      } else {
        mostrarMensaje(
          `${obtenerMensajeError(error)} Si se perdió la conexión durante ` +
            'el envío, comprueba el estado antes de volver a intentarlo.',
          'error'
        );
      }
    } finally {
      boton.textContent = textoOriginal;
      establecerCarga(false);
    }
  }

  // Los eventos de filtrado solo se registran en el listado.
  if (esListado) {
    formulario.addEventListener('submit', (evento) => {
      evento.preventDefault();
      cargarReservas();
    });

    botonLimpiar.addEventListener('click', () => {
      if (ocupado) {
        return;
      }

      formulario.reset();
      cargarReservas();
    });
  }

  // Un único listener también atiende los botones creados al filtrar.
  document.addEventListener('click', (evento) => {
    if (!(evento.target instanceof Element)) {
      return;
    }

    const boton = evento.target.closest('.boton-cancelar');

    if (!(boton instanceof HTMLButtonElement)) {
      return;
    }

    evento.preventDefault();

    if (!boton.disabled) {
      cancelarReserva(boton);
    }
  });
})();
