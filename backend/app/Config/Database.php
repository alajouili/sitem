<?php

declare(strict_types=1);

namespace App\Config;


final class Database
{
    public static function config(): array
    {
        return [
            'driver'   => Env::get('DB_CONNECTION', 'mysql'),
            'host'     => Env::get('DB_HOST', '127.0.0.1'),
            'port'     => Env::get('DB_PORT', '3306'),
            'database' => Env::get('DB_DATABASE', ''),
            'username' => Env::get('DB_USERNAME', 'root'),
            'password' => Env::get('DB_PASSWORD', ''),
            'charset'  => Env::get('DB_CHARSET', 'utf8mb4'),
            'collation' => Env::get('DB_COLLATION', 'utf8mb4_unicode_ci'),
        ];
    }

    public static function dsn(): string
    {
        $config = self::config();

        if ($config['driver'] === 'sqlite') {
            
            return 'sqlite:' . $config['database'];
        }

        return sprintf(
            '%s:host=%s;port=%s;dbname=%s;charset=%s',
            $config['driver'],
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );
    }
}