<?php

if (!isset($_SESSION['id_user'])) {
    exit('Silakan login terlebih dahulu.');
}

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Admin', 'Owner'], true)) {
    echo '<div class="alert alert-danger shadow-sm">';
    echo '<h5><i class="bi bi-shield-lock-fill me-2"></i>Akses Ditolak!</h5>';
    echo '<p class="mb-0">Hanya Admin yang dapat mengelola data pengguna.</p>';
    echo '</div>';
    return;
}

$aksi = isset($_GET['aksi']) ? $_GET['aksi'] : 'view';
$pesan = '';

switch ($aksi) {

    case 'view':
    
        $search = isset($_GET['cari']) ? trim($_GET['cari']) : '';

        if ($search !== '') {
            $like = "%" . $search . "%";
            $stmt = $koneksi->prepare("SELECT id_user, nama, username, role FROM user WHERE nama LIKE ? OR username LIKE ? ORDER BY id_user ASC");
            $stmt->bind_param("ss", $like, $like);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $koneksi->query("SELECT id_user, nama, username, role FROM user ORDER BY id_user ASC");
        }
        ?>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">Daftar Pengguna</h5>
            <a href="index.php?page=user&aksi=tambah" class="btn btn-primary">+ Tambah Pengguna</a>
        </div>

        <form method="GET" class="mb-3">
            <input type="hidden" name="page" value="user">
            <div class="input-group">
                <input type="text" name="cari" class="form-control" placeholder="Cari nama atau username..." value="<?= htmlspecialchars($search); ?>">
                <button class="btn btn-outline-secondary" type="submit">Cari</button>
                <?php if ($search !== '') : ?>
                    <a href="index.php?page=user" class="btn btn-outline-danger">Reset</a>
                <?php endif; ?>
            </div>
        </form>

        <div class="row">
  <?php if ($result && $result->num_rows > 0) : ?>
    <?php while ($row = $result->fetch_assoc()) : ?>
      <?php $warna = ($row['role'] == 'Admin') ? 'bg-danger' : 'bg-primary'; ?>
      <div class="col-md-4 mb-3">
        <div class="card">
          <div class="card-body">
            <h5><?= htmlspecialchars($row['nama']) ?></h5>
            <span class="badge <?= $warna ?>"><?= $row['role'] ?></span>
            <p class="mt-2">Username: <?= htmlspecialchars($row['username']) ?></p>
            <a href="index.php?page=user&aksi=edit&id=<?= $row['id_user'] ?>" class="btn btn-sm btn-warning">Edit</a>
            <?php if ($row['id_user'] != $_SESSION['id_user']) : ?>
            <a href="index.php?page=user&aksi=hapus&id=<?= $row['id_user'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus?')">Hapus</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endwhile; ?>
  <?php else : ?>
    <p>Belum ada data pengguna.</p>
  <?php endif; ?>
</div>
        <?php
        break;
case 'hapus':
    $id = $_GET['id'] ?? '';

    if ($id == $_SESSION['id_user']) {
        echo '<script>alert("Tidak bisa menghapus akun yang sedang login!"); window.location.href="index.php?page=user";</script>';
        exit;
    }

    $stmt = $koneksi->prepare("DELETE FROM user WHERE id_user = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: index.php?page=user");
    exit;

    break;
    case 'tambah':
        if (isset($_POST['simpan'])) {
            $nama = trim($_POST['nama'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $password_input = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? '';

            if (!in_array($role, ['Admin', 'Kasir','Owner'], true)) {
                $pesan = 'Role yang dipilih tidak valid.';
            } elseif ($nama === '' || $username === '' || $password_input === '') {
                $pesan = 'Nama, Username, dan Password wajib diisi.';
            } else {
                $cek = $koneksi->prepare("SELECT id_user FROM user WHERE username = ?");
                $cek->bind_param("s", $username);
                $cek->execute();
                $username_dipakai = $cek->get_result()->num_rows > 0;
                $cek->close();

                if ($username_dipakai) {
                    $pesan = 'Username sudah digunakan oleh pengguna lain.';
                } else {
                    $password = md5($password_input);
                    $stmt = $koneksi->prepare("INSERT INTO user (nama, username, password, role) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("ssss", $nama, $username, $password, $role);
                    if ($stmt->execute()) {
                        echo "<script>alert('Data pengguna berhasil ditambahkan.'); window.location='index.php?page=user';</script>";
                        exit;
                    } else {
                        $pesan = 'Gagal menambahkan data pengguna.';
                    }
                    $stmt->close();
                }
            }
        }
        ?>
        <h5 class="fw-bold mb-3">Tambah Pengguna Baru</h5>
        <?php if ($pesan !== '') : ?>
            <div class="alert alert-warning"><?= htmlspecialchars($pesan); ?></div>
        <?php endif; ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form action="index.php?page=user&aksi=tambah" method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Lengkap</label>
                        <input type="text" name="nama" class="form-control" maxlength="100" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Username</label>
                        <input type="text" name="username" class="form-control" maxlength="50" required>
                    </div>
                    <div class="mb-3">
            <label class="form-label fw-bold">Password</label>
        <div class="input-group">
      <input type="password" id="passwordTambah" name="password" class="form-control" required>
       <button type="button" class="btn btn-outline-secondary" onclick="lihatPasswordTambah()">👁️</button>
    </div>
  </div>

         <script>
          function lihatPasswordTambah() {
          var x = document.getElementById("passwordTambah");
          if (x.type === "password") {
             x.type = "text";
             } else {
             x.type = "password";
  }
}
            </script>
                    <div class="mb-4">
                        <label class="form-label fw-bold">Hak Akses</label>
                        <select name="role" class="form-select" required>
                            <option value="">-- Pilih Role --</option>
                            <option value="Admin">Admin</option>
                            <option value="Kasir">Kasir</option>
                            <option value="Owner">Owner</option>
                        </select>
                    </div>
                    <button type="submit" name="simpan" class="btn btn-primary w-100 mb-2">Simpan Pengguna</button>
                    <a href="index.php?page=user" class="btn btn-light w-100">Batal</a>
                </form>
            </div>
        </div>
        <?php
        break;

    case 'edit':
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id || $id < 1) {
            echo "<script>alert('ID pengguna tidak valid.'); window.location='index.php?page=user';</script>";
            exit;
        }

        $stmt = $koneksi->prepare("SELECT * FROM user WHERE id_user = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$data) {
            echo "<script>alert('Data pengguna tidak ditemukan.'); window.location='index.php?page=user';</script>";
            exit;
        }

        if (isset($_POST['update'])) {
            $nama = trim($_POST['nama'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $password_input = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? '';

            if (!in_array($role, ['Admin', 'Kasir','Owner'], true)) {
                $pesan = 'Role yang dipilih tidak valid.';
            } elseif ($nama === '' || $username === '') {
                $pesan = 'Nama dan Username wajib diisi.';
            } else {
                $cek = $koneksi->prepare("SELECT id_user FROM user WHERE username = ? AND id_user <> ?");
                $cek->bind_param("si", $username, $id);
                $cek->execute();
                $username_dipakai = $cek->get_result()->num_rows > 0;
                $cek->close();

                if ($username_dipakai) {
                    $pesan = 'Username sudah digunakan oleh pengguna lain.';
                } else {
                    if ($password_input !== '') {
                        $password = md5($password_input);
                        $stmt = $koneksi->prepare("UPDATE user SET nama = ?, username = ?, password = ?, role = ? WHERE id_user = ?");
                        $stmt->bind_param("ssssi", $nama, $username, $password, $role, $id);
                    } else {
                        $stmt = $koneksi->prepare("UPDATE user SET nama = ?, username = ?, role = ? WHERE id_user = ?");
                        $stmt->bind_param("sssi", $nama, $username, $role, $id);
                    }

                    if ($stmt->execute()) {
                        echo "<script>alert('Data pengguna berhasil diperbarui.'); window.location='index.php?page=user';</script>";
                        exit;
                    } else {
                        $pesan = 'Gagal memperbarui data pengguna.';
                    }
                    $stmt->close();
                }
            }
            $data['nama'] = $nama;
            $data['username'] = $username;
            $data['role'] = $role;
        }
        ?>
        <h5 class="fw-bold mb-3">Edit Pengguna</h5>
        <?php if ($pesan !== '') : ?>
            <div class="alert alert-warning"><?= htmlspecialchars($pesan); ?></div>
        <?php endif; ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form action="index.php?page=user&aksi=edit&id=<?= (int)$id; ?>" method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Lengkap</label>
                        <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($data['nama']); ?>" maxlength="100" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Username</label>
                        <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($data['username']); ?>" maxlength="50" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Password Baru</label>
                        <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak ingin mengubah password">
                        <div class="form-text">Password lama tetap digunakan jika kolom ini kosong.</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold">Hak Akses</label>
                        <select name="role" class="form-select" required>
                            <option value="Admin" <?= $data['role'] === 'Admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="Kasir" <?= $data['role'] === 'Kasir' ? 'selected' : ''; ?>>Kasir</option>
                            <option value="Owner" <?= $data['role'] === 'Owner' ? 'selected' : ''; ?>>Owner</option>
                        </select>
                    </div>
                    <button type="submit" name="update" class="btn btn-success w-100 mb-2">Update Pengguna</button>
                    <a href="index.php?page=user" class="btn btn-light w-100">Batal</a>
                </form>
            </div>
        </div>
        <?php
        break;

    case 'hapus':
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id || $id < 1) {
            echo "<script>alert('ID pengguna tidak valid.'); window.location='index.php?page=user';</script>";
            exit;
        }

        if ($id == ($_SESSION['id_user'] ?? 0)) {
            echo "<script>alert('Gagal! Anda tidak dapat menghapus akun yang sedang digunakan.'); window.location='index.php?page=user';</script>";
            exit;
        }

        $stmt = $koneksi->prepare("DELETE FROM user WHERE id_user = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $pesan_hapus = 'Pengguna berhasil dihapus.';
        } else {
            $pesan_hapus = 'Pengguna gagal dihapus atau data tidak ditemukan.';
        }
        $stmt->close();

        echo "<script>alert(" . json_encode($pesan_hapus) . "); window.location='index.php?page=user';</script>";
        exit;

    default:
        echo '<div class="alert alert-warning">Aksi tidak dikenali. Silakan kembali ke daftar pengguna.</div>';
        break;
}
?>