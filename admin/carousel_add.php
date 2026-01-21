<?php
require_once __DIR__ . '/../backend/config/db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST['title']);
    $link = !empty($_POST['link']) ? trim($_POST['link']) : null;

    // Handle image upload
    $targetDir = "../uploads/carousel/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $fileName = basename($_FILES["image"]["name"]);
    $targetFilePath = $targetDir . $fileName;

    if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)) {
        // Save the correct public path in DB
        $imagePath = "backend/uploads/carousel/" . $fileName;
        $status = (isset($_POST['action_type']) && $_POST['action_type'] === 'publish') ? 'posted' : 'draft';

        $stmt = $conn->prepare("INSERT INTO carousel (title, image, link, status) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sss", $title, $imagePath, $link, $status);

        if ($stmt->execute()) {
            echo "✅ Carousel added successfully!";
        } else {
            echo "❌ Error: " . $conn->error;
        }
        $stmt->close();
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add Carousel</title>
  <link rel="stylesheet" href="../assets/vendor/bootstrap/css/bootstrap.min.css">
</head>
<body class="container mt-5">
  <h2>Add Carousel Item</h2>
  <form method="POST" enctype="multipart/form-data">
    <div class="mb-3">
      <label class="form-label">Title</label>
      <input type="text" name="title" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Image</label>
      <input type="file" name="image" class="form-control" required>
    </div>
    <div class="mb-3">
  <label class="form-label">Button Link (Optional)</label>
  <input type="url" name="link" class="form-control" placeholder="https://example.com">
</div>
    <button type="submit" class="btn btn-primary">Upload</button>
  </form>
</body>
</html>
