<?php
session_start();
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/../backend/config/db.php';

// =============== CONTENT CRUD =================

// ADD CONTENT
if (isset($_POST['action']) && $_POST['action'] === "add_content") {
    $title = trim($_POST['title']);
    $link = !empty($_POST['link']) ? trim($_POST['link']) : null;
    $contentType = $_POST['content_type'];

    $isCarousel = $contentType === 'carousel';
    $tableName = $isCarousel ? 'carousel' : 'news';
    $targetDir = "../backend/uploads/$tableName/";
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

    $imagePath = null;
    if (!empty($_FILES["image"]["name"])) {
        $fileName = time() . "_" . basename($_FILES["image"]["name"]);
        $targetFilePath = $targetDir . $fileName;
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)) {
            $imagePath = "backend/uploads/$tableName/" . $fileName;
        }
    }

    if ($isCarousel && !$imagePath) {
        header("Location: admin_dashboard.php?error=carousel_image_required");
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO $tableName (title, image, link) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $title, $imagePath, $link);
    $stmt->execute();
    $stmt->close();

    header("Location: admin_dashboard.php?success={$contentType}_added");
    exit;
}

// DELETE (CAROUSEL/NEWS/USER/FAQ) - Condensed logic
if (isset($_GET['delete_carousel'])) {
    $id = intval($_GET['delete_carousel']);
    $conn->query("DELETE FROM carousel WHERE id=$id");
    header("Location: admin_dashboard.php?success=deleted"); exit;
}
if (isset($_GET['delete_news'])) {
    $id = intval($_GET['delete_news']);
    $conn->query("DELETE FROM news WHERE id=$id");
    header("Location: admin_dashboard.php?success=deleted"); exit;
}
if (isset($_GET['delete_user'])) {
    $id = intval($_GET['delete_user']);
    $conn->query("DELETE FROM admins WHERE id=$id");
    header("Location: admin_dashboard.php?success=deleted"); exit;
}
if (isset($_GET['delete_faq'])) {
    $id = intval($_GET['delete_faq']);
    $conn->query("DELETE FROM faq WHERE id=$id");
    header("Location: admin_dashboard.php?success=deleted"); exit;
}

// ADD USER
if (isset($_POST['action']) && $_POST['action'] === "add_user") {
    $username = trim($_POST['username']);
    $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $stmt = $conn->prepare("INSERT INTO admins (username, password, role) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $password, $role);
    $stmt->execute();
    header("Location: admin_dashboard.php?success=user_added"); exit;
}

// ADD FAQ
if (isset($_POST['action']) && $_POST['action'] === "add_faq") {
    $question = trim($_POST['question']);
    $answer = trim($_POST['answer']);
    $stmt = $conn->prepare("INSERT INTO faq (question, answer) VALUES (?, ?)");
    $stmt->bind_param("ss", $question, $answer);
    $stmt->execute();
    header("Location: admin_dashboard.php?success=faq_added"); exit;
}

// =============== FETCH DATA =================
$content = [];
$carouselResult = $conn->query("SELECT *, 'carousel' as type FROM carousel ORDER BY id DESC");
while ($row = $carouselResult->fetch_assoc()) { $content[] = $row; }
$newsResult = $conn->query("SELECT *, 'news' as type FROM news ORDER BY id DESC");
while ($row = $newsResult->fetch_assoc()) { $content[] = $row; }

$users = $conn->query("SELECT * FROM admins ORDER BY id DESC");
$faqs = $conn->query("SELECT * FROM faq ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EARIST Admin Panel</title>
    
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/main.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        :root { --primary-color: #cc2e28; --bg-light: #f8f9fa; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-light); color: #333; }
        
        .card { border-radius: 12px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .nav-tabs { border-bottom: 2px solid #dee2e6; }
        .nav-link { font-weight: 600; color: #6c757d; border: none !important; padding: 1rem 1.5rem; }
        .nav-link.active { color: var(--primary-color) !important; border-bottom: 3px solid var(--primary-color) !important; background: none !important; }
        
        /* Table Styling */
        .table { background: white; border-radius: 8px; overflow: hidden; }
        .table thead { background-color: #f1f4f8; }
        .table thead th { font-weight: 700; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; color: #555; border: none; }
        .table td { vertical-align: middle; border-bottom: 1px solid #f1f1f1; }
        
        /* Image Thumbnail */
        .img-thumb-container { width: 80px; height: 50px; overflow: hidden; border-radius: 6px; border: 1px solid #eee; }
        .img-thumb-container img { width: 100%; height: 100%; object-fit: cover; }
        
        .badge { font-weight: 600; padding: 0.4em 0.8em; border-radius: 6px; }
        .btn-action { border-radius: 8px; padding: 0.4rem 0.6rem; }
        
        .form-control, .form-select { border-radius: 8px; padding: 0.6rem 1rem; border: 1px solid #ced4da; }
        .form-control:focus { box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15); }
      
        /* Target the navigation links specifically */
    .nav-tabs .nav-link {
        color: #6c757d !important; /* Default state */
    }

    .nav-tabs .nav-link.active {
        color: var(--primary-color) !important;
        border-bottom: 3px solid var(--primary-color) !important;
    }

    .nav-tabs .nav-link:hover {
        color: var(--primary-color) !important;
    }


    /* 1. Change the color of the active page number */
.page-item.active .page-link {
    background-color: #cc2e28 !important; /* Your brand color */
    border-color: #cc2e28 !important;
    color: white !important;
}

/* 2. Change the color of the text for inactive buttons */
.page-link {
    color: #cc2e28 !important; /* Your brand color */
    border-radius: 6px;
    margin: 0 2px;
}

/* 3. Change the hover effect */
.page-link:hover {
    background-color: #f8f9fa !important;
    color: rgb(100, 19, 19) !important; /* Darker shade for hover */
    border-color: #dee2e6 !important;
}

/* 4. Fix the focus/outline shadow color */
.page-link:focus {
    box-shadow: 0 0 0 0.25rem rgba(128, 0, 0, 0.25) !important;
}
   </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-light mb-4 shadow-sm">
    <div class="container">
        <a class="fs-1 fw-bold" href="#">ECC Admin</span></a>
        <a href="logout.php" class="btn btn-sm btn-news rounded-3"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
    </div>
</nav>

<div class="container pb-5">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-white p-2">
                <ul class="nav nav-tabs" id="mainTabs" role="tablist">
                    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#contentTab">Content Hub</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#usersTab">User Access</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#faqTab">FAQs</button></li>
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
                                <option value="carousel">Carousel Slider</option>
                                <option value="news">Latest News</option>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-bold small">Headline / Title</label>
                            <input type="text" name="title" class="form-control" placeholder="Enter title..." required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Action Link</label>
                            <input type="url" name="link" class="form-control" placeholder="https://...">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-bold small" id="imageLabel">Image Media</label>
                            <input type="file" name="image" id="imageInput" class="form-control">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-news w-100 fw-bold">Publish Content</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <table class="table align-middle datatable w-100">
                        <thead>
                            <tr>
                                <th style="width: 50px;">ID</th>
                                <th style="width: 100px;">Media</th>
                                <th style="width: 100px;">Type</th>
                                <th>Headline</th>
                                <th style="width: 150px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($content as $item): ?>
                            <tr>
                                <td class="text-muted"><?= $item['id'] ?></td>
                                <td>
                                    <div class="img-thumb-container">
                                        <?php if($item['image']): ?>
                                            <img src="../<?= $item['image'] ?>" alt="content">
                                        <?php else: ?>
                                            <div class="bg-light d-flex align-items-center justify-content-center h-100"><i class="bi bi-image text-muted"></i></div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><span class="badge bg-<?= $item['type'] === 'carousel' ? 'primary-subtle text-primary' : 'success-subtle text-success' ?>"><?= strtoupper($item['type']) ?></span></td>
                                <td class="fw-semibold text-dark"><?= htmlspecialchars($item['title']) ?></td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-light btn-action btn-sm border" title="Edit"><i class="bi bi-pencil-square"></i></button>
                                        <a href="?delete_<?= $item['type'] ?>=<?= $item['id'] ?>" class="btn btn-danger-subtle text-danger btn-action btn-sm" onclick="return confirm('Delete permanently?')"><i class="bi bi-trash"></i></a>
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
                                        <option value="admin">Admin (Full Access)</option>
                                        <option value="user">Staff (View Only)</option>
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
                                    <td><span class="badge <?= $user['role'] === 'admin' ? 'bg-dark' : 'bg-secondary' ?>"><?= $user['role'] ?></span></td>
                                    <td><a href="?delete_user=<?= $user['id'] ?>" class="text-danger"><i class="bi bi-trash"></i></a></td>
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
                <h5 class="fw-bold mb-4">Manage Knowledge Base (FAQ)</h5>
                <form method="POST" class="row g-3 mb-5">
                    <input type="hidden" name="action" value="add_faq">
                    <div class="col-md-12">
                        <input type="text" name="question" class="form-control" placeholder="Question..." required>
                    </div>
                    <div class="col-md-10">
                        <textarea name="answer" class="form-control" placeholder="Answer..." rows="2" required></textarea>
                    </div>
                    <div class="col-md-2 d-flex align-items-stretch">
                        <button type="submit" class="btn btn-news w-100">Add</button>
                    </div>
                </form>
                <table class="table datatable w-100">
                    <thead>
                        <tr>
                            <th>Question</th>
                            <th style="width: 100px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($faq = $faqs->fetch_assoc()): ?>
                        <tr>
                            <td class="small fw-semibold"><?= htmlspecialchars($faq['question']) ?></td>
                            <td><a href="?delete_faq=<?= $faq['id'] ?>" class="btn btn-sm btn-outline-danger border-0"><i class="bi bi-trash"></i></a></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    // Elegant DataTables config
    $('.datatable').DataTable({
        "pageLength": 20,
        "order": [[0, "desc"]],
        "language": {
            "search": "",
            "searchPlaceholder": "Search records...",
            "paginate": { "next": "→", "previous": "←" }
        }
    });

    // Content input requirements
    const contentType = $('#contentType');
    const imageInput = $('#imageInput');
    const imageLabel = $('#imageLabel');

    function toggleFields() {
        if (contentType.val() === 'carousel') {
            imageLabel.html('Image <span class="text-danger">*</span>');
            imageInput.prop('required', true);
        } else {
            imageLabel.text('Image (Optional)');
            imageInput.prop('required', false);
        }
    }

    contentType.on('change', toggleFields);
    toggleFields();
});
</script>
</body>
</html>