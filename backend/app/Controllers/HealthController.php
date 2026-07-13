<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Response;

/**
 * Closes Phase 1 gap R1: the health endpoint must actually verify
 * the app can do its job (DB reachable, storage writable), not
 * just that PHP itself is running. Kubernetes will poll this
 * endpoint every few seconds in Phase 5 to decide whether to route
 * traffic to this pod (readinessProbe) or restart it
 * (livenessProbe) — so it must reflect real application health.
 *
 * Deliberately does NOT use Response::success()/error() like the
 * rest of the API — this endpoint is consumed by infrastructure
 * (k8s probes, uptime monitors, Grafana in Phase 8), not the
 * frontend, so it follows the conventional {status, timestamp,
 * checks} health-check shape instead of this app's internal
 * {success, message, data} envelope. Response::json() gives full
 * control over both status code and body shape, which is exactly
 * what's needed here.
 */
final class HealthController
{
    public function check(): Response
    {
        $checks = [];
        $healthy = true;

        $checks['app'] = ['status' => 'ok'];

        // Reuses the SAME PDO singleton every real request uses —
        // not a separate connection — so this check reflects the
        // exact code path the rest of the app depends on.
        try {
            $pdo = Database::connection();
            $pdo->query('SELECT 1');
            $checks['database'] = ['status' => 'ok'];
        } catch (\Throwable $e) {
            $healthy = false;
            // Deliberately no $e->getMessage() here — this endpoint
            // is unauthenticated by nature (probes, load balancers,
            // uptime monitors all hit it without a JWT), so it must
            // never leak internal error detail (ties to Phase 1
            // finding S4 on APP_DEBUG).
            $checks['database'] = ['status' => 'error'];
        }

        $storagePath = __DIR__ . '/../../storage/uploads';
        if (is_writable($storagePath)) {
            $checks['storage'] = ['status' => 'ok'];
        } else {
            $healthy = false;
            $checks['storage'] = ['status' => 'error'];
        }

        return Response::json([
            'status'    => $healthy ? 'healthy' : 'unhealthy',
            'timestamp' => date('c'),
            'checks'    => $checks,
        ], $healthy ? 200 : 503);
    }
}