<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Home | EARIST - Cavite Campus</title>
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

<body class="index-page">

  <?php include('header.php'); ?>

<?php
  require_once "./backend/config/db.php"; 

  $result = $conn->query("SELECT * FROM carousel WHERE status='active' ORDER BY created_at DESC");
?>

 <main class="main">
<section id="hero" class="hero section dark-background">
    <div id="hero-carousel" data-bs-interval="5000" class="container carousel carousel-fade" data-bs-ride="carousel">
      <?php
      $first = true;
      while ($row = $result->fetch_assoc()): ?>
        <div class="carousel-item <?= $first ? 'active' : '' ?>">
          <div class="carousel-container">
            <div class="row align-items-center h-100">
              <div class="col-md-6">
                <img src="<?= htmlspecialchars($row['image']) ?>" alt="<?= htmlspecialchars($row['title']) ?>" class="img-fluid w-100" data-aos="fade-in">
              </div>
              <div class="col-md-6">
                <div class="carousel-details text-center text-white" data-aos="fade-up" data-aos-delay="100">
                  <h2><?= htmlspecialchars($row['title']) ?></h2>

                  <?php if (!empty($row['link'])): ?>
                    <!-- Open external link -->
                    <a href="<?= htmlspecialchars($row['link']) ?>" 
                       target="_blank" 
                       class="btn btn-details mt-3">
                      View Details
                    </a>
                  <?php else: ?>
                    <!-- Open modal -->
                    <a href="#" 
                       class="btn btn-details mt-3" 
                       data-bs-toggle="modal" 
                       data-bs-target="#imageModal" 
                       data-bs-img="<?= htmlspecialchars($row['image']) ?>" 
                       data-bs-title="<?= htmlspecialchars($row['title']) ?>">
                      View Details
                    </a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php $first = false; endwhile; ?>
    </div>

    <!-- Controls -->
    <a class="carousel-control-prev" href="#hero-carousel" role="button" data-bs-slide="prev">
      <span class="carousel-control-prev-icon bi bi-chevron-left" aria-hidden="true"></span>
    </a>
    <a class="carousel-control-next" href="#hero-carousel" role="button" data-bs-slide="next">
      <span class="carousel-control-next-icon bi bi-chevron-right" aria-hidden="true"></span>
    </a>

    <!-- Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-xl">
       <div class="modal-content bg-transparent border-0 shadow-none position-relative">
      
         <!-- Close button outside image -->
        <button type="button" class="btn-close position-absolute top-0 end-0 m-3 z-3 text-light" 
              data-bs-dismiss="modal" aria-label="Close"></button>
      
         <!-- Zoomable container -->
        <div class="modal-body d-flex justify-content-center align-items-center p-0">
          <img id="modalImage" src="" alt="" class="img-fluid rounded zoomable">
        </div>
      </div>
    </div>
  </div>
    </section>

    <section class="news-section">
        <?php include('news.php'); ?>
    </section>
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
  const imageModal = document.getElementById('imageModal');
  const modalImage = document.getElementById('modalImage');

  // Load correct image
  imageModal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget; 
    const img = button.getAttribute('data-bs-img');
    modalImage.src = img;
    modalImage.classList.remove('zoomed'); // reset zoom
  });

  // Toggle zoom on click
  modalImage.addEventListener('click', function () {
    modalImage.classList.toggle('zoomed');
  });
</script>




</body>
</html>
