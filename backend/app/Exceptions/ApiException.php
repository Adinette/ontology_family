<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/** Erreur « métier » renvoyée au client en JSON (404, 422, 403 ...). */
class ApiException extends Exception
{
    public function __construct(string $message, private int $httpStatus = 422, private array $errors = [])
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return $this->httpStatus;
    }

    public function render($request): JsonResponse
    {
        $body = ['error' => $this->getMessage()];
        if ($this->errors) {
            $body['errors'] = $this->errors;
        }

        return response()->json($body, $this->httpStatus);
    }
}
