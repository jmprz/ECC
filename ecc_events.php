<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Events | EARIST - Cavite Campus</title>
  <meta name="description" content="">
  <meta name="keywords" content="">

  <!-- Favicons -->
  <link rel="apple-touch-icon" sizes="180x180" href="assets/img/favicon/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="assets/img/favicon/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="assets/img/favicon/favicon-16x16.png">
  <link rel="manifest" href="assets/img/favicon/site.webmanifest">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans&family=Poppins&family=Raleway&display=swap" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
  <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">

  <!-- Main CSS File -->
  <link href="assets/css/main.css?v=<?php echo time(); ?>" rel="stylesheet">

<style>
  .card-img-top {
    height: 300px;
    width: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
  }

  .card-img-top:hover {
    transform: scale(1.05);
  }

 .zoomable {
  transition: transform 0.3s ease;
  cursor: zoom-in;
  max-height: 90vh; 
}
.zoomed-in {
  transform: scale(1.5); 
  cursor: zoom-out;
}
</style>

<body class="index-page">

  <?php include('header.php'); ?>

  <main class="main">
    <div class="container" style="margin-top: 150px;">
        <?php
        require_once "./backend/config/db.php";
        $result = $conn->query("SELECT * FROM carousel WHERE status='posted' ORDER BY created_at DESC");
        ?>

        <div class="container-lg" data-aos="fade-up">
          <h2 class="mb-4 news-heading">EARIST - CAVITE<span class="fw-bold"><br>EVENTS</span></h2>
          <p class="text-muted">Stay updated with the latest happenings and events at EARIST Cavite Campus. We are dedicated to providing a vibrant and engaging environment for our students and community.</p>
          
         <div class="row">
          <?php while ($row = $result->fetch_assoc()): ?>
            <div class="col-md-4 mb-4">
              <div class="card h-100">
                <img src="<?= htmlspecialchars($row['image']) ?>" 
                     class="card-img-top" 
                     alt="<?= htmlspecialchars($row['title']) ?>">
                <div class="card-body text-center">
                  <h5 class="card-title"><?= htmlspecialchars($row['title']) ?></h5>
                  <?php if (!empty($row['link'])): ?>
                    <a href="<?= htmlspecialchars($row['link']) ?>" target="_blank" class="btn btn-news mt-2">
                     View Details
                    </a>
                  <?php else: ?>
                    <a href="#" 
                       class="btn btn-news mt-2"
                       data-bs-toggle="modal"
                       data-bs-target="#eventImageModal"
                       data-bs-img="<?= htmlspecialchars($row['image']) ?>"
                       data-bs-title="<?= htmlspecialchars($row['title']) ?>">
                      View Details
                    </a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
        </div>

        <!-- Modal (shared for all event images) -->
        <div class="modal fade" id="eventImageModal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content bg-transparent border-0 shadow-none position-relative">
              <button type="button" class="btn-close position-absolute top-0 end-0 m-3 z-3"
                      data-bs-dismiss="modal" aria-label="Close"></button>
              <div class="modal-body d-flex justify-content-center align-items-center p-0">
                <img id="eventModalImage" src="" alt="" class="img-fluid rounded zoomable">
              </div>
            </div>
          </div>
        </div>
    </div>
  </main>


  <?php include('footer.php'); ?>

  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>


  <!-- Vendor JS Files -->
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/php-email-form/validate.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
  <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
  <script src="assets/vendor/imagesloaded/imagesloaded.pkgd.min.js"></script>
  <script src="assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>

  <!-- Main JS File -->
  <script src="assets/js/main.js"></script>
  
  <script>
    const eventModal = document.getElementById('eventImageModal');
    const eventModalImage = document.getElementById('eventModalImage');

    eventModal.addEventListener('show.bs.modal', function (event) {
      const button = event.relatedTarget;
      const img = button.getAttribute('data-bs-img');
      eventModalImage.src = img;
      eventModalImage.classList.remove('zoomed-in'); // reset zoom each time
    });

    // Toggle zoom on click
    eventModalImage.addEventListener('click', () => {
      eventModalImage.classList.toggle('zoomed-in');
    });
  </script>

</body>
</html>