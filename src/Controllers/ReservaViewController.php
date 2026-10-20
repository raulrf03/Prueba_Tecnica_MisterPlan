<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\ReservaRepository;
use App\Security\Csrf;
use App\View\TwigFactory;
use Throwable;

final class ReservaViewController
{
    public function listar(): void
    {
        try {
            $twig = TwigFactory::crear();
            $repositorio = new ReservaRepository();

            echo $twig->render('reservas/listado.html.twig', [
                'reservas' => $repositorio->obtenerTodas(),
                'csrf_token' => Csrf::obtenerToken(),
            ]);
        } catch (Throwable $e) {
            error_log(sprintf(
                'Error al renderizar el listado: %s en %s:%d',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            ));

            http_response_code(500);
            echo 'No se ha podido cargar el listado de reservas.';
        }
    }

    public function detalle(int $id): void
{
    try {
        $twig = TwigFactory::crear();
        $repositorio = new ReservaRepository();

        $reserva = $repositorio->obtenerPorId($id);

        if ($reserva === null) {
            http_response_code(404);

            echo $twig->render('errores/404.html.twig', [
                'mensaje' => 'La reserva solicitada no existe.',
            ]);

            return;
        }

        $eventos = $repositorio->obtenerEventosPorReservaId($id);

        echo $twig->render('reservas/detalle.html.twig', [
            'reserva' => $reserva,
            'eventos' => $eventos,
            'csrf_token' => Csrf::obtenerToken(),
        ]);
    } catch (Throwable $e) {
        error_log(sprintf(
            'Error al renderizar el detalle de la reserva %d: %s en %s:%d',
            $id,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        ));

        http_response_code(500);
        echo 'No se ha podido cargar el detalle de la reserva.';
    }
}

/** Renderiza la página de error para un recurso no encontrado. */
public function noEncontrado(
    string $mensaje = 'La página solicitada no existe.'
): void {
    try {
        $twig = TwigFactory::crear();

        // Se obtiene el token antes de enviar contenido al navegador.
        $csrfToken = Csrf::obtenerToken();

        http_response_code(404);

        echo $twig->render('errores/404.html.twig', [
            'mensaje' => $mensaje,
            'csrf_token' => $csrfToken,
        ]);
    } catch (Throwable $e) {
        error_log(sprintf(
            'Error al renderizar la página 404: %s en %s:%d',
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        ));

        http_response_code(500);

        echo 'No se ha podido cargar la página de error.';
    }
}
}