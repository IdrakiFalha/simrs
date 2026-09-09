<?php
/**
 * controllers/KlinisController.php
 * Clinical examination — vitals, NEWS2, prescription, DUR check.
 */
require_once APP_ROOT . '/config/satusehat_api.php';
require_once APP_ROOT . '/controllers/IntegrasiController.php';

class KlinisController {
    private PasienModel    $pasien;
    private KunjunganModel $kunjungan;
    private ObatModel      $obat;
    private AuditModel     $audit;

    public function __construct() {
        $this->pasien    = new PasienModel();
        $this->kunjungan = new KunjunganModel();
        $this->obat      = new ObatModel();
        $this->audit     = new AuditModel();
    }

    /** Render pemeriksaan page */
    public function periksa(): void {
        $pasienId = (int)($_GET['pasien_id'] ?? 0);
        $pasien   = $this->pasien->find($pasienId);
        if (!$pasien) { flash('error', 'Pasien tidak ditemukan.'); redirect('admisi'); }

        $umur     = (int) date_diff(date_create($pasien['tanggal_lahir']), date_create('now'))->y;
        $riwayat  = $this->kunjungan->findByPasien($pasienId);
        $obatList = $this->obat->all();
        $flashOk  = flash('ok');
        $flashErr = flash('error');
        $fhirJson = flash('fhir_json') ? json_decode(flash('fhir_json'), true) : null;
        $privacy  = !$pasien['consent_satusehat'];

        view('klinis/periksa', compact('pasien', 'umur', 'riwayat', 'obatList', 'flashOk', 'flashErr', 'fhirJson', 'privacy'));
    }

    /** Store clinical examination + prescription */
    public function store(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('admisi');
        if (!csrf_verify()) { flash('error', 'Token CSRF tidak valid.'); redirect('admisi'); }

        $pasienId = (int)($_POST['pasien_id'] ?? 0);
        $pasien   = $this->pasien->find($pasienId);
        if (!$pasien) { flash('error', 'Pasien tidak ditemukan.'); redirect('admisi'); }

        $vitals = [
            'sistole'    => (int)($_POST['sistole'] ?? 0),
            'diastole'   => (int)($_POST['diastole'] ?? 0),
            'nadi'       => (int)($_POST['nadi'] ?? 0),
            'suhu'       => (float)($_POST['suhu'] ?? 0),
            'laju_napas' => (int)($_POST['laju_napas'] ?? 0),
        ];

        // Build FHIR Encounter payload & execute live push to SATUSEHAT
        $fhirPayload = null;
        $encounterStatus = 'pending';
        $satusehatEncounterId = null;
        $satusehatNotice = '';

        if ($pasien['consent_satusehat']) {
            $fhirPayload = $this->buildFhirEncounter($pasien, $vitals);
            
            $integrasiCtrl = new IntegrasiController();
            $pushResult    = $integrasiCtrl->pushEncounter($fhirPayload);

            if ($pushResult['success']) {
                $encounterStatus      = 'sent';
                $satusehatEncounterId = $pushResult['encounter_id'];
                $satusehatNotice      = ' | SATUSEHAT: Terkirim (ID: ' . ($pushResult['encounter_id'] ?? 'OK') . ')';
            } else {
                $encounterStatus = 'failed';
                $errIssues = $pushResult['response']['issue'] ?? [];
                $errDetail = [];
                foreach ($errIssues as $issue) {
                    $errDetail[] = $issue['details']['text'] ?? ($issue['diagnostics'] ?? 'unknown error');
                }
                $errDesc = !empty($errDetail) ? implode('; ', $errDetail) : ('HTTP ' . ($pushResult['http_code'] ?? 'Error'));
                $satusehatNotice = ' | SATUSEHAT: Gagal (' . $errDesc . ')';
            }
        } else {
            $satusehatNotice = ' | SATUSEHAT: Tidak dikirim (Consent Nonaktif)';
        }

        $kunjunganId = $this->kunjungan->create(array_merge($vitals, [
            'pasien_id'               => $pasienId,
            'dokter_id'               => current_user()['id'],
            'keluhan'                 => $_POST['keluhan'] ?? '',
            'diagnosa_icd10'          => $_POST['diagnosa_icd10'] ?? '',
            'satusehat_encounter_id'  => $satusehatEncounterId,
            'status_kirim_satusehat'  => $encounterStatus,
        ]));

        // Process prescription rows
        $db = Database::connect();
        $obatIds     = $_POST['obat_id'] ?? [];
        $aturanPakai = $_POST['aturan_pakai'] ?? [];
        foreach ($obatIds as $idx => $obatId) {
            $obatId = (int)$obatId;
            if ($obatId <= 0) continue;
            
            $stmt = $db->prepare(
                'INSERT INTO resep (kunjungan_id, obat_id, aturan_pakai, status_dur_warning)
                 VALUES (:kid, :oid, :aturan, :dur)'
            );
            $stmt->execute([
                ':kid'    => $kunjunganId,
                ':oid'    => $obatId,
                ':aturan' => $aturanPakai[$idx] ?? '',
                ':dur'    => 'none',
            ]);
        }

        $this->audit->log(current_user()['id'], 'create_kunjungan', 'kunjungan');

        if ($fhirPayload) {
            flash('fhir_json', json_encode($fhirPayload));
        }
        flash('ok', 'Pemeriksaan berhasil disimpan.' . $satusehatNotice);
        redirect('klinis/periksa', ['pasien_id' => $pasienId]);
    }



    /** AJAX: search drugs */
    public function cariObat(): void {
        $q = trim($_GET['q'] ?? '');
        $results = $q !== '' ? $this->obat->search($q) : [];
        json_response(['ok' => true, 'data' => $results]);
    }

    /** Build SATUSEHAT FHIR R4 Encounter resource */
    private function buildFhirEncounter(array $pasien, array $vitals): array {
        $nowIso = date('Y-m-d\TH:i:sP');

        // Gunakan IHS sandbox resmi Kemenkes saat staging, IHS asli saat production
        if (SATUSEHAT_ENV === 'staging') {
            $patientIhs = SANDBOX_PATIENT_IHS;
            $doctorIhs  = SANDBOX_PRACTITIONER_IHS;
        } else {
            $patientIhs = !empty($pasien['ihs_number']) ? $pasien['ihs_number'] : '';
            $doctorIhs  = !empty(current_user()['ihs_number']) ? current_user()['ihs_number'] : '';
        }

        $encounterIdLocal = 'ENC-' . time() . '-' . rand(100, 999);

        $conditionUuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
        $icdCode = !empty($_POST['diagnosa_icd10']) ? trim($_POST['diagnosa_icd10']) : 'K29.7';

        return [
            'resourceType' => 'Encounter',
            'identifier'   => [
                [
                    'system' => 'http://sys-ids.kemkes.go.id/encounter/' . ORGANIZATION_ID,
                    'value'  => $encounterIdLocal,
                ]
            ],
            'status'       => 'arrived',
            'class'        => [
                'system'  => 'http://terminology.hl7.org/CodeSystem/v3-ActCode',
                'code'    => 'AMB',
                'display' => 'ambulatory',
            ],
            'subject'      => [
                'reference' => 'Patient/' . $patientIhs,
                'display'   => $pasien['nama'] ?? 'Pasien SIMRS',
            ],
            'participant'  => [
                [
                    'type' => [
                        [
                            'coding' => [
                                [
                                    'system'  => 'http://terminology.hl7.org/CodeSystem/v3-ParticipationType',
                                    'code'    => 'ATND',
                                    'display' => 'attending',
                                ]
                            ]
                        ]
                    ],
                    'individual' => [
                        'reference' => 'Practitioner/' . $doctorIhs,
                        'display'   => current_user()['nama_lengkap'] ?? 'Dokter SIMRS',
                    ]
                ]
            ],
            'period'       => [
                'start' => $nowIso,
            ],
            'statusHistory' => [
                [
                    'status' => 'arrived',
                    'period' => [
                        'start' => $nowIso,
                    ]
                ]
            ],
            'serviceProvider' => [
                'reference' => 'Organization/' . ORGANIZATION_ID,
            ],
            'location'     => [
                [
                    'location' => [
                        'reference' => 'Location/' . LOCATION_ID,
                        'display'   => 'Poliklinik Rawat Jalan',
                    ],
                    'status' => 'active',
                ]
            ],
        ];
    }
}
