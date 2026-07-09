<?php

declare(strict_types=1);

namespace App\Helpers;

final class Logger
{
    private static ?string $logDir = null;

    public static function init(string $logDir): void
    {
        self::$logDir = rtrim($logDir, '/\\');
        if (!is_dir(self::$logDir)) {
            mkdir(self::$logDir, 0755, true);
        }
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('WARNING', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    public static function audit(
        ?int $userId,
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

        try {
            $pdo = DB::connection();
            $stmt = $pdo->prepare(
                'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent)
                 VALUES (:user_id, :action, :entity_type, :entity_id, :old_values, :new_values, :ip_address, :user_agent)'
            );
            $stmt->execute([
                'user_id'     => $userId,
                'action'      => $action,
                'entity_type' => $entityType,
                'entity_id'   => $entityId,
                'old_values'  => $oldValues ? json_encode($oldValues) : null,
                'new_values'  => $newValues ? json_encode($newValues) : null,
                'ip_address'  => $ip,
                'user_agent'  => $ua,
            ]);
        } catch (\Throwable $e) {
            self::error('Failed to write audit log', [
                'action' => $action,
                'error'  => $e->getMessage(),
            ]);
        }

        self::info("AUDIT: {$action}", [
            'user_id'     => $userId,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
        ]);
    }

    private static function write(string $level, string $message, array $context = []): void
    {
        $date = date('Y-m-d H:i:s');
        $contextJson = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $line = "[{$date}] [{$level}] {$message}{$contextJson}" . PHP_EOL;

        $logFile = (self::$logDir ?? __DIR__ . '/../../storage/logs')
            . '/app-' . date('Y-m-d') . '.log';

        try {
            file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
        } catch (\Throwable $e) {
            // Fail silently — logging should never break the app
        }
    }
}
