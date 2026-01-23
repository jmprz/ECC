<?php
session_start();
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/../backend/config/db.php';

// =============== SECURE FILE UPLOAD FUNCTION =================
function uploadSecureImage($file, $targetSubDir)
{
    $targetDir = "../backend/uploads/" . $targetSubDir . "/";
    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
    $maxFileSize = 5 * 1024 * 1024; // 5MB

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload error code: ' . $file['error']];
    }

    if ($file['size'] > $maxFileSize) {
        return ['success' => false, 'error' => 'File is too large (Max 5MB).'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    if (!in_array($mimeType, $allowedMimeTypes)) {
        return ['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, and WEBP allowed.'];
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $safeName = bin2hex(random_bytes(16)) . '.' . $extension;
    $targetFilePath = $targetDir . $safeName;

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
        return [
            'success' => true,
            'path' => "backend/uploads/" . $targetSubDir . "/" . $safeName
        ];
    }
    return ['success' => false, 'error' => 'Failed to move uploaded file.'];
}

// =============== CSRF VALIDATION =================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed. Possible cross-site request detected.");
    }
}

if (isset($_GET['delete_carousel']) || isset($_GET['delete_news']) || isset($_GET['delete_user']) || isset($_GET['delete_faq'])) {
    if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed for deletion.");
    }
}

// =============== 1. UPDATE CONTENT (Carousel/News) =================
if (isset($_POST['action']) && $_POST['action'] === "update_content") {
    $id = intval($_POST['id']);
    $title = trim($_POST['title']);
    $link = !empty($_POST['link']) ? trim($_POST['link']) : null;
    $status = $_POST['status'];
    $tableName = ($_POST['table_name'] === 'news') ? 'news' : 'carousel';

    $stmt = $conn->prepare("SELECT status FROM $tableName WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $check = $stmt->get_result()->fetch_assoc();

    if ($check['status'] === 'posted') {
        header("Location: admin_dashboard.php?error=already_posted");
        exit;
    }

    if (!empty($_FILES["image"]["name"])) {
        $uploadResult = uploadSecureImage($_FILES["image"], $tableName);
        if ($uploadResult['success']) {
            $imagePath = $uploadResult['path'];
            $stmt = $conn->prepare("UPDATE $tableName SET title=?, image=?, link=?, status=? WHERE id=?");
            $stmt->bind_param("ssssi", $title, $imagePath, $link, $status, $id);
        } else {
            header("Location: admin_dashboard.php?error=" . urlencode($uploadResult['error']));
            exit;
        }
    } else {
        $stmt = $conn->prepare("UPDATE $tableName SET title=?, link=?, status=? WHERE id=?");
        $stmt->bind_param("sssi", $title, $link, $status, $id);
    }
    $stmt->execute();
    header("Location: admin_dashboard.php?success=updated");
    exit;
}

// =============== 2. ADD CONTENT (Carousel/News) =================
if (isset($_POST['action']) && $_POST['action'] === "add_content") {
    $title = trim($_POST['title']);
    $link = !empty($_POST['link']) ? trim($_POST['link']) : null;
    $contentType = ($_POST['content_type'] === 'news') ? 'news' : 'carousel';
    $status = $_POST['status'] ?? 'draft';

    if (!empty($_FILES["image"]["name"])) {
        $uploadResult = uploadSecureImage($_FILES["image"], $contentType);
        if ($uploadResult['success']) {
            $imagePath = $uploadResult['path'];
            $stmt = $conn->prepare("INSERT INTO $contentType (title, image, link, status) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $title, $imagePath, $link, $status);
            $stmt->execute();
            header("Location: admin_dashboard.php?success={$contentType}_added");
            exit;
        } else {
            header("Location: admin_dashboard.php?error=" . urlencode($uploadResult['error']));
            exit;
        }
    } else {
        header("Location: admin_dashboard.php?error=image_required");
        exit;
    }
}

// =============== 3. DELETE LOGIC (Prepared) =================
if (isset($_GET['delete_carousel'])) {
    $stmt = $conn->prepare("DELETE FROM carousel WHERE id = ?");
    $stmt->bind_param("i", $_GET['delete_carousel']);
    $stmt->execute();
    header("Location: admin_dashboard.php?success=deleted");
    exit;
}
if (isset($_GET['delete_news'])) {
    $stmt = $conn->prepare("DELETE FROM news WHERE id = ?");
    $stmt->bind_param("i", $_GET['delete_news']);
    $stmt->execute();
    header("Location: admin_dashboard.php?success=deleted");
    exit;
}
if (isset($_GET['delete_user'])) {
    $stmt = $conn->prepare("DELETE FROM admins WHERE id = ?");
    $stmt->bind_param("i", $_GET['delete_user']);
    $stmt->execute();
    header("Location: admin_dashboard.php?success=deleted");
    exit;
}
if (isset($_GET['delete_faq'])) {
    $stmt = $conn->prepare("DELETE FROM faq WHERE id = ?");
    $stmt->bind_param("i", $_GET['delete_faq']);
    $stmt->execute();
    header("Location: admin_dashboard.php?success=deleted");
    exit;
}

// =============== 4. USER LOGIC =================
if (isset($_POST['action']) && $_POST['action'] === "update_user") {
    $id = intval($_POST['user_id']);
    $username = trim($_POST['username']);
    $role = $_POST['role'];

    if (!empty($_POST['new_password'])) {
        $hashed_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE admins SET username=?, password=?, role=? WHERE id=?");
        $stmt->bind_param("sssi", $username, $hashed_password, $role, $id);
    } else {
        $stmt = $conn->prepare("UPDATE admins SET username=?, role=? WHERE id=?");
        $stmt->bind_param("ssi", $username, $role, $id);
    }
    $stmt->execute();
    header("Location: admin_dashboard.php?success=user_updated");
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === "add_user") {
    $username = trim($_POST['username']);
    $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $stmt = $conn->prepare("INSERT INTO admins (username, password, role) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $password, $role);
    $stmt->execute();
    header("Location: admin_dashboard.php?success=user_added");
    exit;
}

// =============== 5. FAQ LOGIC =================
if (isset($_POST['action']) && $_POST['action'] === "update_faq") {
    $id = intval($_POST['id']);
    $question = trim($_POST['question']);
    $answer = trim($_POST['answer']);
    $status = $_POST['status'];

    $stmt = $conn->prepare("SELECT status FROM faq WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $check = $stmt->get_result()->fetch_assoc();

    if ($check['status'] === 'posted') {
        header("Location: admin_dashboard.php?error=already_posted");
        exit;
    }
    $stmt = $conn->prepare("UPDATE faq SET question=?, answer=?, status=? WHERE id=?");
    $stmt->bind_param("sssi", $question, $answer, $status, $id);
    $stmt->execute();
    header("Location: admin_dashboard.php?success=faq_updated");
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === "add_faq") {
    $question = trim($_POST['question']);
    $answer = trim($_POST['answer']);
    $status = $_POST['status'] ?? 'draft';
    $stmt = $conn->prepare("INSERT INTO faq (question, answer, status) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $question, $answer, $status);
    $stmt->execute();
    header("Location: admin_dashboard.php?success=faq_added");
    exit;
}

// =============== FETCH DATA =================
$content = [];
$carouselResult = $conn->query("SELECT *, 'carousel' as type FROM carousel ORDER BY id DESC");
while ($row = $carouselResult->fetch_assoc()) {
    $content[] = $row;
}
$newsResult = $conn->query("SELECT *, 'news' as type FROM news ORDER BY id DESC");
while ($row = $newsResult->fetch_assoc()) {
    $content[] = $row;
}

$users = $conn->query("SELECT * FROM admins ORDER BY id DESC");
$faqs = $conn->query("SELECT * FROM faq ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | EARIST - Cavite Campus</title>

    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/main.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- Favicons -->
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/img/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/img/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/img/favicon/favicon-16x16.png">
    <link rel="manifest" href="../assets/img/favicon/site.webmanifest">

    <style>
        :root {
            --primary-color: #cc2e28;
            --bg-light: #f8f9fa;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-light);
            color: #333;
        }

        .card {
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .nav-tabs .nav-link {
            color: #6c757d !important;
            font-weight: 600;
            border: none !important;
            padding: 1rem 1.5rem;
        }

        .nav-tabs .nav-link.active {
            color: var(--primary-color) !important;
            border-bottom: 3px solid var(--primary-color) !important;
            background: none !important;
        }

        .img-thumb-container {
            width: 80px;
            height: 50px;
            overflow: hidden;
            border-radius: 6px;
            border: 1px solid #eee;
        }

        .img-thumb-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .badge {
            font-weight: 600;
            padding: 0.4em 0.8em;
            border-radius: 6px;
        }

        /* Pagination Styling */
        .page-item.active .page-link {
            background-color: #cc2e28 !important;
            border-color: #cc2e28 !important;
            color: white !important;
        }

        .page-link {
            color: #cc2e28 !important;
            border-radius: 6px;
            margin: 0 2px;
        }

        .page-link:hover {
            background-color: #f8f9fa !important;
            color: #800000 !important;
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4 shadow-sm">
        <div class="container">
            <a class="fs-1 fw-bold" href="#">ECC Dashboard</a>
            <a href="logout.php" class="btn btn-sm btn-news rounded-3"><i
                    class="bi bi-box-arrow-right me-2"></i>Logout</a>
        </div>
    </nav>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?php
            if ($_GET['error'] == 'image_required')
                echo "Error: You must upload an image.";
            else if ($_GET['error'] == 'upload_failed')
                echo "Error: Image upload failed. Check folder permissions.";
            else
                echo "An unexpected error occurred.";
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <div class="container pb-5">
        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-white p-2">
                    <ul class="nav nav-tabs" id="mainTabs" role="tablist">
                        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab"
                                data-bs-target="#contentTab">Content</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab"
                                data-bs-target="#usersTab">User Access</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab"
                                data-bs-target="#faqTab">FAQs</button></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="contentTab">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="fw-bold mb-4">Create New Content</h5>
                        <form method="POST" enctype="multipart/form-data" class="row g-3">
                            <input type="hidden" name="action" value="add_content">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                            <div class="col-md-3">
                                <label class="form-label fw-bold small">Category</label>
                                <select name="content_type" id="contentType" class="form-select">
                                    <option value="carousel">Events</option>
                                    <option value="news">News</option>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-bold small">Headline / Title</label>
                                <input type="text" name="title" class="form-control" placeholder="Enter title..."
                                    required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">Action Link</label>
                                <input type="url" name="link" class="form-control" placeholder="https://...">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label fw-bold small" id="imageLabel">Image Media <span
                                        class="text-danger">*</span></label>
                                <input type="file" name="image" id="imageInput" class="form-control" required
                                    accept="image/*">
                                <div class="form-text">An image is required for both events and news.</div>
                            </div>
                            <div class="col-md-4 d-flex align-items-end gap-2 mb-4">
                                <button type="submit" name="status" value="draft" class="btn btn-news w-100 fw-bold"><i
                                        class="bi bi-floppy2-fill me-1"></i> Save</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <table class="table align-middle datatable w-100">
                            <thead>
                                <tr>
                                    <th>Media</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Headline</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($content as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="img-thumb-container">
                                                <?php if ($item['image']): ?>
                                                    <img src="../<?= $item['image'] ?>" alt="content">
                                                <?php else: ?>
                                                    <div
                                                        class="bg-light d-flex align-items-center justify-content-center h-100">
                                                        <i class="bi bi-image text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td><span
                                                class="badge bg-<?= $item['type'] === 'carousel' ? 'primary text-white' : 'warning text-white' ?>"><?= strtoupper($item['type']) ?></span>
                                        </td>
                                        <td>
                                            <span
                                                class="badge <?= ($item['status'] ?? 'posted') === 'posted' ? 'bg-success text-white' : 'bg-secondary text-white' ?>">
                                                <?= ucfirst($item['status'] ?? 'posted') ?>
                                            </span>
                                        </td>
                                        <td class="fw-semibold text-dark"><?= htmlspecialchars($item['title']) ?></td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-sm btn-action-edit" data-id="<?= $item['id'] ?>"
                                                    data-title="<?= htmlspecialchars($item['title']) ?>"
                                                    data-link="<?= $item['link'] ?>" data-status="<?= $item['status'] ?>"
                                                    data-type="<?= $item['type'] ?>" data-bs-toggle="modal"
                                                    data-bs-target="#editContentModal" <?= ($item['status'] === 'posted') ? 'disabled title="Posted items are locked"' : 'title="Edit Draft"' ?>>
                                                    <i class="bi bi-pencil-fill"></i>
                                                </button>

                                                <a href="?delete_<?= $item['type'] ?>=<?= $item['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>"
                                                    class="btn btn-sm btn-action-delete" title="Delete Permanently"
                                                    onclick="return confirm('Are you sure you want to delete this?')">
                                                    <i class="bi bi-trash3-fill"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>


            <div class="tab-pane fade" id="usersTab">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="fw-bold mb-3">Add Administrator</h5>
                                <form method="POST">
                                    <input type="hidden" name="action" value="add_user">
                                    <input type="hidden" name="csrf_token"
                                        value="<?php echo $_SESSION['csrf_token']; ?>">

                                    <div class="mb-3">
                                        <label class="form-label small">Username</label>
                                        <input type="text" name="username" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small">Password</label>
                                        <input type="password" name="password" class="form-control" required>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label small">Role</label>
                                        <select name="role" class="form-select">
                                            <option value="admin">Admin</option>
                                            <option value="user">User</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-news w-100">Create Account</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card p-3">
                            <table class="table datatable w-100">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Role</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($user = $users->fetch_assoc()): ?>
                                        <tr>
                                            <td class="fw-bold"><?= htmlspecialchars($user['username']) ?></td>
                                            <td><span
                                                    class="badge <?= $user['role'] === 'admin' ? 'bg-danger' : 'bg-info' ?>"><?= $user['role'] ?></span>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <button class="btn btn-sm btn-action-edit" data-id="<?= $user['id'] ?>"
                                                        data-username="<?= htmlspecialchars($user['username']) ?>"
                                                        data-role="<?= $user['role'] ?>" data-bs-toggle="modal"
                                                        data-bs-target="#editUserModal" title="Manage Permissions">
                                                        <i class="bi bi-shield-lock-fill"></i>
                                                    </button>

                                                    <a href="?delete_user=<?= $user['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>"
                                                        class="btn btn-sm btn-action-delete"
                                                        onclick="return confirm('Remove administrator?')">
                                                        <i class="bi bi-person-x-fill"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="faqTab">
                <div class="card p-4">
                    <h5 class="fw-bold mb-4">Manage FAQ</h5>
                    <form method="POST" class="row g-3 mb-5">
                        <input type="hidden" name="action" value="add_faq">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                        <div class="col-md-12">
                            <input type="text" name="question" class="form-control" placeholder="Question..." required>
                        </div>
                        <div class="col-md-9">
                            <textarea name="answer" class="form-control" placeholder="Answer..." rows="2"
                                required></textarea>
                        </div>
                        <div class="col-md-3 d-flex align-items-stretch gap-2">
                            <button type="submit" name="status" value="draft" class="btn btn-news w-100 fw-bold"><i
                                    class="bi bi-floppy2-fill me-1"></i> Save</button>
                        </div>
                    </form>
                    <table class="table datatable align-middle w-100">
                        <thead>
                            <tr>
                                <th style="width: 25%;">Question</th>
                                <th style="width: 40%;">Answer Preview</th>
                                <th style="width: 15%;">Status</th>
                                <th style="width: 20%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($faq = $faqs->fetch_assoc()): ?>
                                <tr>
                                    <td class="small fw-bold text-dark"><?= htmlspecialchars($faq['question']) ?></td>

                                    <td class="small text-muted">
                                        <div style="max-width: 350px;" class="text-truncate">
                                            <?= htmlspecialchars($faq['answer']) ?>
                                        </div>
                                    </td>

                                    <td>
                                        <span
                                            class="badge bg-<?= ($faq['status'] ?? 'posted') === 'posted' ? 'success' : 'secondary' ?> text-white">
                                            <?= ucfirst($faq['status'] ?? 'posted') ?>
                                        </span>
                                    </td>

                                    <td>
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-sm btn-action-faq-edit edit-faq-btn"
                                                data-id="<?= $faq['id'] ?>"
                                                data-question="<?= htmlspecialchars($faq['question']) ?>"
                                                data-answer="<?= htmlspecialchars($faq['answer']) ?>"
                                                data-status="<?= $faq['status'] ?>" data-bs-toggle="modal"
                                                data-bs-target="#editFaqModal" <?= ($faq['status'] === 'posted') ? 'disabled title="Posted FAQs are locked"' : 'title="Edit FAQ"' ?>>
                                                <i class="bi bi-pencil-fill"></i>
                                            </button>

                                            <a href="?delete_faq=<?= $faq['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>"
                                                class="btn btn-sm btn-action-delete" title="Delete FAQ"
                                                onclick="return confirm('Remove this FAQ permanently?')">
                                                <i class="bi bi-trash3-fill"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editContentModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" enctype="multipart/form-data" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit <span id="edit-type-label"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                    <input type="hidden" name="action" value="update_content">
                    <input type="hidden" name="id" id="edit-id">
                    <input type="hidden" name="table_name" id="edit-table-name">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">

                    <div class="col-12">
                        <label class="form-label small fw-bold">Headline / Title</label>
                        <input type="text" name="title" id="edit-title" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">Action Link</label>
                        <input type="url" name="link" id="edit-link" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold d-block">Status</label>
                        <div class="form-check">
                            <input type="hidden" name="status" value="draft">
                            <input class="form-check-input" type="checkbox" name="status" value="posted"
                                id="edit-status-check">
                            <label class="form-check-label" for="edit-status-check" id="status-label">
                                Draft
                            </label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">Replace Image (Leave blank to keep current)</label>
                        <input type="file" name="image" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-news">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="editFaqModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit FAQ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                    <input type="hidden" name="action" value="update_faq">
                    <input type="hidden" name="id" id="edit-faq-id">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">

                    <div class="col-12">
                        <label class="form-label small fw-bold">Question</label>
                        <input type="text" name="question" id="edit-faq-question" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">Answer</label>
                        <textarea name="answer" id="edit-faq-answer" class="form-control" rows="4" required></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold d-block">Status</label>
                        <div class="form-check">
                            <input type="hidden" name="status" value="draft">
                            <input class="form-check-input custom-checkbox" type="checkbox" name="status" value="posted"
                                id="edit-faq-status-check">
                            <label class="form-check-label" for="edit-faq-status-check" id="faq-status-label">
                                Draft
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-news">Update FAQ</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit User/Admin</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_user">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Username</label>
                            <input type="text" name="username" id="edit_username" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">New Password (leave blank to keep current)</label>
                            <input type="password" name="new_password" class="form-control" placeholder="••••••••">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Role</label>
                            <select name="role" id="edit_role" class="form-select">
                                <option value="admin">Admin</option>
                                <option value="user">User</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"
                            style="background-color: #cc2e28; border: none;">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="liveToast" class="toast align-items-center text-white border-0" role="alert" aria-live="assertive"
            aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body" id="toastMessage">
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                    aria-label="Close"></button>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function () {
            // 1. PERSIST ACTIVE TAB AFTER RELOAD
            let activeTab = localStorage.getItem('activeAdminTab');
            if (activeTab) {
                $(`#mainTabs button[data-bs-target="${activeTab}"]`).tab('show');
            }

            $('#mainTabs button').on('shown.bs.tab', function (e) {
                localStorage.setItem('activeAdminTab', $(e.target).data('bs-target'));
            });

            // 2. DATA POPULATION FOR MODALS

            // Content Hub Edit (Carousel/News)
            $('.btn-action-edit').on('click', function () {
                $('#edit-id').val($(this).data('id'));
                $('#edit-title').val($(this).data('title'));
                $('#edit-link').val($(this).data('link'));
                $('#edit-table-name').val($(this).data('type'));

                // Correct Checkbox Logic
                let status = $(this).data('status');
                if (status === 'posted') {
                    $('#edit-status-check').prop('checked', true); // SET TO CHECKED
                    $('#status-label').text('Posted');
                } else {
                    $('#edit-status-check').prop('checked', false); // SET TO UNCHECKED
                    $('#status-label').text('Draft');
                }
            });

            // CHANGE LABEL MANUALLY WHEN CHECKBOX IS CLICKED
            // Move this outside the click function so it only runs once
            $('#edit-status-check').on('change', function () {
                $('#status-label').text(this.checked ? 'Posted' : 'Draft');
            });

            // FAQ Edit
            $('.edit-faq-btn').on('click', function () {
                $('#edit-faq-id').val($(this).data('id'));
                $('#edit-faq-question').val($(this).data('question'));
                $('#edit-faq-answer').val($(this).data('answer'));

                // Checkbox Logic for FAQ
                let status = $(this).data('status'); // Expecting 'posted' or 'draft'
                if (status === 'posted') {
                    $('#edit-faq-status-check').prop('checked', true);
                    $('#faq-status-label').text('Posted');
                } else {
                    $('#edit-faq-status-check').prop('checked', false);
                    $('#faq-status-label').text('Draft');
                }
            });

            // Update label text dynamically when the user clicks the checkbox
            $('#edit-faq-status-check').on('change', function () {
                $('#faq-status-label').text(this.checked ? 'Posted' : 'Draft');
            });

            // User Edit
            $('.btn-action-edit[data-bs-target="#editUserModal"]').on('click', function () {
                $('#edit_user_id').val($(this).data('id'));
                $('#edit_username').val($(this).data('username'));
                $('#edit_role').val($(this).data('role'));
                $('#display-username').text($(this).data('username'));
            });

            // 3. INITIALIZE TOOLTIPS
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });
        });

        document.addEventListener('DOMContentLoaded', function () {
            const urlParams = new URLSearchParams(window.location.search);
            const toastEl = document.getElementById('liveToast');
            const toastMessage = document.getElementById('toastMessage');
            const toast = new bootstrap.Toast(toastEl);

            let message = "";
            let bgColor = "";

            // Check for Success Messages
            if (urlParams.has('success')) {
                const type = urlParams.get('success');
                bgColor = "bg-success";

                const messages = {
                    'updated': 'Content updated successfully!',
                    'deleted': 'Item removed permanently.',
                    'user_added': 'New administrator created.',
                    'faq_added': 'FAQ added to the database.',
                    'carousel_added': 'Carousel event added.',
                    'news_added': 'News article published.'
                };
                message = messages[type] || "Action completed successfully!";
            }

            // Check for Error Messages
            if (urlParams.has('error')) {
                const errType = urlParams.get('error');
                bgColor = "bg-danger";

                const errors = {
                    'image_required': 'Error: You must upload an image.',
                    'upload_failed': 'Error: File upload failed.',
                    'already_posted': 'Action Locked: Posted items cannot be edited.',
                    'invalid_file_type': 'Error: Only JPG, PNG, and WEBP allowed.'
                };
                message = errors[errType] || decodeURIComponent(errType);
            }

            // If we have a message, show the toast
            if (message) {
                toastEl.classList.add(bgColor);
                toastMessage.textContent = message;
                toast.show();

                // Optional: Clean the URL after showing the toast so it doesn't pop up again on refresh
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        });
    </script>
</body>

</html>