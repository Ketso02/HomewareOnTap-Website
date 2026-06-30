<?php
// pages/about.php - Public About page
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/session.php';

$pageTitle = 'About Us';
include __DIR__ . '/../../includes/header.php';
?>

<style>
    :root {
        --primary: #A67B5B;
        --primary-dark: #8B6145;
        --secondary: #F2E8D5;
        --light: #F9F5F0;
        --dark: #3A3229;
    }

    .about-hero {
        background: linear-gradient(135deg, rgba(166, 123, 91, 0.92), rgba(58, 50, 41, 0.92)),
                    url('<?php echo SITE_URL; ?>/assets/img/banners/hero-homeware.jpg') center/cover no-repeat;
        color: #fff;
        padding: 90px 0;
    }

    .section-kicker {
        color: var(--primary);
        font-weight: 700;
        letter-spacing: 1px;
        text-transform: uppercase;
        font-size: 0.85rem;
    }

    .soft-card {
        border: 0;
        border-radius: 18px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.07);
        background: #fff;
        height: 100%;
    }

    .icon-circle {
        width: 58px;
        height: 58px;
        border-radius: 50%;
        background: var(--secondary);
        color: var(--primary-dark);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        margin-bottom: 18px;
    }

    .about-image-box {
        background: var(--light);
        border-radius: 22px;
        padding: 18px;
    }

    .about-image-box img {
        width: 100%;
        min-height: 380px;
        object-fit: cover;
        border-radius: 18px;
    }

    .timeline-item {
        border-left: 3px solid var(--primary);
        padding-left: 20px;
        margin-bottom: 24px;
    }

    .cta-band {
        background: var(--dark);
        color: #fff;
        border-radius: 24px;
        padding: 42px;
    }

    .btn-homeware {
        background: var(--primary);
        color: #fff;
        border: 0;
        border-radius: 999px;
        padding: 12px 26px;
        font-weight: 700;
        text-decoration: none;
        display: inline-block;
    }

    .btn-homeware:hover {
        background: var(--primary-dark);
        color: #fff;
    }
</style>

<section class="about-hero text-center">
    <div class="container">
        <h1 class="display-4 fw-bold mb-3">About HomewareOnTap</h1>
        <p class="lead mb-0">Beautiful, practical homeware selected to make everyday spaces feel warmer, calmer, and more personal.</p>
    </div>
</section>

<main>
    <section class="py-5">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <span class="section-kicker">Our Story</span>
                    <h2 class="display-6 fw-bold mt-2 mb-4">Curated pieces for homes that feel lived-in and loved.</h2>
                    <p class="text-muted mb-3">
                        HomewareOnTap was created for customers who want home essentials that are stylish, useful, and easy to shop online. Our focus is on pieces that help transform a room without overcomplicating the process.
                    </p>
                    <p class="text-muted mb-4">
                        From decor accents to everyday home essentials, we aim to bring together products that suit modern living, thoughtful gifting, and simple home upgrades.
                    </p>
                    <a href="<?php echo SITE_URL; ?>/pages/shop.php" class="btn-homeware">Shop Our Collection</a>
                </div>
                <div class="col-lg-6">
                    <div class="about-image-box">
                        <img src="<?php echo SITE_URL; ?>/assets/img/banners/hero-homeware.jpg" alt="Modern homeware display" onerror="this.src='<?php echo SITE_URL; ?>/assets/img/products/primary/default-product.jpg'">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 bg-light">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-lg-8 mx-auto">
                    <span class="section-kicker">What We Value</span>
                    <h2 class="fw-bold mt-2">Designed around simple, beautiful living</h2>
                    <p class="text-muted">We believe good homeware should be easy to choose, easy to use, and beautiful enough to make your space feel special.</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="soft-card p-4 text-center">
                        <div class="icon-circle"><i class="fas fa-couch"></i></div>
                        <h5 class="fw-bold">Style with purpose</h5>
                        <p class="text-muted mb-0">Products are selected for both appearance and everyday usefulness.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="soft-card p-4 text-center">
                        <div class="icon-circle"><i class="fas fa-box-open"></i></div>
                        <h5 class="fw-bold">Easy online shopping</h5>
                        <p class="text-muted mb-0">Browse, compare, add to cart, and checkout without unnecessary steps.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="soft-card p-4 text-center">
                        <div class="icon-circle"><i class="fas fa-heart"></i></div>
                        <h5 class="fw-bold">Warm home feeling</h5>
                        <p class="text-muted mb-0">We focus on pieces that help your space feel inviting and personal.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <div class="row g-5 align-items-start">
                <div class="col-lg-5">
                    <span class="section-kicker">How We Work</span>
                    <h2 class="fw-bold mt-2 mb-3">A smoother way to shop homeware</h2>
                    <p class="text-muted">Our website is built to make product discovery and ordering simple, whether you are shopping for yourself, refreshing a room, or finding a thoughtful gift.</p>
                </div>
                <div class="col-lg-7">
                    <div class="timeline-item">
                        <h5 class="fw-bold">1. Browse curated homeware</h5>
                        <p class="text-muted mb-0">Explore categories, product details, pricing, stock, and images before adding to cart.</p>
                    </div>
                    <div class="timeline-item">
                        <h5 class="fw-bold">2. Choose what fits your space</h5>
                        <p class="text-muted mb-0">Use product pages and related recommendations to compare options.</p>
                    </div>
                    <div class="timeline-item mb-0">
                        <h5 class="fw-bold">3. Checkout with confidence</h5>
                        <p class="text-muted mb-0">Place orders through the website and manage your shopping journey from your account.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="pb-5">
        <div class="container">
            <div class="cta-band text-center">
                <h2 class="fw-bold mb-3">Ready to refresh your home?</h2>
                <p class="mb-4 text-white-50">Explore our latest products and find pieces that suit your space.</p>
                <a href="<?php echo SITE_URL; ?>/pages/shop.php" class="btn-homeware">Start Shopping</a>
            </div>
        </div>
    </section>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
