<?php
/**
 * controllers/Api/AuditApiController.php
 * REST API — Audit Log Viewer.
 *
 * Endpoints:
 *  GET /api/audit → Return paginated audit log (Admin only)
 */
class AuditApiController
{
    private AuditModel $audit;

    public function __construct()
    {
        $this->audit = new AuditModel();
    }

    // ── GET /api/audit ────────────────────────────────────────────────────────
    /**
     * Query params:
     *  ?limit=N   → number of records to return (default 100, max 500)
     *
     * Response: { ok, data: [...], total }
     * Each entry includes user info (nama_lengkap, role) via the AuditModel JOIN.
     */
    public function index(): void
    {
        api_role_guard(['Admin', 'Dokter']);

        $limit = min((int)($_GET['limit'] ?? 100), 500);
        $logs  = $this->audit->all($limit);

        json_response([
            'ok'    => true,
            'data'  => $logs,
            'total' => count($logs),
        ]);
    }
}
