'use strict';

(() => {
  const formulario = document.querySelector('#formulario-filtros');
  const tabla = document.querySelector('#tabla-reservas');
  const mensaje = document.querySelector('#mensaje');
  const botonLimpiar = document.querySelector('#limpiar-filtros');
  const estadoReserva = document.querySelector('#estado-reserva');
  const historialEventos = document.querySelector('#historial-eventos');
  const formularioCreacion = document.querySelector('#formulario-creacion');
  const mensajeCreacion = document.querySelector('#mensaje-creacion');

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

    document.querySelectorAll('.boton-cancelar, .boton-confirmar').forEach((boton) => {
      boton.disabled = cargando;
    });

    if (tabla) {
      tabla.setAttribute('aria-busy', String(cargando));
    }

    if (estadoReserva) {
      estadoReserva.closest('.detalle')?.setAttribute('aria-busy', String(cargando));
    }

    if (formularioCreacion) {
      formularioCreacion.querySelectorAll('input, textarea, button').forEach((control) => {
        control.disabled = cargando;
      });

      formularioCreacion.setAttribute('aria-busy', String(cargando));
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

    // Columna «Ver».
    const celdaVer = agregarCelda(fila, '');
    const enlace = document.createElement('a');

    enlace.href = `/reservas/${encodeURIComponent(String(reserva.id))}`;
    enlace.className = 'boton-detalle';
    enlace.textContent = 'Ver detalle';

    celdaVer.append(enlace);

    // Columna «Gestionar».
    const celdaGestionar = agregarCelda(fila, '');
    const acciones = document.createElement('div');

    acciones.className = 'acciones-reserva';

    if (reserva.estado === 'pendiente') {
      const botonConfirmar = document.createElement('button');

      botonConfirmar.type = 'button';
      botonConfirmar.className = 'boton-confirmar';
      botonConfirmar.dataset.id = String(reserva.id);
      botonConfirmar.textContent = 'Confirmar';
      botonConfirmar.disabled = ocupado;

      const botonCancelar = document.createElement('button');

      botonCancelar.type = 'button';
      botonCancelar.className = 'boton-cancelar';
      botonCancelar.dataset.id = String(reserva.id);
      botonCancelar.textContent = 'Cancelar';
      botonCancelar.disabled = ocupado;

      acciones.append(botonConfirmar, botonCancelar);
    }
    
    celdaGestionar.append(acciones);

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

  async function cambiarEstadoReserva(boton, nuevoEstado) {
    if (ocupado) {
      return;
    }

    const id = boton.dataset.id;

    if (!id || !/^[1-9]\d*$/.test(id)) {
      mostrarMensaje('El identificador de la reserva no es válido.', 'error');
      return;
    }

    const esConfirmacion = nuevoEstado === 'confirmada';
    const accion = esConfirmacion ? 'confirmar' : 'cancelar';
    const progreso = esConfirmacion ? 'Confirmando…' : 'Cancelando…';
    const resultado = esConfirmacion ? 'confirmado' : 'cancelado';

    if (!window.confirm(`¿Quieres ${accion} la reserva #${id}?`)) {
      return;
    }

    let operacionConfirmada = false;
    const textoOriginal = boton.textContent;

    try {
      const token = document.querySelector('meta[name="csrf-token"]')?.content;

      if (!token) {
        throw new Error('No se ha encontrado el token de seguridad. Recarga la página.');
      }

      const parametros = esListado ? obtenerParametros() : null;

      establecerCarga(true);
      boton.textContent = progreso;
      mostrarMensaje(progreso);

      const contenido = await solicitarJson(`/api/reservas/${encodeURIComponent(id)}/estado`, {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': token,
        },
        body: JSON.stringify({
          estado: nuevoEstado,
        }),
      });

      operacionConfirmada = true;

      if (
        !contenido?.datos ||
        contenido.datos.estado !== nuevoEstado ||
        !Array.isArray(contenido.eventos)
      ) {
        throw new Error('La respuesta no tiene el formato esperado.');
      }

      if (esListado) {
        const fila = boton.closest('tr');

        if (fila) {
          fila.replaceWith(crearFila(contenido.datos));
        }

        await actualizarListado(parametros);
      } else {
        actualizarDetalle(contenido.datos, contenido.eventos, boton);
      }

      mostrarMensaje(`La reserva #${id} se ha ${resultado} correctamente.`, 'exito');
    } catch (error) {
      if (operacionConfirmada) {
        // Evita repetir una acción que el servidor ya ha aceptado.
        const contenedor = boton.closest('.acciones-reserva, .detalle__acciones');

        contenedor
          ?.querySelectorAll('.boton-confirmar, .boton-cancelar')
          .forEach((elemento) => elemento.remove());

        mostrarMensaje(
          'El servidor ha confirmado el cambio, pero no se ha podido ' +
            'actualizar completamente la pantalla. Recarga la página.',
          'error'
        );
      } else {
        mostrarMensaje(
          `${obtenerMensajeError(error)} Si se perdió la conexión durante ` +
            'el envío, comprueba el estado antes de repetir la operación.',
          'error'
        );
      }
    } finally {
      boton.textContent = textoOriginal;
      establecerCarga(false);
    }
  }

  function mostrarMensajeCreacion(texto, tipo = 'informacion') {
    mensajeCreacion.textContent = texto;
    mensajeCreacion.dataset.tipo = tipo;
    mensajeCreacion.hidden = false;
  }

  async function crearReserva(evento) {
    evento.preventDefault();

    if (ocupado || !formularioCreacion || !mensajeCreacion) {
      return;
    }

    if (!formularioCreacion.reportValidity()) {
      return;
    }

    const boton = formularioCreacion.querySelector('button[type="submit"]');
    const textoOriginal = boton.textContent;
    let creacionConfirmada = false;

    try {
      // FormData se construye antes de deshabilitar los controles.
      const campos = new FormData(formularioCreacion);

      const datos = {
        nombre_huesped: String(campos.get('nombre_huesped') ?? '').trim(),
        email_huesped: String(campos.get('email_huesped') ?? '').trim(),
        alojamiento: String(campos.get('alojamiento') ?? '').trim(),
        fecha_entrada: String(campos.get('fecha_entrada') ?? ''),
        fecha_salida: String(campos.get('fecha_salida') ?? ''),
        num_personas: Number(campos.get('num_personas')),
        importe_total: String(campos.get('importe_total') ?? ''),
        notas: String(campos.get('notas') ?? '').trim(),
      };

      if (!datos.nombre_huesped || !datos.email_huesped || !datos.alojamiento) {
        throw new Error('Los campos obligatorios no pueden contener solo espacios.');
      }

      if (datos.fecha_salida <= datos.fecha_entrada) {
        throw new Error('La fecha de salida debe ser posterior a la fecha de entrada.');
      }

      const token = document.querySelector('meta[name="csrf-token"]')?.content;

      if (!token) {
        throw new Error('No se ha encontrado el token de seguridad. Recarga la página.');
      }

      // Conservamos los filtros activos al actualizar la tabla.
      const parametros = obtenerParametros();

      establecerCarga(true);
      boton.textContent = 'Creando…';
      mostrarMensajeCreacion('Creando reserva…');

      const contenido = await solicitarJson('/api/reservas', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': token,
        },
        body: JSON.stringify(datos),
      });

      creacionConfirmada = true;
      formularioCreacion.reset();

      if (!contenido?.datos?.id) {
        throw new Error('La respuesta no contiene el identificador de la reserva creada.');
      }

      await actualizarListado(parametros);

      const hayFiltros = parametros.toString() !== '';

      mostrarMensajeCreacion(
        `Reserva #${contenido.datos.id} creada correctamente.` +
          (hayFiltros
            ? ' Se mantienen los filtros: podría no aparecer en la tabla si no coincide con ellos.'
            : ''),
        'exito'
      );
    } catch (error) {
      if (creacionConfirmada) {
        mostrarMensajeCreacion(
          'El servidor ha confirmado la creación, pero no se ha podido ' +
            'actualizar completamente la pantalla. No vuelvas a enviarla; ' +
            'recarga el listado para comprobarla.',
          'error'
        );
      } else {
        const avisoRed =
          error instanceof TypeError
            ? ' Si el fallo ocurrió durante el envío, comprueba el listado ' +
              'antes de repetirlo para evitar duplicados.'
            : '';

        mostrarMensajeCreacion(obtenerMensajeError(error) + avisoRed, 'error');
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

  if (esListado && formularioCreacion && mensajeCreacion) {
    formularioCreacion.addEventListener('submit', crearReserva);
  }

  document.addEventListener('click', (evento) => {
    if (!(evento.target instanceof Element)) {
      return;
    }

    const boton = evento.target.closest('.boton-cancelar, .boton-confirmar');

    if (!(boton instanceof HTMLButtonElement) || boton.disabled) {
      return;
    }

    evento.preventDefault();

    const nuevoEstado = boton.classList.contains('boton-confirmar') ? 'confirmada' : 'cancelada';

    cambiarEstadoReserva(boton, nuevoEstado);
  });
})();
