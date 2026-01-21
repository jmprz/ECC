<?php
session_start();
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/../backend/config/db.php';

// =============== UPDATE LOGIC =================

// 1. UPDATE CONTENT (Carousel/News)
if (isset($_POST['action']) && $_POST['action'] === "update_content") {
    $id = intval($_POST['id']);
    $title = trim($_POST['title']);
    $link = !empty($_POST['link']) ? trim($_POST['link']) : null;
    $status = $_POST['status'];
    $tableName = $_POST['table_name']; // 'carousel' or 'news'
    $check = $conn->query("SELECT status FROM $tableName WHERE id=$id")->fetch_assoc();
    if ($check['status'] === 'posted') {
        header("Location: admin_dashboard.php?error=already_posted");
        exit;
    }
    if (!empty($_FILES["image"]["name"])) {
        $targetDir = "../backend/uploads/$tableName/";
        $fileName = time() . "_" . basename($_FILES["image"]["name"]);
        $targetFilePath = $targetDir . $fileName;
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)) {
            $imagePath = "backend/uploads/$tableName/" . $fileName;
            $stmt = $conn->prepare("UPDATE $tableName SET title=?, image=?, link=?, status=? WHERE id=?");
            $stmt->bind_param("ssssi", $title, $imagePath, $link, $status, $id);
        }
    } else {
        $stmt = $conn->prepare("UPDATE $tableName SET title=?, link=?, status=? WHERE id=?");
        $stmt->bind_param("sssi", $title, $link, $status, $id);
    }
    $stmt->execute();
    header("Location: admin_dashboard.php?success=updated");
    exit;
}

// 2. UPDATE FAQ
if (isset($_POST['action']) && $_POST['action'] === "update_faq") {
    $id = intval($_POST['id']);
    $question = trim($_POST['question']);
    $answer = trim($_POST['answer']);
    $status = $_POST['status'];

    $check = $conn->query("SELECT status FROM faq WHERE id=$id")->fetch_assoc();
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

// =============== ORIGINAL ADD/DELETE LOGIC =================

if (isset($_POST['action']) && $_POST['action'] === "add_content") {
    $title = trim($_POST['title']);
    $link = !empty($_POST['link']) ? trim($_POST['link']) : null;
    $contentType = $_POST['content_type'];
    $status = $_POST['status'] ?? 'draft';

    $tableName = ($contentType === 'carousel') ? 'carousel' : 'news';
    $targetDir = "../backend/uploads/$tableName/";

    if (!is_dir($targetDir))
        mkdir($targetDir, 0777, true);

    $imagePath = null;

    // STRICT IMAGE CHECK
    if (isset($_FILES["image"]) && !empty($_FILES["image"]["name"])) {
        $fileName = time() . "_" . basename($_FILES["image"]["name"]);
        $targetFilePath = $targetDir . $fileName;

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)) {
            $imagePath = "backend/uploads/$tableName/" . $fileName;
        } else {
            // Error if upload fails
            header("Location: admin_dashboard.php?error=upload_failed");
            exit;
        }
    } else {
        // ERROR: No image was uploaded
        header("Location: admin_dashboard.php?error=image_required");
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO $tableName (title, image, link, status) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $title, $imagePath, $link, $status);
    $stmt->execute();

    header("Location: admin_dashboard.php?success={$contentType}_added");
    exit;
}

if (isset($_GET['delete_carousel'])) {
    $id = intval($_GET['delete_carousel']);
    $conn->query("DELETE FROM carousel WHERE id=$id");
    header("Location: admin_dashboard.php?success=deleted");
    exit;
}
if (isset($_GET['delete_news'])) {
    $id = intval($_GET['delete_news']);
    $conn->query("DELETE FROM news WHERE id=$id");
    header("Location: admin_dashboard.php?success=deleted");
    exit;
}
if (isset($_GET['delete_user'])) {
    $id = intval($_GET['delete_user']);
    $conn->query("DELETE FROM admins WHERE id=$id");
    header("Location: admin_dashboard.php?success=deleted");
    exit;
}
if (isset($_GET['delete_faq'])) {
    $id = intval($_GET['delete_faq']);
    $conn->query("DELETE FROM faq WHERE id=$id");
    header("Location: admin_dashboard.php?success=deleted");
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === "update_user") {
    $id = intval($_POST['user_id']);
    $username = trim($_POST['username']); // Get new username
    $new_password = $_POST['new_password'];
    $role = $_POST['role'];

    if (!empty($new_password)) {
        // Update username, password, and role
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE admins SET username=?, password=?, role=? WHERE id=?");
        $stmt->bind_param("sssi", $username, $hashed_password, $role, $id);
    } else {
        // Update username and role only
        $stmt = $conn->prepare("UPDATE admins SET username=?, role=? WHERE id=?");
        $stmt->bind_param("ssi", $username, $role, $id);
    }

    if ($stmt->execute()) {
        header("Location: admin_dashboard.php?success=user_updated");
    } else {
        header("Location: admin_dashboard.php?error=update_failed");
    }
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
                            <div class="col-md-4 d-flex align-items-end gap-2">
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

                                                <a href="?delete_<?= $item['type'] ?>=<?= $item['id'] ?>"
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

                                                    <a href="?delete_user=<?= $user['id'] ?>"
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
                                            class="badge bg-<?= ($faq['status'] ?? 'posted') === 'posted' ? 'success' : 'secondary' ?> text-white<?= ($faq['status'] ?? 'posted') === 'posted' ? 'success' : 'secondary' ?>">
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

                                            <a href="?delete_faq=<?= $faq['id'] ?>" class="btn btn-sm btn-action-delete"
                                                title="Delete FAQ" onclick="return confirm('Remove this FAQ permanently?')">
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
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
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
    </script>
</body>

</html>