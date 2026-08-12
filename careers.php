<?php
require_once __DIR__ . '/config.php';

$jobs = [];
$result = db()->query("SELECT * FROM job_listings WHERE is_active = 1 ORDER BY created_at DESC");
if ($result) {
    $jobs = $result->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sunfreight | Careers – Join Our Team</title>
    <link href="https://fonts.googleapis.com/css2?family=Titillium+Web:ital,wght@0,600;0,700;1,700&family=Poppins:wght@600;700;800&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="css/careers.css">
    <style>
      /* Job listing cards (matches existing card style) */
      .jobs-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px; margin-top: 40px; }
      .job-card { background: #fff; border: 1px solid rgba(0,0,0,.08); border-radius: 14px; padding: 28px; box-shadow: 0 4px 18px rgba(0,0,0,.05); display: flex; flex-direction: column; }
      .job-card-title { font-family: 'Poppins', sans-serif; font-weight: 700; font-size: 1.15rem; margin-bottom: 8px; }
      .job-meta { font-size: .85rem; opacity: .75; margin-bottom: 14px; display: flex; flex-wrap: wrap; gap: 8px; }
      .job-tag { background: rgba(0,0,0,.06); border-radius: 999px; padding: 3px 12px; }
      .job-desc { font-size: .95rem; line-height: 1.6; flex: 1; }
      .job-apply { margin-top: 18px; align-self: flex-start; font-weight: 600; text-decoration: none; }
      .jobs-empty { margin-top: 32px; opacity: .8; }
      .section-title em{
        color: var(--purple);
      }
    </style>
</head>
<body>

<!-- NAV -->
<nav id="navbar">
  <div class="logo"><a href="index.html"><img class="logo" src="pics/sunfreight_logo.png" alt="logo"></a></div>
  <ul class="nav-links" id="navLinks">
      <li><a href="index.html">Home</a></li>
      <li><a href="services.html">Services</a></li>
      <li><a href="about.html">About Us</a></li>
      <li><a href="careers.php" class="active">Careers</a></li>
      <li><a href="gallery.php">Gallery</a></li>
      <li class="nav-cta-mobile"><a href="contact.html">Get a Quote</a></li>
  </ul>
  <a href="contact.html" class="nav-cta">Get a Quote</a>
  <button class="nav-toggle" id="navToggle" aria-label="Open menu" aria-expanded="false">☰</button>
</nav>

<!-- HERO -->
<section class="hero" id="home">
  <div class="hero-text">
    <div class="hero-eyebrow">Join Our Team</div>
    <h1 class="hero-title">Move the World<br>with <em>Sunfreight</em></h1>
    <p class="hero-body">We've been connecting the Philippines to the world since 2002. Now we're looking for driven people ready to grow their careers at the heart of global logistics.</p>
    <div class="hero-actions">
      <a class="btn-primary" href="#roles">Why Work Here</a>
    </div>
  </div>
  <div class="hero-visual">
    <div class="hero-pill-grid">
      <div class="hero-pill">
        <div class="hero-pill-num"><em>20+</em></div>
        <div class="hero-pill-label">Years in Business</div>
      </div>
      <div class="hero-pill">
        <div class="hero-pill-num">50+</div>
        <div class="hero-pill-label">Team Members</div>
      </div>
      <div class="hero-pill">
        <div class="hero-pill-num"><em>PH</em></div>
        <div class="hero-pill-label">Nationwide Network</div>
      </div>
      <div class="hero-pill">
        <div class="hero-pill-num">Air<em> & </em>Sea</div>
        <div class="hero-pill-label">Global Reach</div>
      </div>
    </div>
  </div>
</section>

<!-- TICKER -->
<div class="ticker" aria-hidden="true">
  <div class="ticker-track">
    <span class="ticker-item"><span class="ticker-dot"></span>Competitive Compensation</span>
    <span class="ticker-item"><span class="ticker-dot"></span>HMO Coverage</span>
    <span class="ticker-item"><span class="ticker-dot"></span>Career Growth</span>
    <span class="ticker-item"><span class="ticker-dot"></span>Government-Mandated Benefits</span>
    <span class="ticker-item"><span class="ticker-dot"></span>Training & Development</span>
    <span class="ticker-item"><span class="ticker-dot"></span>Collaborative Culture</span>
    <span class="ticker-item"><span class="ticker-dot"></span>Stable & Established Company</span>
    <span class="ticker-item"><span class="ticker-dot"></span>International Exposure</span>
    <!-- duplicate for seamless loop -->
    <span class="ticker-item"><span class="ticker-dot"></span>Competitive Compensation</span>
    <span class="ticker-item"><span class="ticker-dot"></span>HMO Coverage</span>
    <span class="ticker-item"><span class="ticker-dot"></span>Career Growth</span>
    <span class="ticker-item"><span class="ticker-dot"></span>Government-Mandated Benefits</span>
    <span class="ticker-item"><span class="ticker-dot"></span>Training & Development</span>
    <span class="ticker-item"><span class="ticker-dot"></span>Collaborative Culture</span>
    <span class="ticker-item"><span class="ticker-dot"></span>Stable & Established Company</span>
    <span class="ticker-item"><span class="ticker-dot"></span>International Exposure</span>
  </div>
</div>

<!-- WHY WETLI -->
<section class="why" id="why">
  <div class="why-header">
    <div class="section-eyebrow">Why Sunfreight</div>
    <h2 class="section-title">More than a job — a career that moves</h2>
    <p class="section-sub">At 88 Sunfreight Express Corp, you'll work alongside experienced professionals in an industry that never stands still.</p>
  </div>
  <div class="why-grid">
    <div class="why-card">
      <div class="why-title">Global Exposure</div>
      <p class="why-desc">Work with international networks, freight partners, and accredited government agencies across air and sea logistics.</p>
    </div>
    <div class="why-card">
      <div class="why-title">Career Advancement</div>
      <p class="why-desc">We promote from within. Whether you're starting out or experienced, there's a clear path to grow into leadership roles.</p>
    </div>
    <div class="why-card">
      <div class="why-title">HMO & Benefits</div>
      <p class="why-desc">Comprehensive health coverage for you and your dependents, plus all government-mandated benefits and competitive compensation.</p>
    </div>
    <div class="why-card">
      <div class="why-title">Collaborative Culture</div>
      <p class="why-desc">Our team of over 100 professionals works as one — sharing knowledge, solving problems together, and celebrating wins.</p>
    </div>
    <div class="why-card">
      <div class="why-title">Training & Development</div>
      <p class="why-desc">Ongoing skills training, industry certifications, and mentorship to keep you sharp and ahead of industry changes.</p>
    </div>
    <div class="why-card">
      <div class="why-title">Stable Foundation</div>
      <p class="why-desc">Over two decades in operation with a strong client base, multiple government accreditations, and a resilient business model.</p>
    </div>
  </div>
</section>

<!-- OPEN ROLES (dynamic from database) -->
<section class="roles" id="roles">
  <div class="roles-header">
    <h2 class="section-title">Find your role at <em>Sunfreight</em></h2>

    <?php if (count($jobs) > 0): ?>
      <p class="section-sub">We're currently hiring for the roles below. Click "Apply" to send your resume directly to our HR team.</p>
    <?php else: ?>
      <p class="section-sub jobs-empty">We are continuously looking for competent and driven individuals who are ready to contribute and grow with our team. While specific roles may not always be listed, we welcome applications from professionals who align with our standards of excellence and commitment to quality work.</p>
    <?php endif; ?>
  </div>

  <?php if (count($jobs) > 0): ?>
  <div class="jobs-grid">
    <?php foreach ($jobs as $job): ?>
      <div class="job-card">
        <div class="job-card-title"><?= e($job['title']) ?></div>
        <div class="job-meta">
          <?php if (!empty($job['department'])): ?><span class="job-tag"><?= e($job['department']) ?></span><?php endif; ?>
          <span class="job-tag"><?= e($job['employment_type']) ?></span>
          <?php if (!empty($job['location'])): ?><span class="job-tag"><?= e($job['location']) ?></span><?php endif; ?>
        </div>
        <p class="job-desc"><?= nl2br(e($job['description'])) ?></p>
        <a class="job-apply link1" href="mailto:hr@windfreightexpress.com?subject=Application: <?= rawurlencode($job['title']) ?> - Sunfreight Careers">Apply for this role →</a>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div class="roles-header" style="margin-top:48px;">
    <p class="section-sub">
    <h3>Submit Your Application</h3>
    <br>
    You may send your resume and a brief introduction to <a href="mailto:hr@windfreightexpress.com" class="link1"> hr@windfreightexpress.com</a>  or <a href="mailto:hrad@windfreightexpress.com" class="link2"> hrad@windfreightexpress.com</a>
    <br>
    Qualified candidates will be contacted should a suitable opportunity arise.
    </p>
  </div>
</section>


<!-- CTA BANNER -->
<div class="cta-banner">
  <div>
    <h2 class="cta-banner-title">Join our Organization<br><span>Send us your resume</span></h2>
    <p class="cta-banner-sub">We're always open to meeting talented people. Drop us a line and we'll keep you in mind.</p>
  </div>
  <a href="mailto:hr@windfreightexpress.com?subject=General Application - Sunfreight Careers" class="btn-gold">Send Your Resume →</a>
</div>

<!-- INDEED SECTION 
<div class="indeed-section" id="indeed">
  <div class="indeed-text">
    <div class="section-eyebrow">Also on Indeed</div>
    <h2 class="section-title">Browse our listings<br>on <em>Indeed</em></h2>
    <p class="section-sub">All our active job openings are also posted on Indeed — the Philippines' most-used job platform. Search, save, and apply directly from your Indeed account.</p>
    <a href="https://ph.indeed.com/cmp/Windfreight-Express-Total-Logistics" target="_blank" rel="noopener noreferrer" class="btn-indeed">View Jobs on Indeed →</a>
  </div>
  <div class="indeed-card">
    <div class="indeed-logo">
      <div class="indeed-logo-text">indeed</div>
    </div>
    <div class="indeed-rating-row">
      <span class="indeed-stars">★★★</span>
      <span class="indeed-rating-num">3.0</span>
    </div>
    <div class="indeed-rating-label">Based on employee reviews</div>
    <div class="indeed-divider"></div>
    <p class="indeed-tagline">Read what current and former team members say about working at 88 Sunfreight Express Corp.</p>
    <a href="https://ph.indeed.com/cmp/Windfreight-Express-Total-Logistics/reviews" target="_blank" rel="noopener noreferrer" class="btn-indeed">Read Reviews</a>
    <span class="indeed-note">Opens in a new tab · ph.indeed.com</span>
  </div>
</div>
-->

<!-- FOOTER -->
<footer>
  <div class="footer-grid">
    <div class="footer-brand">
      <div class="logo">88 Sunfreight Express Corp</div>
    </div>
    <div>
      <div class="footer-col-title">Company</div>
      <ul class="footer-links">
        <li><a href="about.html">About Us</a></li>
        <li><a href="careers.html">Careers</a></li>
        <li><a href="#coverage">Network and Accreditations</a></li>
      </ul>
    </div>
    <div>
      <div class="footer-col-title">Contact</div>
      <ul class="footer-links">
        <li><a href="https://www.facebook.com/88SEC" target="_blank">Facebook</a></li>
        <li><a href="https://www.instagram.com/88sunfreightexpresscorp" target="_blank">Instagram</a></li>
        <li><a href="tel:63228903009">+63 2 8290 3009</a></li>
        <li><a href="mailto:inquiry@88sunfreight.com">inquiry@88sunfreight.com</a></li>
        <li><a href="mailto:forwarding@88sunfreight.com ">forwarding@88sunfreight.com </a></li>
      </ul>
    </div>
  </div>
  <div class="footer-bottom">
    <span>Unit 6B, Frabelle Alabang Building, Madrigal Business Park, Ayala Alabang, Muntinlupa City, Metro Manila, 1780</span>
    <span><a href="login.php" class="staff-login" aria-label="Staff login">©</a> 2008 | Sunfreight Express Corp., All Rights Reserved</span>
  </div>
</footer>

<script>
  // Nav scroll effect
  const navbar = document.getElementById('navbar');
  window.addEventListener('scroll', () => {
    navbar.classList.toggle('scrolled', window.scrollY > 40);
  });

  // Mobile menu toggle
  const navToggle = document.getElementById('navToggle');
  const navLinks = document.getElementById('navLinks');

  navToggle.addEventListener('click', () => {
    const open = navLinks.classList.toggle('open');
    navToggle.textContent = open ? '✕' : '☰';
    navToggle.setAttribute('aria-expanded', open);
  });

  navLinks.querySelectorAll('a').forEach(a => {
    a.addEventListener('click', () => {
      navLinks.classList.remove('open');
      navToggle.textContent = '☰';
      navToggle.setAttribute('aria-expanded', 'false');
    });
  });
</script>
</body>
</html>