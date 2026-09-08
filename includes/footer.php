<footer class="site-footer">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-about">
        <a href="<?= base_url('index.php') ?>" class="logo">
          <?php if ($logo_url): ?>
            <img src="<?= e($logo_url) ?>" alt="<?= e(setting('site_name')) ?>" class="logo-image">
          <?php else: ?>
            <span><span class="zee">ZEE</span><span class="broast">BROAST</span></span>
          <?php endif; ?>
        </a>
        <p>Crispy, juicy, irresistible broast chicken delivered hot and fresh to your doorstep.</p>
        <div class="footer-social">
          <a href="<?= e(setting('facebook_url', '#')) ?>" title="Facebook">f</a>
          <a href="<?= e(setting('twitter_url', '#')) ?>" title="Twitter">𝕏</a>
          <a href="<?= e(setting('instagram_url', '#')) ?>" title="Instagram">◎</a>
        </div>
      </div>
      <div>
        <h4>Quick Links</h4>
        <ul>
          <li><a href="<?= base_url('index.php') ?>">Home</a></li>
          <li><a href="<?= base_url('menu.php') ?>">Menu</a></li>
          <li><a href="<?= base_url('category.php') ?>">Categories</a></li>
          <li><a href="<?= base_url('deals.php') ?>">Deals</a></li>
          <li><a href="<?= base_url('about.php') ?>">About Us</a></li>
          <li><a href="<?= base_url('contact.php') ?>">Contact Us</a></li>
        </ul>
      </div>
      <div>
        <h4>Categories</h4>
        <ul>
          <?php foreach (db()->query("SELECT name, slug FROM categories WHERE status='active' ORDER BY sort_order")->fetchAll() as $cat): ?>
          <li><a href="<?= base_url('category.php?slug=' . urlencode($cat['slug'])) ?>"><?= e($cat['name']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div>
        <h4>Contact Us</h4>
        <ul>
          <li><a href="tel:<?= e(setting('phone')) ?>">📞 <?= e(setting('phone')) ?></a></li>
          <li><a href="mailto:<?= e(setting('email')) ?>">✉️ <?= e(setting('email')) ?></a></li>
          <li><span>📍 <?= e(setting('address')) ?></span></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      &copy; <?= date('Y') ?> <?= e(setting('site_name')) ?>. All rights reserved.
    </div>
  </div>
</footer>
<script src="<?= base_url('assets/js/main.js') ?>"></script>
</body>
</html>
