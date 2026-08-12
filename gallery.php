<?php

require_once __DIR__ . '/config.php';

/*
|--------------------------------------------------------------------------
| Load active events with their photos
|--------------------------------------------------------------------------
*/

$events = db()->query(
    'SELECT * FROM gallery_events
     WHERE is_active = 1
     ORDER BY event_date DESC, created_at DESC'
)->fetch_all(MYSQLI_ASSOC);

$photosByEvent = [];

if ($events) {
    $result = db()->query(
        'SELECT * FROM gallery_photos
         ORDER BY is_cover DESC, sort_order ASC, id ASC'
    );

    while ($row = $result->fetch_assoc()) {
        $photosByEvent[$row['event_id']][] = $row;
    }
}

/*
|--------------------------------------------------------------------------
| Group events by category (fixed display order)
|--------------------------------------------------------------------------
*/

$categoryOrder = [
    'Proof of Shipments',
    'Expanding Our Network',
    'Company Culture',
    'Corporate Social Responsibility',
];

$eventsByCategory = array_fill_keys($categoryOrder, []);

foreach ($events as $ev) {
    $cat = $ev['category'] ?? 'Company Culture';

    if (!isset($eventsByCategory[$cat])) {
        $eventsByCategory[$cat] = [];
    }

    $eventsByCategory[$cat][] = $ev;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>WETLI | Gallery</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@700;800&family=Raleway:wght@600;700&family=Open+Sans:wght@400;500;600&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="css/index.css">
<link rel="stylesheet" href="css/gallery.css">
</head>
<body>

<!-- NAV -->
<nav id="navbar">
  <div class="logo"><a href="index.html"><img class="logo" src="pics/sunfreight_logo.png" alt="logo"></a></div>
  <ul class="nav-links" id="navLinks">
      <li><a href="index.html">Home</a></li>
      <li><a href="services.html">Services</a></li>
      <li><a href="about.html">About Us</a></li>
      <li><a href="careers.php">Careers</a></li>
      <li><a href="gallery.php" class="active">Gallery</a></li>
      <li class="nav-cta-mobile"><a href="contact.html">Get a Quote</a></li>
  </ul>
  <a href="contact.html" class="nav-cta">Get a Quote</a>
  <button class="nav-toggle" id="navToggle" aria-label="Open menu" aria-expanded="false">☰</button>
</nav>

<!-- PAGE HERO -->
<section class="gallery-hero">
  <div class="gallery-hero-inner">
    <div class="gallery-hero-eyebrow">Our Gallery</div>
    <h1>Life at <em>WETLI</em></h1>
    <p>A look inside our operations, our people, and the moments that move us.</p>
  </div>
</section>

<div class="gallery-wrap">

  <?php if (!$events): ?>
    <p class="gallery-empty">Photos coming soon — check back shortly!</p>
  <?php else: ?>

    <?php foreach ($eventsByCategory as $catName => $catEvents):
        // Skip categories with no displayable events (must have photos)
        $visible = array_filter($catEvents, fn ($ev) => !empty($photosByEvent[$ev['id']]));
        if (!$visible) continue;
    ?>
    <div class="gallery-section">
      <div class="gallery-section-head">
        <h2 class="gallery-section-title"><?= e($catName) ?></h2>
        <div class="gallery-section-line"></div>
        <span class="gallery-section-count"><?= count($visible) ?> event<?= count($visible) == 1 ? '' : 's' ?></span>
      </div>

      <div class="gallery-grid">
        <?php foreach ($visible as $ev):
            $photos = $photosByEvent[$ev['id']];
            $cover  = $photos[0];
        ?>
        <div class="gallery-item" data-event="<?= (int)$ev['id'] ?>">
          <img src="uploads/gallery/<?= e($cover['filename']) ?>" alt="<?= e($ev['title']) ?>" loading="lazy">
          <div class="gallery-caption">
            <div class="gallery-title"><?= e($ev['title']) ?></div>
            <?php if (!empty($ev['event_date'])): ?>
              <div class="gallery-sub"><?= e(date('F j, Y', strtotime($ev['event_date']))) ?></div>
            <?php endif; ?>
            <span class="gallery-count"><?= count($photos) ?> photo<?= count($photos) == 1 ? '' : 's' ?></span>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>

  <?php endif; ?>
</div>

<!-- LIGHTBOX -->
<div class="lightbox" id="lightbox">
  <div class="lightbox-inner">
    <button class="lightbox-close" id="lightboxClose" aria-label="Close">✕</button>
    <div class="carousel">
      <div class="carousel-track" id="carouselTrack"></div>
      <button class="carousel-btn prev" id="carouselPrev" aria-label="Previous">‹</button>
      <button class="carousel-btn next" id="carouselNext" aria-label="Next">›</button>
      <div class="carousel-dots" id="carouselDots"></div>
    </div>
    <div class="lightbox-body">
      <div class="lightbox-title" id="lightboxTitle"></div>
      <div class="lightbox-date" id="lightboxDate"></div>
      <div class="lightbox-desc" id="lightboxDesc"></div>
    </div>
  </div>
</div>
<!--FOOTER-->
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
    // Navbar scroll effect
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

    // Event data from PHP
    const events = <?= json_encode(
        array_values(
            array_filter(
                array_map(function ($ev) use ($photosByEvent) {
                    $photos = $photosByEvent[$ev['id']] ?? [];

                    if (!$photos) {
                        return null;
                    }

                    return [
                        'id' => (int) $ev['id'],
                        'title' => $ev['title'],
                        'date' => $ev['event_date']
                            ? date(
                                'F j, Y',
                                strtotime($ev['event_date'])
                            )
                            : '',
                        'desc' => $ev['description'] ?? '',
                        'photos' => array_map(
                            fn ($photo) => $photo['filename'],
                            $photos
                        ),
                    ];
                }, $events)
            )
        )
    ) ?>;

    const lightbox = document.getElementById('lightbox');
    const track = document.getElementById('carouselTrack');
    const dotsWrap = document.getElementById('carouselDots');

    const titleEl = document.getElementById('lightboxTitle');
    const dateEl = document.getElementById('lightboxDate');
    const descEl = document.getElementById('lightboxDesc');

    const previousButton = document.getElementById('carouselPrev');
    const nextButton = document.getElementById('carouselNext');
    const closeButton = document.getElementById('lightboxClose');
    const carousel = document.querySelector('.carousel');

    const AUTOPLAY_DELAY = 3500;

    let current = 0;
    let realSlideCount = 0;
    let autoplayTimer = null;
    let isAnimating = false;

    /**
     * Create one carousel slide.
     */
    function createSlide(file, title, photoNumber) {
        const slide = document.createElement('div');
        slide.className = 'carousel-slide';

        const image = document.createElement('img');
        image.src = `uploads/gallery/${file}`;
        image.alt = `${title} photo ${photoNumber}`;
        image.loading = 'lazy';
        image.draggable = false;

        slide.appendChild(image);

        return slide;
    }

    /**
     * Move the carousel track.
     */
    function setCarouselPosition(animate = true) {
        track.style.transition = animate
            ? 'transform 0.35s ease'
            : 'none';

        track.style.transform =
            `translate3d(-${current * 100}%, 0, 0)`;
    }

    /**
     * Update the active navigation dot.
     */
    function updateDots() {
        const dots = dotsWrap.querySelectorAll('.carousel-dot');

        if (!realSlideCount) {
            return;
        }

        const activeIndex = realSlideCount === 1
            ? 0
            : (
                current - 1 + realSlideCount
            ) % realSlideCount;

        dots.forEach((dot, index) => {
            dot.classList.toggle(
                'active',
                index === activeIndex
            );
        });
    }

    /**
     * Navigate to a slide.
     */
    function goTo(index, animate = true) {
        if (!realSlideCount) {
            return;
        }

        if (realSlideCount === 1) {
            current = 0;
            setCarouselPosition(false);
            updateDots();
            return;
        }

        if (isAnimating && animate) {
            return;
        }

        current = index;
        isAnimating = animate;

        setCarouselPosition(animate);
        updateDots();
    }

    /**
     * Stop automatic movement.
     */
    function stopAutoplay() {
        if (autoplayTimer) {
            clearInterval(autoplayTimer);
            autoplayTimer = null;
        }
    }

    /**
     * Start automatic movement.
     */
    function startAutoplay() {
        stopAutoplay();

        if (
            realSlideCount <= 1 ||
            !lightbox.classList.contains('open')
        ) {
            return;
        }

        autoplayTimer = setInterval(() => {
            goTo(current + 1);
        }, AUTOPLAY_DELAY);
    }

    /**
     * Restart autoplay after manual navigation.
     */
    function restartAutoplay() {
        stopAutoplay();
        startAutoplay();
    }

    /**
     * Open an event gallery.
     */
    function openEvent(id) {
        const event = events.find(
            item => item.id === id
        );

        if (!event || !event.photos.length) {
            return;
        }

        stopAutoplay();

        track.innerHTML = '';
        dotsWrap.innerHTML = '';

        realSlideCount = event.photos.length;
        isAnimating = false;

        /*
         * Add a clone of the last image before the first
         * real image.
         */
        if (realSlideCount > 1) {
            const lastIndex = realSlideCount - 1;

            track.appendChild(
                createSlide(
                    event.photos[lastIndex],
                    event.title,
                    realSlideCount
                )
            );
        }

        /*
         * Add all real images.
         */
        event.photos.forEach((file, index) => {
            track.appendChild(
                createSlide(
                    file,
                    event.title,
                    index + 1
                )
            );

            const dot = document.createElement('button');

            dot.type = 'button';
            dot.className =
                'carousel-dot' +
                (index === 0 ? ' active' : '');

            dot.setAttribute(
                'aria-label',
                `Go to photo ${index + 1}`
            );

            dot.addEventListener('click', () => {
                /*
                 * Add 1 because slide 0 is the cloned
                 * last image.
                 */
                goTo(
                    realSlideCount > 1
                        ? index + 1
                        : index
                );

                restartAutoplay();
            });

            dotsWrap.appendChild(dot);
        });

        /*
         * Add a clone of the first image after the last
         * real image.
         */
        if (realSlideCount > 1) {
            track.appendChild(
                createSlide(
                    event.photos[0],
                    event.title,
                    1
                )
            );

            current = 1;
        } else {
            current = 0;
        }

        titleEl.textContent = event.title;
        dateEl.textContent = event.date;
        descEl.textContent = event.desc;

        dateEl.style.display = event.date
            ? ''
            : 'none';

        previousButton.style.display =
            realSlideCount > 1 ? '' : 'none';

        nextButton.style.display =
            realSlideCount > 1 ? '' : 'none';

        dotsWrap.style.display =
            realSlideCount > 1 ? 'flex' : 'none';

        lightbox.classList.add('open');
        document.body.style.overflow = 'hidden';

        requestAnimationFrame(() => {
            setCarouselPosition(false);
            updateDots();
            startAutoplay();
        });
    }

    /**
     * Close the gallery popup.
     */
    function closeLightbox() {
        stopAutoplay();

        lightbox.classList.remove('open');
        document.body.style.overflow = '';

        track.innerHTML = '';
        dotsWrap.innerHTML = '';

        current = 0;
        realSlideCount = 0;
        isAnimating = false;
    }

    /*
     * After reaching a cloned image, instantly jump to
     * its corresponding real image.
     */
    track.addEventListener('transitionend', () => {
        if (realSlideCount <= 1) {
            isAnimating = false;
            return;
        }

        // Cloned last image reached
        if (current === 0) {
            current = realSlideCount;
            setCarouselPosition(false);
        }

        // Cloned first image reached
        if (current === realSlideCount + 1) {
            current = 1;
            setCarouselPosition(false);
        }

        isAnimating = false;
        updateDots();
    });

    /*
     * Open a gallery event.
     */
    document
        .querySelectorAll('.gallery-item')
        .forEach(item => {
            item.addEventListener('click', () => {
                openEvent(
                    Number.parseInt(
                        item.dataset.event,
                        10
                    )
                );
            });
        });

    /*
     * Previous and next buttons.
     */
    previousButton.addEventListener('click', () => {
        goTo(current - 1);
        restartAutoplay();
    });

    nextButton.addEventListener('click', () => {
        goTo(current + 1);
        restartAutoplay();
    });

    /*
     * Close button.
     */
    closeButton.addEventListener(
        'click',
        closeLightbox
    );

    /*
     * Pause when the mouse is over the carousel.
     */
    carousel.addEventListener(
        'mouseenter',
        stopAutoplay
    );

    carousel.addEventListener(
        'mouseleave',
        startAutoplay
    );

    /*
     * Close when clicking the dark background.
     */
    lightbox.addEventListener('click', event => {
        if (event.target === lightbox) {
            closeLightbox();
        }
    });

    /*
     * Keyboard controls.
     */
    document.addEventListener('keydown', event => {
        if (!lightbox.classList.contains('open')) {
            return;
        }

        if (event.key === 'Escape') {
            closeLightbox();
        }

        if (event.key === 'ArrowLeft') {
            goTo(current - 1);
            restartAutoplay();
        }

        if (event.key === 'ArrowRight') {
            goTo(current + 1);
            restartAutoplay();
        }
    });

    /*
     * Pause autoplay when the browser tab is inactive.
     */
    document.addEventListener(
        'visibilitychange',
        () => {
            if (document.hidden) {
                stopAutoplay();
            } else {
                startAutoplay();
            }
        }
    );
</script>
</body>
</html>