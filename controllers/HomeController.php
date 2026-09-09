<?php
/**
 * controllers/HomeController.php
 * Public Landing Page Controller for RSUD Meuraxa
 */

class HomeController {
    public function index(): void {
        $currentUser = current_user();
        view('home/index', compact('currentUser'));
    }

    public function storePesan(): void {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            return;
        }

        $email        = trim($_POST['email'] ?? '');
        $subjek       = trim($_POST['subjek'] ?? '');
        $pesan        = trim($_POST['pesan'] ?? '');

        if (!$email || !$subjek || !$pesan) {
            echo json_encode(['success' => false, 'message' => 'Semua kolom wajib diisi.']);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Format email tidak valid.']);
            return;
        }

        try {
            $db = Database::connect();
            $stmt = $db->prepare("INSERT INTO `pesan_cs` (`email`, `subjek`, `pesan`) VALUES (?, ?, ?)");
            $stmt->execute([$email, $subjek, $pesan]);
            echo json_encode(['success' => true, 'message' => 'Terima kasih! Pesan Anda telah terkirim ke Tim Customer Service.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem saat menyimpan pesan.']);
        }
    }
}
