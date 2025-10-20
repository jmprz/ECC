<?php
session_start();
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/../backend/config/db.php';


// =============== CAROUSEL CRUD =================

// ADD
if (isset($_POST['action']) && $_POST['action'] === "add_carousel") {
    $title = trim($_POST['title']);
    $link = !empty($_POST['link']) ? trim($_POST['link']) : null;

    $targetDir = "../backend/uploads/carousel/";
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

    $fileName = time() . "_" . basename($_FILES["image"]["name"]);
    $targetFilePath = $targetDir . $fileName;

    if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)) {
        $imagePath = "backend/uploads/carousel/" . $fileName;
        $stmt = $conn->prepare("INSERT INTO carousel (title, image, link) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $title, $imagePath, $link);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: admin_dashboard.php?success=carousel_added");
    exit;
}

// EDIT
if (isset($_POST['action']) && $_POST['action'] === "edit_carousel") {
    $id = intval($_POST['id']);
    $title = trim($_POST['title']);
    $link = !empty($_POST['link']) ? trim($_POST['link']) : null;

    $imagePath = $_POST['old_image']; // keep old image by default
    if (!empty($_FILES["image"]["name"])) {
        $targetDir = "../backend/uploads/carousel/";
        $fileName = time() . "_" . basename($_FILES["image"]["name"]);
        $targetFilePath = $targetDir . $fileName;
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)) {
            $imagePath = "backend/uploads/carousel/" . $fileName;
        }
    }

    $stmt = $conn->prepare("UPDATE carousel SET title=?, image=?, link=? WHERE id=?");
    $stmt->bind_param("sssi", $title, $imagePath, $link, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: admin_dashboard.php?success=carousel_updated");
    exit;
}

// DELETE
if (isset($_GET['delete_carousel'])) {
    $id = intval($_GET['delete_carousel']);
    $stmt = $conn->prepare("DELETE FROM carousel WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    header("Location: admin_dashboard.php?success=carousel_deleted");
    exit;
}



// =============== NEWS CRUD =================

// ADD
if (isset($_POST['action']) && $_POST['action'] === "add_news") {
    $title = trim($_POST['title']);
    $link = !empty($_POST['link']) ? trim($_POST['link']) : null;

    $targetDir = "../backend/uploads/news/";
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

    $imagePath = null;
    if (!empty($_FILES["image"]["name"])) {
        $fileName = time() . "_" . basename($_FILES["image"]["name"]);
        $targetFilePath = $targetDir . $fileName;
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)) {
            $imagePath = "backend/uploads/news/" . $fileName;
        }
    }

    $stmt = $conn->prepare("INSERT INTO news (title, image, link) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $title, $imagePath, $link);
    $stmt->execute();
    $stmt->close();

    header("Location: admin_dashboard.php?success=news_added");
    exit;
}

// EDIT
if (isset($_POST['action']) && $_POST['action'] === "edit_news") {
    $id = intval($_POST['id']);
    $title = trim($_POST['title']);
    $link = !empty($_POST['link']) ? trim($_POST['link']) : null;

    $imagePath = $_POST['old_image']; // keep old image
    if (!empty($_FILES["image"]["name"])) {
        $targetDir = "../backend/uploads/news/";
        $fileName = time() . "_" . basename($_FILES["image"]["name"]);
        $targetFilePath = $targetDir . $fileName;
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)) {
            $imagePath = "backend/uploads/news/" . $fileName;
        }
    }

    $stmt = $conn->prepare("UPDATE news SET title=?, image=?, link=? WHERE id=?");
    $stmt->bind_param("sssi", $title, $imagePath, $link, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: admin_dashboard.php?success=news_updated");
    exit;
}

// DELETE
if (isset($_GET['delete_news'])) {
    $id = intval($_GET['delete_news']);
    $stmt = $conn->prepare("DELETE FROM news WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    header("Location: admin_dashboard.php?success=news_deleted");
    exit;
}

// ADD USER
if (isset($_POST['action']) && $_POST['action'] === "add_user") {
    $username = trim($_POST['username']);
    $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
      $role = $_POST['role'];

    $stmt = $conn->prepare("INSERT INTO admins (username, password, role) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $password, $role);
    $stmt->execute();
    $stmt->close();

    header("Location: admin_dashboard.php?success=user_added");
    exit;
}

// =============== USERS CRUD =================

// EDIT USER
if (isset($_POST['action']) && $_POST['action'] === "edit_user") {
    $id = intval($_POST['id']);
    $username = trim($_POST['username']);
    $role = $_POST['role'];

    if (!empty($_POST['password'])) {
        $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE admins SET username=?, password=?, role=? WHERE id=?");
        $stmt->bind_param("sssi", $username, $password, $role, $id);
    } else {
        $stmt = $conn->prepare("UPDATE admins SET username=?, role=? WHERE id=?");
        $stmt->bind_param("ssi", $username, $role, $id);
    }
    $stmt->execute();
    $stmt->close();

    header("Location: admin_dashboard.php?success=user_updated");
    exit;
}

// DELETE USER
if (isset($_GET['delete_user'])) {
    $id = intval($_GET['delete_user']);
    $stmt = $conn->prepare("DELETE FROM admins WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    header("Location: admin_dashboard.php?success=user_deleted");
    exit;
}


// =============== FETCH DATA =================
$carousels = $conn->query("SELECT * FROM carousel ORDER BY id DESC");
$news = $conn->query("SELECT * FROM news ORDER BY id DESC");
$users = $conn->query("SELECT * FROM admins ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard</title>
<!-- Favicons -->
  <link rel="apple-touch-icon" sizes="180x180" href="../../assets/img/favicon/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="../../assets/img/favicon/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="../../assets/img/favicon/favicon-16x16.png">
  <link rel="manifest" href="../../assets/img/favicon/site.webmanifest">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans&family=Poppins&family=Raleway&display=swap" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="../assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="../assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
  <link href="../assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">

  <!-- Main CSS File -->
  <link href="../assets/css/main.css?v=<?php echo time(); ?>" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
  <div class="card shadow-lg border-0">
    <div class="card-body">
     <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-primary fw-bold mb-0">Admin Dashboard</h2>
        <a href="logout.php" class="btn btn-danger">Logout</a>
     </div>
      <?php if (isset($_GET['success'])): ?>
  <?php
    $alerts = [
      "carousel_added"   => "✅ Carousel added successfully!",
      "carousel_updated" => "✏️ Carousel updated successfully!",
      "carousel_deleted" => "🗑️ Carousel deleted!",
      "news_added"       => "✅ News article added successfully!",
      "news_updated"     => "✏️ News article updated successfully!",
      "news_deleted"     => "🗑️ News deleted!",
      "user_added"       => "✅ New admin added successfully!",
      "user_updated"     => "✏️ Admin updated successfully!",
      "user_deleted"     => "🗑️ Admin deleted!",
    ];
    $message = $alerts[$_GET['success']] ?? null;
  ?>
  <?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <?= $message ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>
<?php endif; ?>
      <!-- Tabs -->
      <ul class="nav nav-tabs" id="dashboardTabs" role="tablist">
        <li class="nav-item">
          <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#carouselTab" type="button">🎠 Carousel</button>
        </li>
        <li class="nav-item">
          <button class="nav-link" data-bs-toggle="tab" data-bs-target="#newsTab" type="button">📰 News</button>
        </li>
        <li class="nav-item">
          <button class="nav-link" data-bs-toggle="tab" data-bs-target="#usersTab" type="button">👤 Users</button>
        </li>
      </ul>

      <div class="tab-content mt-4">
        <!-- ================= Carousel Tab ================= -->
        <div class="tab-pane fade show active" id="carouselTab">
          <h4 class="fw-semibold">Add Carousel Item</h4>
          <form method="POST" enctype="multipart/form-data" class="row g-3 mb-4">
            <input type="hidden" name="action" value="add_carousel">
            <div class="col-md-6">
              <label class="form-label">Title</label>
              <input type="text" name="title" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Image</label>
              <input type="file" name="image" class="form-control" required>
            </div>
            <div class="col-12">
              <label class="form-label">Button Link (Optional)</label>
              <input type="url" name="link" class="form-control" placeholder="https://example.com">
            </div>
            <div class="col-12">
              <button type="submit" class="btn btn-primary">➕ Upload</button>
            </div>
          </form>

          <h5 class="fw-semibold">Carousel List</h5>
          <table class="table table-hover align-middle">
            <thead class="table-dark">
              <tr>
                <th>ID</th><th>Title</th><th>Image</th><th>Link</th><th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = $carousels->fetch_assoc()): ?>
              <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['title']) ?></td>
                <td><img src="../<?= $row['image'] ?>" class="img-thumbnail" width="100"></td>
                <td><?= $row['link'] ?: '<span class="text-muted">None</span>' ?></td>
                <td>
                  <button class="btn btn-sm btn-warning" 
                          data-bs-toggle="modal" 
                          data-bs-target="#editCarouselModal<?= $row['id'] ?>">✏️ Edit</button>
                  <a href="?delete_carousel=<?= $row['id'] ?>" 
                     class="btn btn-sm btn-danger" 
                     onclick="return confirm('Are you sure you want to delete this carousel?')">🗑️ Delete</a>
                </td>
              </tr>

              <!-- Edit Carousel Modal -->
              <div class="modal fade" id="editCarouselModal<?= $row['id'] ?>" tabindex="-1">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                  <div class="modal-content">
                    <form method="POST" enctype="multipart/form-data">
                      <div class="modal-header">
                        <h5 class="modal-title">Edit Carousel</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>
                      <div class="modal-body row g-3">
                        <input type="hidden" name="action" value="edit_carousel">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <input type="hidden" name="old_image" value="<?= $row['image'] ?>">

                        <div class="col-md-6">
                          <label class="form-label">Title</label>
                          <input type="text" name="title" value="<?= htmlspecialchars($row['title']) ?>" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                          <label class="form-label">Replace Image (optional)</label>
                          <input type="file" name="image" class="form-control">
                        </div>
                        <div class="col-12">
                          <label class="form-label">Link</label>
                          <input type="url" name="link" value="<?= $row['link'] ?>" class="form-control">
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">💾 Save Changes</button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>

        <!-- ================= News Tab ================= -->
        <div class="tab-pane fade" id="newsTab">
          <h4 class="fw-semibold">Add News Article</h4>
          <form method="POST" enctype="multipart/form-data" class="row g-3 mb-4">
            <input type="hidden" name="action" value="add_news">
            <div class="col-md-6">
              <label class="form-label">Title</label>
              <input type="text" name="title" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Image (Optional)</label>
              <input type="file" name="image" class="form-control">
            </div>
            <div class="col-12">
              <label class="form-label">Link (Optional)</label>
              <input type="url" name="link" class="form-control" placeholder="https://example.com">
            </div>
            <div class="col-12">
              <button type="submit" class="btn btn-success">📢 Publish</button>
            </div>
          </form>

          <h5 class="fw-semibold">News List</h5>
          <table class="table table-hover align-middle">
            <thead class="table-dark">
              <tr>
                <th>ID</th><th>Title</th><th>Image</th><th>Link</th><th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = $news->fetch_assoc()): ?>
              <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['title']) ?></td>
                <td><?php if ($row['image']): ?><img src="../<?= $row['image'] ?>" class="img-thumbnail" width="100"><?php endif; ?></td>
                <td><?= $row['link'] ?: '<span class="text-muted">None</span>' ?></td>
                <td>
                  <button class="btn btn-sm btn-warning" 
                          data-bs-toggle="modal" 
                          data-bs-target="#editNewsModal<?= $row['id'] ?>">✏️ Edit</button>
                  <a href="?delete_news=<?= $row['id'] ?>" 
                     class="btn btn-sm btn-danger" 
                     onclick="return confirm('Are you sure you want to delete this news?')">🗑️ Delete</a>
                </td>
              </tr>

              <!-- Edit News Modal -->
              <div class="modal fade" id="editNewsModal<?= $row['id'] ?>" tabindex="-1">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                  <div class="modal-content">
                    <form method="POST" enctype="multipart/form-data">
                      <div class="modal-header">
                        <h5 class="modal-title">Edit News</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>
                      <div class="modal-body row g-3">
                        <input type="hidden" name="action" value="edit_news">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <input type="hidden" name="old_image" value="<?= $row['image'] ?>">

                        <div class="col-md-6">
                          <label class="form-label">Title</label>
                          <input type="text" name="title" value="<?= htmlspecialchars($row['title']) ?>" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                          <label class="form-label">Replace Image (optional)</label>
                          <input type="file" name="image" class="form-control">
                        </div>
                        <div class="col-12">
                          <label class="form-label">Link</label>
                          <input type="url" name="link" value="<?= $row['link'] ?>" class="form-control">
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">💾 Save Changes</button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
        <!-- ================= Users Tab ================= -->
<div class="tab-pane fade" id="usersTab">
  <h4 class="fw-semibold">Add User</h4>
  <form method="POST" class="row g-3 mb-4">
    <input type="hidden" name="action" value="add_user">
    <div class="col-md-6">
      <label class="form-label">Username</label>
      <input type="text" name="username" class="form-control" required>
    </div>
    <div class="col-md-6">
      <label class="form-label">Password</label>
      <input type="password" name="password" class="form-control" required>
    </div>
      <div class="col-md-4">
    <label class="form-label">Role</label>
    <select name="role" class="form-select" required>
      <option value="admin">Admin</option>
      <option value="user" selected>User</option>
    </select>
  </div>
    <div class="col-12">
      <button type="submit" class="btn btn-primary">➕ Add User</button>
    </div>
  </form>

  <h5 class="fw-semibold">User List</h5>
 <table class="table table-hover align-middle">
  <thead class="table-dark">
    <tr>
      <th>ID</th>
      <th>Username</th>
      <th>Role</th>
      <th>Action</th>
    </tr>
  </thead>
  <tbody>
    <?php while ($user = $users->fetch_assoc()): ?>
    <tr>
      <td><?= $user['id'] ?></td>
      <td><?= htmlspecialchars($user['username']) ?></td>
      <td>
        <span class="badge bg-<?= $user['role'] === 'admin' ? 'primary' : 'secondary' ?>">
          <?= ucfirst($user['role']) ?>
        </span>
      </td>
      <td>
        <button class="btn btn-sm btn-warning"
                data-bs-toggle="modal"
                data-bs-target="#editUserModal<?= $user['id'] ?>">✏️ Edit</button>
        <a href="?delete_user=<?= $user['id'] ?>"
           class="btn btn-sm btn-danger"
           onclick="return confirm('Are you sure you want to delete this user?')">🗑️ Delete</a>
      </td>
    </tr>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal<?= $user['id'] ?>" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <form method="POST">
            <div class="modal-header">
              <h5 class="modal-title">Edit User</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
              <input type="hidden" name="action" value="edit_user">
              <input type="hidden" name="id" value="<?= $user['id'] ?>">
              <div class="col-12">
                <label class="form-label">Username</label>
                <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" class="form-control" required>
              </div>
              <div class="col-12">
                <label class="form-label">New Password (leave blank to keep current)</label>
                <input type="password" name="password" class="form-control">
              </div>
              <div class="col-12">
                <label class="form-label">Role</label>
                <select name="role" class="form-select">
                  <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                  <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
                </select>
              </div>
            </div>
            <div class="modal-footer">
              <button type="submit" class="btn btn-primary">💾 Save Changes</button>
            </div>
          </form>
        </div>
      </div>
      </div>
      <?php endwhile; ?>
      </tbody>
      </table>
      </div>
      </div>
    </div>
  </div>
</div>


<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
