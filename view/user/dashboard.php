<?php
session_start();
include '../../includes/database.php'; // Cek apakah koneksi berhasil

// Cek apakah user sudah login
// Jika belum login, arahkan ke halaman login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../../loginuser.php");
    exit;
}

$user_id = $_SESSION['user_id'];  // Cek apakah user_id ada di session

// Ambil data user
$user_query = "SELECT username, photo FROM users WHERE id = $user_id LIMIT 1";
$user_result = mysqli_query($conn, $user_query);
$user_data = mysqli_fetch_assoc($user_result);

// Cek apakah data user ditemukan
$photo_path = '../../uploads/profile/' . ($user_data['photo'] ?: 'default-profile.png');
if (!file_exists($photo_path) || empty($user_data['photo'])) {
    $photo_path = '../../assets/img/default-profile.png';
}


// Pagination
$limit = 5;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1) $page = 1;
$start = ($page - 1) * $limit;


// Ambil produk user
$query = "SELECT id, name, stock, created_at, status FROM products WHERE user_id = $user_id ORDER BY created_at DESC LIMIT $start, $limit";
$result = mysqli_query($conn, $query);

// Hitung total data
$countQuery = "SELECT COUNT(*) AS total FROM products WHERE user_id = $user_id";
$countResult = mysqli_query($conn, $countQuery);
$totalData = mysqli_fetch_assoc($countResult)['total'];
$totalPages = ceil($totalData / $limit);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f6f9;
            color: #333;
        }

        .dashboard-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .profile-photo {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #0d6efd;
            margin-right: 1rem;
        }

        .header-box {
            background: #fff;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }

        .btn {
            border-radius: 8px;
        }

        .table-container {
            background: #fff;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .table thead {
            background-color: #e9f1ff;
            color: #0d6efd;
        }

        .pagination .page-link {
            color: #0d6efd;
            border-radius: 6px;
        }

        .pagination .page-item.active .page-link {
            background-color: #0d6efd;
            border-color: #0d6efd;
            color: #fff;
        }

        .alert {
            border-radius: 10px;
        }

        .badge-status {
            padding: 0.5em 0.8em;
            border-radius: 0.375rem;
            font-weight: 600;
            font-size: 0.85em;
        }

        .badge-status.pending {
            background-color: #ffc107;
            color: #856404;
        }

        .badge-status.rejected {
            background-color: #dc3545;
            color: #fff;
        }

        .badge-status.approved {
            background-color: #28a745;
            color: #fff;
        }

        .badge-status.unknown {
            background-color: #6c757d;
            color: #fff;
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <div class="header-box d-flex align-items-center justify-content-between flex-wrap">
            <div class="d-flex align-items-center">
                <img src="<?= htmlspecialchars($photo_path); ?>" alt="Foto Profil" class="profile-photo">
                <div>
                    <h2 class="mb-0">Hai, <?= htmlspecialchars($user_data['username']); ?> 👋</h2>
                    <p class="text-muted mb-0 mt-1">Selamat datang kembali di dashboard kamu</p>
                </div>
            </div>
            <div class="mt-3 mt-md-0">
                <a href="profil.php" class="btn btn-outline-secondary me-2"><i class="fas fa-user"></i> Profil</a>
                <a href="produk.php" class="btn btn-primary me-2"><i class="fas fa-box"></i> Kelola Produk</a>
                <a href="logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <div class="table-container">
            <h4 class="mb-4"><i class="fas fa-clock"></i> Produk Terbaru Anda</h4>

            <?php if (mysqli_num_rows($result) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Nama Produk</th>
                                <th>Stok</th>
                                <th>Tanggal Submit</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($produk = mysqli_fetch_assoc($result)): ?>
                                <?php
                                // Ambil dan normalisasi status dari database
                                $statusRaw = strtolower(trim($produk['status']));
                                $statusClass = 'badge-status unknown';
                                $statusText  = 'Status Tidak Dikenal';

                                // Standarisasi status (gunakan hanya 3 nilai: menunggu, berhasil, ditolak)
                                if ($statusRaw === 'menunggu') {
                                    $statusClass = 'badge-status pending';
                                    $statusText  = '🕒 Menunggu Konfirmasi';
                                } elseif ($statusRaw === 'berhasil') {
                                    $statusClass = 'badge-status approved';
                                    $statusText  = '✅ Berhasil';
                                } elseif ($statusRaw === 'ditolak') {
                                    $statusClass = 'badge-status rejected';
                                    $statusText  = '❌ Ditolak';
                                }
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($produk['name']) ?></td>
                                    <td><span class="badge bg-primary"><?= (int)$produk['stock'] ?> pcs</span></td>
                                    <td><?= date('d-m-Y H:i', strtotime($produk['created_at'])) ?></td>
                                    <td><span class="<?= $statusClass ?>"><?= $statusText ?></span></td>
                                </tr>
                            <?php endwhile; ?>

                        </tbody>
                    </table>
                </div>

                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i; ?>"><?= $i; ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i> Belum ada produk yang dikirim. Yuk tambah produk pertama kamu!
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>