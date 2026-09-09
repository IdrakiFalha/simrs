<?php
/**
 * controllers/AuditController.php
 * Audit log viewer.
 */
class AuditController {
    private AuditModel $audit;

    public function __construct() {
        $this->audit = new AuditModel();
    }

    public function index(): void {
        require_role(['Admin', 'Dokter']);
        $logs = $this->audit->all(200);
        view('audit/index', compact('logs'));
    }
}
