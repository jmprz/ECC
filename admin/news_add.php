<?php
require_once __DIR__ . '/../backend/config/db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST['title']);
    $link = !empty($_POST['link']) ? trim($_POST['link']) : null;

    // Handle image upload
    $targetDir = "../uploads/news/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $imagePath = null;
    if (!empty($_FILES["image"]["name"])) {
        $fileName = time() . "_" . basename($_FILES["image"]["name"]); // add timestamp to avoid overwrite
        $targetFilePath = $targetDir . $fileName;
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)) {
            $imagePath = "backend/uploads/news/" . $fileName;
        }
    }

    $stmt = $conn->prepare("INSERT INTO news (title, image, link) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $title, $imagePath, $link);
    if ($stmt->execute()) {
        // ✅ Redirect to prevent duplicate insert on refresh
        header("Location: news_add.php?success=1");
        exit;
    } else {
        $error = "❌ Error: " . $conn->error;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add News</title>
  <link rel="stylesheet" href="../assets/vendor/bootstrap/css/bootstrap.min.css">
</head>
<body class="container mt-5">
  <h2>Add News Article</h2>

  <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">✅ News article added successfully!</div>
  <?php elseif (!empty($error)): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data">
    <div class="mb-3">
      <label class="form-label">Title</label>
      <input type="text" name="title" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Image (optional)</label>
      <input type="file" name="image" class="form-control">
    </div>
    <div class="mb-3">
      <label class="form-label">Link (optional)</label>
      <input type="url" name="link" class="form-control" placeholder="https://example.com">
    </div>
    <button type="submit" class="btn btn-success">Publish</button>
  </form>
</body>
</html>
