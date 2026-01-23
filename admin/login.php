<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();
require_once __DIR__ . '/../backend/config/db.php';

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


// If already logged in, redirect to correct dashboard
if (isset($_SESSION['admin_id'])) {
  if ($_SESSION['role'] === 'admin') {
    header("Location: admin_dashboard.php");
  } else {
    header("Location: user_dashboard.php");
  }
  exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $username = trim($_POST['username']);
  $password = trim($_POST['password']);

  // Fetch user + role
  $stmt = $conn->prepare("SELECT id, password, role FROM admins WHERE username = ?");
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $stmt->store_result();
  $stmt->bind_result($id, $hashedPassword, $role);
  $stmt->fetch();

  if ($stmt->num_rows > 0 && password_verify($password, $hashedPassword)) {
    session_regenerate_id(true);
    $_SESSION['admin_id'] = $id;
    $_SESSION['admin_username'] = $username;
    $_SESSION['role'] = $role;

    if ($role === 'admin') {
      header("Location: admin_dashboard.php");
    } else {
      header("Location: user_dashboard.php");
    }
    exit;
  } else {
    $error = "❌ Invalid username or password";
  }
  $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Login | EARIST - Cavite Campus</title>
  <!-- Favicons -->
  <link rel="apple-touch-icon" sizes="180x180" href="../assets/img/favicon/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="../assets/img/favicon/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="../assets/img/favicon/favicon-16x16.png">
  <link rel="manifest" href="../assets/img/favicon/site.webmanifest">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans&family=Poppins&family=Raleway&display=swap"
    rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">

  <!-- Main CSS File -->
  <link href="../assets/css/main.css?v=<?php echo time(); ?>" rel="stylesheet">
  <style>
    body {
      background: #f8f9fa;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }

    .login-card {
      max-width: 400px;
      width: 100%;
      padding: 20px;
      border-radius: 10px;
    }
  </style>
</head>

<body>
  <div class="card shadow login-card">
    <div class="card-body">
      <center>
        <img src="../assets/img/earist.png" class="logo" width="150" alt="">
        <h1 class="fs-4 fw-bold mt-3 ms-2 mb-4" style="color: #cc2e28">EARIST - CAVITE CAMPUS</h1>
      </center>
      <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
      <?php endif; ?>

      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <div class="mb-3">
          <label class="form-label">Username</label>
          <input type="text" name="username" class="form-control" placeholder="Enter username" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Password</label>
          <div class="position-relative">
            <input type="password" name="password" id="passwordField" class="form-control pe-5"
              placeholder="Enter password" required>
            <i class="bi bi-eye position-absolute top-50 end-0 translate-middle-y me-3" style="cursor: pointer;"
              onclick="togglePassword()" id="toggleIcon"></i>
          </div>
        </div>
        <button type="submit" class="btn btn-news w-100 fw-bold">Login</button>
      </form>
    </div>
  </div>
  <script>
    function togglePassword() {
      const passField = document.getElementById('passwordField');
      passField.type = passField.type === 'password' ? 'text' : 'password';
    }
  </script>
</body>

</html>