<?php

class CSRF {

    public function new(): void
    {
        if (empty($_SESSION['_token'])) {
            $_SESSION['_token'] = bin2hex(random_bytes(32));
            $_SESSION["token_expire"] = time() + 1800; // 30 minutes
        }
    }

    public function get(): array
    {
        $token = $_SESSION['_token'] ?? '';
        $token_expire = $_SESSION["token_expire"] ?? 0;

        return [$token, $token_expire];
    }

    public function isValid(string $token): bool
    {
        return hash_equals(($_SESSION['_token'] ?? ''), $token);
    }

    public function isExpired(): bool
    {
        $expire = $_SESSION["token_expire"] ?? 0;

        return time() >= $expire;
    }

    public function drop(): void
    {
        unset($_SESSION["_token"]);
        unset($_SESSION["token_expire"]);
    }
}
