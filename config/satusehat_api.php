<?php
// config/satusehat_api.php
// Constants for SATUSEHAT FHIR R4 integration (sandbox environment)

define('SATUSEHAT_ENV', 'staging'); // Set to 'staging' or 'production'

if (SATUSEHAT_ENV === 'staging') {
    define('SATUSEHAT_AUTH_URL', 'https://api-satusehat-stg.dto.kemkes.go.id/oauth2/v1/accesstoken?grant_type=client_credentials');
    define('SATUSEHAT_BASE_URL', 'https://api-satusehat-stg.dto.kemkes.go.id/fhir-r4/v1');
} else {
    define('SATUSEHAT_AUTH_URL', 'https://api-satusehat.kemkes.go.id/oauth2/v1/accesstoken?grant_type=client_credentials');
    define('SATUSEHAT_BASE_URL', 'https://api-satusehat.kemkes.go.id/fhir-r4/v1');
}

define('ORGANIZATION_ID', '06b88280-afdc-4884-b1a0-e0b68e408ebf');

// Location ID: Diambil dari POST /Location yang berhasil saat uji coba staging
// UUID ini sudah terdaftar resmi di Kemenkes SATUSEHAT staging
define('LOCATION_ID', '403b434c-603f-4b74-b8e8-81d39da87b97');

// IHS Sandbox Kemenkes (hanya untuk Staging)
// Ganti dengan IHS dokter dan pasien asli saat Production
define('SANDBOX_PATIENT_IHS',      'P02478375538');
define('SANDBOX_PRACTITIONER_IHS', '10009880728'); // dr. Alexander

define('CLIENT_ID', 'hQuNscGVJswsS7ArGOTYM14r6KrFAqlHA4hQw8F2ZOWtojGM');

define('CLIENT_SECRET', 'BIeKlVDGAQDbCtptnDfjaxO3LivGGpIGC8cSsWZ5InXFGPa1WIYnxxWbeDf6sjex');
?>
