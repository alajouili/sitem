<?php

declare(strict_types=1);

namespace App\Core;

final class Application
{
    public function run(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'ok',
            'message' => 'Backend restored',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
