<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';
include 'includes/header.php';

$origin = '';
if (isset($_GET['origin'])) {
    $origin = trim($_GET['origin']);
}

$destination = '';
if (isset($_GET['destination'])) {
    $destination = trim($_GET['destination']);
}

$travel_date = '';
if (isset($_GET['travel_date'])) {
    $travel_date = trim($_GET['travel_date']);
} elseif (isset($_GET['date'])) {
    // Accept `date` as a fallback for older clients/scripts
    $travel_date = trim($_GET['date']);
}

$error = '';
$schedules = array();

if ($origin === '' || $destination === '' || $travel_date === '') {
    // If the user navigated here without search parameters, send them back to the home page
    header('Location: home.php');
    exit();
} else {
    $sql = "SELECT s.schedule_id, s.departure_time, s.arrival_time, s.available_seats, s.price, s.expired,
                   r.original_city, r.destination, r.base_fare,
                   b.bus_name, b.bus_type
            FROM schedule s
            JOIN route r ON s.route_id = r.route_id
            JOIN bus b ON s.bus_id = b.bus_id
            WHERE DATE(s.departure_time) = :travel_date
                        AND LOWER(CONVERT(r.original_city USING utf8)) COLLATE utf8_general_ci LIKE :origin
                            AND LOWER(CONVERT(r.destination USING utf8)) COLLATE utf8_general_ci LIKE :destination
            ORDER BY s.departure_time ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'travel_date' => $travel_date,
        'origin' => '%' . strtolower($origin) . '%',
        'destination' => '%' . strtolower($destination) . '%',
    ]);
    $schedules = $stmt->fetchAll();
}
?>

<style>
    .results-section {
        padding: 140px 0 90px;
    }

    .results-header {
        margin-bottom: 40px;
    }

    .results-grid {
        display: grid;
        gap: 24px;
    }

    .schedule-card {
        background: rgba(255, 255, 255, 0.06);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 24px;
        padding: 28px;
        box-shadow: 0 18px 50px rgba(8, 14, 34, 0.12);
    }

    .schedule-card .route {
        font-size: 20px;
        font-weight: 700;
        margin-bottom: 14px;
        color: #ffffff;
    }

    .schedule-card .meta {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 12px;
        margin-bottom: 22px;
    }

    .meta-item {
        color: #cbd5e1;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .schedule-card .price {
        font-size: 24px;
        font-weight: 700;
        color: #38bdf8;
        margin-bottom: 18px;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 8px 16px;
        border-radius: 999px;
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.02em;
        background: #fee2e2;
        color: #b91c1c;
        border: 1px solid rgba(239, 68, 68, 0.2);
    }

    .schedule-card .btn-book {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        color: #ffffff;
        background: #7c5cff;
        border-radius: 999px;
        padding: 12px 24px;
        text-decoration: none;
        transition: background 0.3s ease, transform 0.3s ease;
    }

    .schedule-card .btn-book:hover {
        background: #5a3bff;
        transform: translateY(-2px);
    }

    .error-box {
        padding: 18px 22px;
        border-radius: 20px;
        background: rgba(239, 68, 68, 0.09);
        color: #fecaca;
        margin-bottom: 30px;
        border: 1px solid rgba(239, 68, 68, 0.16);
    }

    .no-results {
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 24px;
        padding: 42px 26px;
        text-align: center;
        color: #94a3b8;
    }

    .no-results i {
        font-size: 44px;
        margin-bottom: 18px;
        display: block;
    }
</style>

<section class="results-section">
    <div class="container">
        <div class="results-header">
            <span class="subtitle">Search Results</span>
            <h2 class="section-title">Available buses for your trip</h2>
            <p class="section-description">We found the following departures based on your selected route and travel date.</p>
        </div>

        <?php if ($error): ?>
            <div class="error-box"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (count($schedules) > 0): ?>
            <div class="results-grid">
                <?php foreach ($schedules as $schedule): ?>
                    <div class="schedule-card">
                        <div class="route"><?php echo htmlspecialchars($schedule['original_city']); ?> <i class="fas fa-arrow-right"></i> <?php echo htmlspecialchars($schedule['destination']); ?></div>
                        <div class="meta">
                            <div class="meta-item"><i class="fas fa-bus"></i> <?php echo htmlspecialchars($schedule['bus_name']); ?> (<?php echo htmlspecialchars($schedule['bus_type']); ?>)</div>
                            <div class="meta-item"><i class="fas fa-calendar-day"></i> <?php echo date('d M Y', strtotime($schedule['departure_time'])); ?></div>
                            <div class="meta-item"><i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($schedule['departure_time'])); ?> - <?php echo date('H:i', strtotime($schedule['arrival_time'])); ?></div>
                            <div class="meta-item"><i class="fas fa-chair"></i> <?php echo (int)$schedule['available_seats']; ?> seats available</div>
                        </div>
                        <div class="price">XAF <?php echo number_format($schedule['price'] > 0 ? $schedule['price'] : $schedule['base_fare'], 0); ?> / seat</div>
                        <?php if (isset($schedule['expired']) && $schedule['expired'] == 1): ?>
                            <span class="status-badge expired">Expired</span>
                        <?php else: ?>
                            <a href="booking.php?schedule_id=<?php echo $schedule['schedule_id']; ?>" class="btn-book"><i class="fas fa-ticket-alt"></i> Book now</a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php elseif ($error === ''): ?>
            <div class="no-results">
                <i class="fas fa-search"></i>
                <h3>No buses found</h3>
                <p>We could not find any schedules matching your search. Try another date or route.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php';
