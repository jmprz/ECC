<?php
session_start();
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: login.php");
  exit;
}
require_once '../backend/config/db.php';

// Handle form submission for adding FAQ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_faq'])) {
  $question = $_POST['question'];
  $answer = $_POST['answer'];

  $stmt = $conn->prepare("INSERT INTO faq (question, answer) VALUES (?, ?)");
  $stmt->bind_param("ss", $question, $answer);
  $stmt->execute();
  $stmt->close();
  header("Location: faq_add.php");
  exit;
}

// Handle deletion of FAQ
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM faq WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: faq_add.php");
    exit;
}


$result = $conn->query("SELECT * FROM faq ORDER BY created_at DESC");

?>
