<?php
require_once "./backend/config/db.php";
$result = $conn->query("SELECT * FROM news ORDER BY created_at DESC");
?>
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

<div class="container-lg" data-aos="fade-up">
  <h2 class="mb-4 news-heading">EARIST - CAVITE<span class="fw-bold"><br>NEWS</span></h2>
  <p class="text-muted">We are thrilled to share some exciting developments that are set to elevate the educational experience and opportunities for all members of our EARIST family. As we embark on this new chapter, we remain committed to our mission of fostering academic excellence, holistic growth, and a strong sense of community.</p>
  
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
               data-bs-target="#newsImageModal"
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

<!-- Modal (shared for all news images) -->
<div class="modal fade" id="newsImageModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content bg-transparent border-0 shadow-none position-relative">
      <button type="button" class="btn-close position-absolute top-0 end-0 m-3 z-3"
              data-bs-dismiss="modal" aria-label="Close"></button>
      <div class="modal-body d-flex justify-content-center align-items-center p-0">
        <img id="newsModalImage" src="" alt="" class="img-fluid rounded zoomable">
      </div>
    </div>
  </div>
</div>

<script>
  const newsModal = document.getElementById('newsImageModal');
  const newsModalImage = document.getElementById('newsModalImage');

  newsModal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    const img = button.getAttribute('data-bs-img');
    newsModalImage.src = img;
    newsModalImage.classList.remove('zoomed-in'); // reset zoom each time
  });

  // Toggle zoom on click
  newsModalImage.addEventListener('click', () => {
    newsModalImage.classList.toggle('zoomed-in');
  });
</script>



