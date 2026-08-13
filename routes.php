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
    .page-section {
        padding: 140px 0 90px;
    }

    .route-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 22px;
        margin-top: 40px;
    }

    .route-card {
        background: rgba(255, 255, 255, 0.06);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 24px;
        padding: 28px;
        box-shadow: 0 18px 50px rgba(8, 14, 34, 0.12);
        transition: transform 0.3s ease, border-color 0.3s ease;
    }

    .route-card:hover {
        transform: translateY(-4px);
        border-color: rgba(124, 92, 255, 0.3);
    }

    .route-card h3 {
        margin-bottom: 14px;
        color: #ffffff;
        font-size: 22px;
    }

    .route-card p {
        color: #cbd5e1;
        line-height: 1.8;
        margin-bottom: 18px;
    }

    .route-details {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 16px;
        align-items: center;
    }

    .route-price {
        color: #38bdf8;
        font-weight: 700;
        font-size: 20px;
    }

    .route-meta {
        display: flex;
        gap: 14px;
        flex-wrap: wrap;
        margin-top: 12px;
    }

    .route-meta span {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #94a3b8;
        font-size: 14px;
    }

    .route-meta i {
        color: #7c5cff;
    }

    .no-results {
        text-align: center;
        padding: 80px 20px;
        color: #94a3b8;
    }

    .no-results i {
        font-size: 48px;
        margin-bottom: 18px;
        display: block;
    }
</style>

<section class="page-section">
    <div class="container">
        <div class="section-header">
            <span class="subtitle">Available Routes</span>
            <h2 class="section-title">Choose your next trip</h2>
            <p class="section-description">See all active bus connections and their base fare. Planning is easier when the route is clear.</p>
        </div>

        <?php if (count($routes) > 0): ?>
            <div class="route-grid">
                <?php foreach ($routes as $route): ?>
                    <div class="route-card">
                        <h3><?php echo htmlspecialchars($route['original_city']); ?> <i class="fas fa-arrow-right"></i> <?php echo htmlspecialchars($route['destination']); ?></h3>
                        <p>Enjoy a dependable trip between these cities with transparent pricing and reliable service.</p>
                        <div class="route-details">
                            <span class="route-price">XAF <?php echo number_format($route['base_fare'], 0); ?></span>
                            <a href="schedule.php" class="btn btn-secondary">View schedules</a>
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
