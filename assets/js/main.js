// HomewareOnTap - Main JavaScript

document.addEventListener('DOMContentLoaded', function() {

    // =========================================================
    // Mobile Menu Toggle
    // =========================================================
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const mobileMenu = document.getElementById('mobileMenu');
    const closeMobileMenu = document.getElementById('closeMobileMenu');
    const overlay = document.getElementById('overlay');

    function openMobileMenu() {
        if (mobileMenu) mobileMenu.classList.add('open');
        if (overlay) overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeMobileMenuHandler() {
        if (mobileMenu) mobileMenu.classList.remove('open');
        if (overlay) overlay.classList.remove('active');
        document.body.style.overflow = 'auto';
    }

    if (mobileMenuToggle) mobileMenuToggle.addEventListener('click', openMobileMenu);
    if (closeMobileMenu) closeMobileMenu.addEventListener('click', closeMobileMenuHandler);
    if (overlay) overlay.addEventListener('click', closeMobileMenuHandler);

    // =========================================================
    // Search Toggle
    // =========================================================
    const searchToggle = document.getElementById('searchToggle');
    const searchBox = document.getElementById('searchBox');
    const cartPreview = document.getElementById('cartPreview');

    if (searchToggle) {
        searchToggle.addEventListener('click', function(e) {
            e.preventDefault();
            if (searchBox) searchBox.classList.toggle('active');
            if (cartPreview) cartPreview.classList.remove('active');
        });
    }

    // =========================================================
    // Cart Preview Toggle
    // =========================================================
    const cartToggle = document.getElementById('cartToggle');

    if (cartToggle) {
        cartToggle.addEventListener('click', function(e) {
            e.preventDefault();
            if (cartPreview) cartPreview.classList.toggle('active');
            if (searchBox) searchBox.classList.remove('active');
        });
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (searchToggle && searchBox) {
            if (!searchToggle.contains(e.target) && !searchBox.contains(e.target)) {
                searchBox.classList.remove('active');
            }
        }
        if (cartToggle && cartPreview) {
            if (!cartToggle.contains(e.target) && !cartPreview.contains(e.target)) {
                cartPreview.classList.remove('active');
            }
        }
    });

    // =========================================================
    // Product Category Filtering
    // =========================================================
    const filterButtons = document.querySelectorAll('.filter-btn');
    const productItems = document.querySelectorAll('#productGrid > [data-category]');

    filterButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            filterButtons.forEach(function(btn) { btn.classList.remove('active'); });
            this.classList.add('active');

            const filter = this.getAttribute('data-filter');

            productItems.forEach(function(item) {
                if (filter === 'all' || item.getAttribute('data-category') === filter) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });

    // =========================================================
    // Add to Cart (UI feedback only — backend handled by cart.js)
    // =========================================================
    const notification = document.getElementById('notification') || document.getElementById('cartNotification');
    const cartCountEl = document.querySelector('.cart-count') || document.querySelector('.cart-badge');

    function showNotification(message) {
        if (!notification) return;
        notification.textContent = message;
        notification.classList.add('show');
        setTimeout(function() {
            notification.classList.remove('show');
        }, 3000);
    }

    // =========================================================
    // Newsletter Form
    // =========================================================
    const newsletterForm = document.getElementById('newsletterForm');

    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const email = this.querySelector('input[type="email"]').value;
            console.log('Subscribed with email:', email);
            showNotification('Thanks for subscribing to our newsletter!');
            this.reset();
        });
    }

    // =========================================================
    // Back to Top Button
    // =========================================================
    const toTopButton = document.getElementById('toTop');

    if (toTopButton) {
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                toTopButton.classList.add('visible');
            } else {
                toTopButton.classList.remove('visible');
            }
        });

        toTopButton.addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // =========================================================
    // Search Input — live product filtering
    // =========================================================
    const searchInput = document.querySelector('#searchBox input');

    if (searchInput && productItems.length > 0) {
        searchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            productItems.forEach(function(item) {
                const titleEl = item.querySelector('.card-title') || item.querySelector('.product-title');
                if (titleEl) {
                    item.style.display = titleEl.textContent.toLowerCase().includes(searchTerm) ? 'block' : 'none';
                }
            });
        });
    }

});