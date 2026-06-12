<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Error de negocio que se serializa con el formato estándar de la API:
 *   { "message": "...", "code": "ERROR_CODE" }
 *
 * Los códigos viven en docs/api-contracts.md ("Códigos de error propios").
 */
class BusinessException extends Exception
{
    public function __construct(
        string $message,
        public readonly string $errorCode,
        public readonly int $status = 400,
    ) {
        parent::__construct($message);
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode,
        ], $this->status);
    }
}
