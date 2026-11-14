<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Throwable;

/**
 * Erreur de communication avec le serveur SPARQL (Apache Jena Fuseki).
 * Laravel appelle automatiquement render() : le client reçoit un JSON propre.
 */
class FusekiException extends Exception
{
    public function __construct(
        string $message,
        private int $httpStatus = 502,
        private ?string $detail = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function status(): int
    {
        return $this->httpStatus;
    }

    public function render($request): JsonResponse
    {
        return response()->json([
            'error'  => $this->getMessage(),
            'detail' => $this->detail,
        ], $this->httpStatus);
    }
}
