<?php
session_start();
require_once '../koneksi.php';

// Cek jika user sudah login dan rolenya adalah dosen
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'dosen') {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$database = new Database();
$pdo = $database->getConnection();

$error_message = '';
$success_message = '';

// Ambil ID pengumpulan dari URL
$submission_id = $_GET['id'] ?? 0;
if (!$submission_id) {
    header('Location: penilaian_tugas.php');
    exit();
}

// Logika untuk menyimpan nilai saat form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $score = $_POST['score'];
    $feedback = $_POST['feedback'];
    $posted_submission_id = $_POST['submission_id'];

    if ($score === '' || !is_numeric($score)) {
        $error_message = 'Nilai harus diisi dengan angka.';
    } else {
        try {
            $sql_update = "UPDATE submission 
                           SET score = ?, feedback = ?, status = 'graded', dinilai_oleh = ?, tanggal_dinilai = NOW() 
                           WHERE submission_id = ?";
            $stmt_update = $pdo->prepare($sql_update);
            
            if ($stmt_update->execute([$score, $feedback, $user_id, $posted_submission_id])) {
                $success_message = 'Nilai berhasil disimpan! Anda akan diarahkan kembali.';
                header("refresh:2;url=penilaian_tugas.php");
            } else {
                $error_message = 'Gagal menyimpan nilai.';
            }
        } catch (PDOException $e) {
            $error_message = 'Terjadi error pada database: ' . $e->getMessage();
        }
    }
}

// Ambil detail pengumpulan tugas dari database
$sql_submission = "
    SELECT 
        s.submission_id, s.tanggal_dikumpulkan, s.file_path, s.submission_title, s.student_comment, s.score, s.feedback,
        u.full_name as student_name,
        a.title as assignment_title, a.max_score,
        c.nama_matkul
    FROM submission s
    JOIN users u ON s.id_mahasiswa = u.user_id
    JOIN assignment a ON s.id_tugas = a.assignment_id
    JOIN course c ON a.id_matkul = c.course_id
    WHERE s.submission_id = ?";
$stmt_submission = $pdo->prepare($sql_submission);
$stmt_submission->execute([$submission_id]);
$submission = $stmt_submission->fetch(PDO::FETCH_ASSOC);

if (!$submission) {
    header('Location: penilaian_tugas.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Beri Nilai Tugas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="dosen.css">
    <style>
        .quick-grade-buttons {
            display: flex;
            gap: 5px;
            margin-top: 8px;
        }
        .quick-grade-btn {
            background-color: #fff;
            border: 1px solid #ced4da;
            border-radius: 8px;
            padding: 8px 15px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .quick-grade-btn:hover {
            background-color: #f1f3f5;
            border-color: #adb5bd;
        }
        .quick-grade-btn.active {
            background-color: var(--primary-color);
            color: #fff;
            border-color: var(--primary-color);
        }
        .grade-legend {
            font-size: 12px;
            color: #6c757d;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <?php include 'sidebar_dosen.html'; ?>

    <div class="main-content">
        <header class="header">
            <h1>Beri Nilai Tugas</h1>
            <p style="color: #6c757d;">Review dan berikan nilai untuk tugas yang dikumpulkan mahasiswa.</p>
        </header>

        <div class="section">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <h2 class="section-title">Detail Pengumpulan</h2>
                    <table class="data-table">
                         <tr>
                            <th style="width: 200px;">Mahasiswa</th>
                            <td><?php echo htmlspecialchars($submission['student_name']); ?></td>
                        </tr>
                        <tr>
                            <th>Mata Kuliah</th>
                            <td><?php echo htmlspecialchars($submission['nama_matkul']); ?></td>
                        </tr>
                         <tr>
                            <th>Tugas</th>
                            <td><?php echo htmlspecialchars($submission['assignment_title']); ?></td>
                        </tr>
                        <tr>
                            <th>Waktu Kumpul</th>
                            <td><?php echo date('d M Y, H:i', strtotime($submission['tanggal_dikumpulkan'])); ?></td>
                        </tr>
                        <tr>
                            <th>Judul Pengumpulan</th>
                            <td><?php echo htmlspecialchars($submission['submission_title'] ?: 'Tidak ada judul'); ?></td>
                        </tr>
                         <tr>
                            <th>Komentar Mahasiswa</th>
                            <td><?php echo nl2br(htmlspecialchars($submission['student_comment'] ?: 'Tidak ada komentar.')); ?></td>
                        </tr>
                        <tr>
                            <th>File Jawaban</th>
                            <td>
                                <a href="<?php echo htmlspecialchars($submission['file_path']); ?>" target="_blank" class="btn btn-success btn-sm">
                                    <i class="fas fa-download"></i> Unduh Jawaban
                                </a>
                            </td>
                        </tr>
                    </table>
                </div>

                <div>
                    <h2 class="section-title">Formulir Penilaian</h2>
                    
                    <?php if ($success_message): ?>
                        <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <?php elseif ($error_message): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php else: ?>
                        <form method="POST">
                            <input type="hidden" name="submission_id" value="<?php echo $submission['submission_id']; ?>">
                            
                            <div class="form-group">
                                <label for="score" class="form-label">Nilai (Maks: <?php echo $submission['max_score']; ?>)</label>
                                <input type="number" name="score" id="score" class="form-control" 
                                       value="<?php echo htmlspecialchars($submission['score'] ?? ''); ?>"
                                       max="<?php echo $submission['max_score']; ?>" min="0" required>
                                
                                <div class="quick-grade-buttons">
                                    <button type="button" class="quick-grade-btn" id="btn-grade-A" onclick="setScore(90)">A</button>
                                    <button type="button" class="quick-grade-btn" id="btn-grade-B" onclick="setScore(75)">B</button>
                                    <button type="button" class="quick-grade-btn" id="btn-grade-C" onclick="setScore(60)">C</button>
                                    <button type="button" class="quick-grade-btn" id="btn-grade-D" onclick="setScore(45)">D</button>
                                    <button type="button" class="quick-grade-btn" id="btn-grade-E" onclick="setScore(20)">E</button>
                                </div>
                                <div class="grade-legend">
                                    Keterangan: A (85-100), B (70-84), C (55-69), D (40-54), E (0-39)
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="feedback" class="form-label">Feedback / Komentar Dosen</label>
                                <textarea name="feedback" id="feedback" rows="8" class="form-control"><?php echo htmlspecialchars($submission['feedback'] ?? ''); ?></textarea>
                            </div>

                            <div style="text-align: right;">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Nilai</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        const scoreInput = document.getElementById('score');
        const gradeButtons = {
            'A': document.getElementById('btn-grade-A'),
            'B': document.getElementById('btn-grade-B'),
            'C': document.getElementById('btn-grade-C'),
            'D': document.getElementById('btn-grade-D'),
            'E': document.getElementById('btn-grade-E')
        };

        // Fungsi untuk mengisi nilai & highlight tombol
        function setScore(value) {
            const maxScore = <?php echo $submission['max_score'] ?: 100; ?>;
            scoreInput.value = Math.min(value, maxScore);
            highlightGradeButton();
        }

        // Fungsi untuk highlight tombol berdasarkan nilai
        function highlightGradeButton() {
            const score = parseInt(scoreInput.value, 10);
            let activeGrade = null;

            if (score >= 85) { activeGrade = 'A'; }
            else if (score >= 70) { activeGrade = 'B'; }
            else if (score >= 55) { activeGrade = 'C'; }
            else if (score >= 40) { activeGrade = 'D'; }
            else if (score >= 0) { activeGrade = 'E'; }

            // Hapus class 'active' dari semua tombol
            for (const grade in gradeButtons) {
                if (gradeButtons[grade]) {
                    gradeButtons[grade].classList.remove('active');
                }
            }

            // Tambahkan class 'active' ke tombol yang sesuai
            if (activeGrade && gradeButtons[activeGrade]) {
                gradeButtons[activeGrade].classList.add('active');
            }
        }

        // Tambahkan event listener ke input nilai
        scoreInput.addEventListener('input', highlightGradeButton);

        // Jalankan saat halaman pertama kali dimuat untuk memeriksa nilai yang sudah ada
        document.addEventListener('DOMContentLoaded', highlightGradeButton);
    </script>
</body>
</html>