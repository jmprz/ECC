<?php
// Include database connection
require_once 'backend/config/db.php';

// Fetch FAQs from database
$result = $conn->query("SELECT * FROM faq ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>FAQ | EARIST - Cavite Campus</title>

    <link rel="apple-touch-icon" sizes="180x180" href="assets/img/favicon/apple-touch-icon.png" />
    <link rel="icon" type="image/png" sizes="32x32" href="assets/img/favicon/favicon-32x32.png" />
    <link rel="icon" type="image/png" sizes="16x16" href="assets/img/favicon/favicon-16x16.png" />
    <link rel="manifest" href="assets/img/favicon/site.webmanifest" />

    <link href="https://fonts.googleapis.com" rel="preconnect" />
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400;1,500;1,600;1,700;1,800&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet" />

    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" />
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet" />
    <link href="assets/vendor/aos/aos.css" rel="stylesheet" />
    <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet" />
    <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet" />

    <link href="assets/css/main.css" rel="stylesheet" />
  </head>

  <body class="index-page">
  <?php include('header.php'); ?>

    <main class="main">
       <section id="ecc-faq" class="ecc-faq section">
        <!-- Section Title -->
        <div class="container mt-2" data-aos="fade-up">
          <h1 class="fw-bold text-center">Frequently Asked Questions</h1>
          
          <div class="row justify-content-center">
            <div class="col-lg-10">
              <div class="accordion accordion-flush" id="faqAccordion">

                <?php
                if ($result && $result->num_rows > 0):
                    $index = 0;
                    while($faq = $result->fetch_assoc()): 
                        $showClass = ($index === 0) ? 'show' : ''; 
                        $collapsedClass = ($index === 0) ? '' : 'collapsed';
                ?>
                  <div class="accordion-item border-bottom py-2">
                    <h2 class="accordion-header" id="heading-<?= $faq['id']; ?>">
                      <button class="accordion-button <?= $collapsedClass; ?> fw-semibold bg-transparent shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?= $faq['id']; ?>" aria-expanded="<?= ($index === 0) ? 'true' : 'false'; ?>">
                        <?= htmlspecialchars($faq['question']); ?>
                      </button>
                    </h2>
                    <div id="collapse-<?= $faq['id']; ?>" class="accordion-collapse collapse <?= $showClass; ?>" data-bs-parent="#faqAccordion">
                      <div class="accordion-body text-secondary">
                        <?= nl2br(htmlspecialchars($faq['answer'])); ?>
                      </div>
                    </div>
                  </div>
                <?php 
                    $index++;
                    endwhile; 
                else:
                ?>
                  <div class="text-center py-5">
                    <p class="text-muted">No FAQs available at the moment. Please check back later.</p>
                  </div>
                <?php endif; ?>

              </div>
            </div>
          </div>
        </div>
      </section>
    </main>

    <?php include('footer.php'); ?>

    <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/aos/aos.js"></script>
    <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
    <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
    <script src="assets/js/main.js"></script>
  </body>
</html>