<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once 'config/database.php';

// Include header
include 'includes/home-header.php';
?>

<!-- ===== HERO SECTION ===== -->
<section class="hero" style="background-image: url('assets/images/bus-hero\ \(2\).jpg'); background-size: cover; background-position: center; background-attachment: fixed;">
    <div class="particles">
        <span></span><span></span><span></span><span></span><span></span>
        <span></span><span></span><span></span><span></span><span></span>
    </div>

    <div class="container">

        <div class="hero-content">
            <div class="hero-badge">
                <span class="dot"></span>
                Premium Bus Service
            </div>

            <h1>
                Your Journey<br><span class="highlight">Starts Here</span>
            </h1>

            <p>Book your bus tickets online with ease. Safe, secure, and hassle-free reservation system.</p>
        </div>

        <!-- Search Box -->
        <div class="search-box">
            <form class="search-form" action="search-results.php" method="GET">
                <div class="form-group">
                    <div class="form-group">
                        <label for="origin"><i class="fas fa-map-marker-alt"></i> From</label>
                    </div>
                    <input type="text" id="origin" name="origin" placeholder="Enter departure city" required>

                </div>

                <div class="form-group">
                    <label for="destination"><i class="fas fa-flag-checkered"></i> To</label>
                    <input type="text" id="destination" name="destination" placeholder="Enter destination" required>
                </div>

                <div class="form-group">
                    <label for="travel-date"><i class="fas fa-calendar"></i> Travel Date</label>
                    <input type="date" id="travel-date" name="travel_date" required>
                </div>

                <button type="submit" class="btn-search">
                    <i class="fas fa-search"></i> Find schedule
                </button>
            </form>
        </div>
    </div>
</section>

<!-- ===== FEATURES SECTION ===== -->
<section class="features">
    <div class="container">
        <div class="section-header">
            <span class="subtitle">Why Choose Us</span>
            <h2 class="section-title">
                Additional <span class="highlight">Features</span>
            </h2>
            <p class="section-description">
                Experience the best bus booking service with these amazing features
            </p>
        </div>

        <div class="features-grid">
            <div class="feature-card">
                <div class="icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3>Secure Booking</h3>
                <p>Your transactions are fully protected</p>
                <div class="line"></div>
            </div>

            <div class="feature-card">
                <div class="icon">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <h3>Instant code</h3>
                <p>Receive your code instantly </p>
                <div class="line"></div>
            </div>

            <div class="feature-card">
                <div class="icon">
                    <i class="fas fa-undo-alt"></i>
                </div>
                <h3>Easy Cancellation</h3>
                <p>Cancel your tickets with just one click</p>
                <div class="line"></div>
            </div>

            <div class="feature-card">
                <div class="icon">
                    <i class="fas fa-headset"></i>
                </div>
                <h3>24/7 Support</h3>
                <p>Our dedicated support team is always here to help</p>
                <div class="line"></div>
            </div>
        </div>
    </div>
</section>

<?php
// Include footer
include 'includes/home-footer.php';
?>