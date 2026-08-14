<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';
include 'includes/header.php';

$sql = "SELECT route_id, original_city, destination, IFNULL(base_fare, 0) AS base_fare
        FROM route
        ORDER BY original_city ASC, destination ASC";
$stmt = $pdo->query($sql);
$routes = $stmt->fetchAll();
?>

<style>
    /* ===== MATCHING ADMIN DARK THEME ===== */
    .page-section {
        padding: 140px 0 90px;
        background: #0F172A;
        font-family: 'Poppins', sans-serif;
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 24px;
    }

    .section-header {
        text-align: center;
        margin-bottom: 40px;
    }

    .section-header .subtitle {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(56, 189, 248, 0.08);
        color: #38BDF8;
        padding: 6px 18px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        border: 1px solid rgba(56, 189, 248, 0.1);
        margin-bottom: 12px;
    }

    .section-title {
        font-size: 32px;
        font-weight: 700;
        color: #FFFFFF;
        margin: 0 0 8px 0;
    }

    .section-description {
        color: #94A3B8;
        max-width: 620px;
        margin: 0 auto;
        line-height: 1.8;
        font-size: 15px;
    }

    /* ===== ROUTE GRID ===== */
    .route-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 22px;
        margin-top: 40px;
    }

    /* ===== DARK ROUTE CARDS ===== */
    .route-card {
        background: #1E293B;
        border: 1px solid rgba(255, 255, 255, 0.04);
        border-radius: 12px;
        padding: 28px 24px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        transition: all 0.3s ease;
    }

    .route-card:hover {
        transform: translateY(-4px);
        border-color: rgba(56, 189, 248, 0.2);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
    }

    .route-card h3 {
        margin-bottom: 10px;
        color: #FFFFFF;
        font-size: 20px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .route-card h3 i {
        color: #38BDF8;
        font-size: 14px;
    }

    .route-card p {
        color: #94A3B8;
        line-height: 1.8;
        margin-bottom: 18px;
        font-size: 14px;
    }

    .route-details {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        border-top: 1px solid rgba(255, 255, 255, 0.04);
        padding-top: 16px;
    }

    .route-price {
        color: #38BDF8;
        font-weight: 700;
        font-size: 22px;
    }

    .route-price span {
        font-size: 14px;
        font-weight: 400;
        color: #64748B;
    }

    /* ===== ACTION BUTTON ===== */
    .btn-secondary {
        background: rgba(56, 189, 248, 0.06);
        color: #38BDF8;
        border: 1px solid rgba(56, 189, 248, 0.1);
        padding: 8px 20px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s ease;
        font-family: 'Poppins', sans-serif;
        display: inline-block;
    }

    .btn-secondary:hover {
        background: #38BDF8;
        color: #0F172A;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(56, 189, 248, 0.2);
    }

    /* ===== ROUTE META TAGS ===== */
    .route-meta {
        display: flex;
        gap: 14px;
        flex-wrap: wrap;
        margin-top: 14px;
    }

    .route-meta span {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #64748B;
        font-size: 13px;
    }

    .route-meta i {
        color: #38BDF8;
    }

    /* ===== NO RESULTS ===== */
    .no-results {
        text-align: center;
        padding: 80px 20px;
        color: #94A3B8;
    }

    .no-results i {
        font-size: 48px;
        margin-bottom: 18px;
        display: block;
        opacity: 0.3;
        color: #475569;
    }

    .no-results h3 {
        color: #FFFFFF;
        margin-bottom: 8px;
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 768px) {
        .page-section {
            padding: 100px 0 60px;
        }

        .route-grid {
            grid-template-columns: 1fr;
        }

        .route-card {
            padding: 20px;
        }

        .section-title {
            font-size: 26px;
        }
    }
</style>

<section class="page-section">
    <div class="container">
        <div class="section-header">
            <span class="subtitle"><i class="fas fa-route"></i> Available Routes</span>
            <h2 class="section-title">Choose your next trip</h2>
            <p class="section-description">See all active bus connections and their base fare. Planning is easier when the route is clear.</p>
        </div>

        <?php if (count($routes) > 0): ?>
            <div class="route-grid">
                <?php foreach ($routes as $route): ?>
                    <div class="route-card">
                        <h3>
                            <?php echo htmlspecialchars($route['original_city']); ?>
                            <i class="fas fa-arrow-right"></i>
                            <?php echo htmlspecialchars($route['destination']); ?>
                        </h3>
                        <p>Enjoy a dependable trip between these cities with transparent pricing and reliable service.</p>
                        <div class="route-details">
                            <div class="route-price">
                                XAF <?php echo number_format($route['base_fare'], 0); ?>
                                <span>/ seat</span>
                            </div>
                            <a href="schedule.php" class="btn-secondary">
                                <i class="fas fa-calendar-alt"></i> View schedules
                            </a>
                        </div>
                        <div class="route-meta">
                            <span><i class="fas fa-map-marker-alt"></i> From <?php echo htmlspecialchars($route['original_city']); ?></span>
                            <span><i class="fas fa-flag-checkered"></i> To <?php echo htmlspecialchars($route['destination']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-results">
                <i class="fas fa-route"></i>
                <h3>No routes found</h3>
                <p>There are no available routes in the system yet. Reach out to the administrator to add new connections.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php';
