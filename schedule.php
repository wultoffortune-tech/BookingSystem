<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once 'config/database.php';

// Include header
include 'includes/header.php';

// ========================================
// FILTER LOGIC
// ========================================

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$route_filter = isset($_GET['route_filter']) ? (int)$_GET['route_filter'] : 0;
$date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : '';
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'departure_asc';

// ✅ Base query - Only show future schedules with available seats
$sql = "SELECT s.*, 
        r.original_city, r.destination, r.base_fare,
        b.bus_name, b.bus_type, b.total_seats
        FROM schedule s
        JOIN route r ON s.route_id = r.route_id
        JOIN bus b ON s.bus_id = b.bus_id
        WHERE s.departure_time > NOW() 
        AND s.available_seats > 0
        AND s.expired = 0";

$params = [];

// Apply search filter
if (!empty($search)) {
    $sql .= " AND (r.original_city LIKE :search 
              OR r.destination LIKE :search 
              OR b.bus_name LIKE :search)";
    $params['search'] = '%' . $search . '%';
}

// Apply route filter
if ($route_filter > 0) {
    $sql .= " AND r.route_id = :route_id";
    $params['route_id'] = $route_filter;
}

// Apply date filter
if (!empty($date_filter)) {
    // ✅ Validate date format
    $date_obj = DateTime::createFromFormat('Y-m-d', $date_filter);
    if ($date_obj && $date_obj->format('Y-m-d') === $date_filter) {
        $sql .= " AND DATE(s.departure_time) = :date_filter";
        $params['date_filter'] = $date_filter;
    }
}

// Apply sort
switch ($sort_by) {
    case 'departure_asc':
        $sql .= " ORDER BY s.departure_time ASC";
        break;
    case 'departure_desc':
        $sql .= " ORDER BY s.departure_time DESC";
        break;
    case 'price_asc':
        $sql .= " ORDER BY s.price ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY s.price DESC";
        break;
    case 'seats_asc':
        $sql .= " ORDER BY s.available_seats ASC";
        break;
    case 'seats_desc':
        $sql .= " ORDER BY s.available_seats DESC";
        break;
    default:
        $sql .= " ORDER BY s.departure_time ASC";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$schedules = $stmt->fetchAll();

// Get all routes for filter dropdown
$routes = $pdo->query("SELECT route_id, original_city, destination FROM route ORDER BY original_city")->fetchAll();

// Get filter statistics
$total_schedules = count($schedules);
$available_seats_total = 0;
foreach ($schedules as $s) {
    $available_seats_total += $s['available_seats'];
}
?>

<!-- ===== SCHEDULE CSS ===== -->
<style>
    .schedule-section {
        padding: 120px 0 60px;
        min-height: 70vh;
        background: #0F172A;
    }

    .schedule-section h1 {
        font-size: 36px;
        font-weight: 700;
        color: #FFFFFF;
        margin-bottom: 8px;
    }

    .schedule-section .subtitle {
        color: #94A3B8;
        font-size: 16px;
        margin-bottom: 30px;
    }

    /* ===== FILTER BAR ===== */
    .filter-bar {
        background: #1E293B;
        border-radius: 12px;
        padding: 20px 24px;
        margin-bottom: 30px;
        border: 1px solid rgba(255, 255, 255, 0.04);
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        align-items: flex-end;
    }

    .filter-group {
        flex: 1;
        min-width: 160px;
    }

    .filter-group label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        color: #94A3B8;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }

    .filter-group input,
    .filter-group select {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid rgba(255, 255, 255, 0.04);
        border-radius: 8px;
        font-size: 14px;
        font-family: 'Poppins', sans-serif;
        background: rgba(255, 255, 255, 0.02);
        color: #FFFFFF;
        transition: all 0.3s ease;
    }

    .filter-group input:focus,
    .filter-group select:focus {
        outline: none;
        border-color: #38BDF8;
        box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.04);
    }

    .filter-group input::placeholder {
        color: #64748B;
    }

    .filter-group select option {
        background: #1E293B;
        color: #FFFFFF;
    }

    .filter-actions {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
    }

    .btn-filter {
        padding: 10px 24px;
        background: #38BDF8;
        color: #0F172A;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        font-family: 'Poppins', sans-serif;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        height: 44px;
    }

    .btn-filter:hover {
        background: #0EA5E9;
        transform: translateY(-2px);
    }

    .btn-reset {
        padding: 10px 20px;
        background: transparent;
        color: #94A3B8;
        border: 1px solid rgba(255, 255, 255, 0.04);
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        font-family: 'Poppins', sans-serif;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        height: 44px;
    }

    .btn-reset:hover {
        background: rgba(255, 255, 255, 0.02);
        color: #FFFFFF;
    }

    /* ===== FILTER STATS ===== */
    .filter-stats {
        display: flex;
        gap: 30px;
        flex-wrap: wrap;
        margin-bottom: 20px;
        padding: 12px 20px;
        background: rgba(56, 189, 248, 0.02);
        border-radius: 8px;
        border: 1px solid rgba(56, 189, 248, 0.04);
    }

    .filter-stats .stat-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        color: #94A3B8;
    }

    .filter-stats .stat-item strong {
        color: #FFFFFF;
        font-weight: 600;
    }

    .filter-stats .stat-item i {
        color: #38BDF8;
    }

    /* ===== SCHEDULE GRID ===== */
    .schedule-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 20px;
    }

    /* ===== WHITE CARDS ===== */
    .schedule-card {
        background: #FFFFFF;
        border-radius: 12px;
        padding: 20px 24px;
        border: 1px solid #E8EDF2;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
    }

    .schedule-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
        border-color: #38BDF8;
    }

    .schedule-card .route {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 18px;
        font-weight: 600;
        color: #1A1A2E;
        margin-bottom: 8px;
    }

    .schedule-card .route i {
        color: #94A3B8;
        font-size: 14px;
    }

    .schedule-card .bus-info {
        display: flex;
        gap: 16px;
        flex-wrap: wrap;
        font-size: 14px;
        color: #6B7A8A;
        margin-bottom: 12px;
    }

    .schedule-card .bus-info span {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .schedule-card .bus-info i {
        color: #38BDF8;
        width: 16px;
    }

    .schedule-card .time-info {
        display: flex;
        justify-content: space-between;
        padding: 12px 0;
        border-top: 1px solid #F1F5F9;
        border-bottom: 1px solid #F1F5F9;
        margin-bottom: 12px;
    }

    .schedule-card .time-info .time {
        font-size: 14px;
        color: #1A1A2E;
    }

    .schedule-card .time-info .time span {
        display: block;
        font-size: 12px;
        color: #94A3B8;
    }

    .schedule-card .footer-info {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .schedule-card .footer-info .price {
        font-size: 20px;
        font-weight: 700;
        color: #2563EB;
    }

    .schedule-card .footer-info .price span {
        font-size: 13px;
        font-weight: 400;
        color: #6B7A8A;
    }

    .schedule-card .btn-book {
        padding: 8px 20px;
        background: #2563EB;
        color: #FFFFFF;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        font-family: 'Poppins', sans-serif;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
    }

    .schedule-card .btn-book:hover {
        background: #1D4ED8;
        transform: translateY(-2px);
    }

    .schedule-card .btn-book.disabled {
        pointer-events: none;
        opacity: 0.55;
        background: #9CA3AF;
    }

    /* ===== STATUS BADGE ===== */
    .status-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 4px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.02em;
        margin-bottom: 10px;
    }

    .status-badge.available {
        background: #D1FAE5;
        color: #065F46;
        border: 1px solid #A7F3D0;
    }

    /* ===== NO SCHEDULES ===== */
    .no-schedules {
        text-align: center;
        padding: 60px 20px;
        color: #94A3B8;
    }

    .no-schedules i {
        font-size: 48px;
        margin-bottom: 16px;
        opacity: 0.3;
    }

    .no-schedules h3 {
        color: #FFFFFF;
        margin-bottom: 8px;
    }

    /* ===== MOBILE RESPONSIVE ===== */
    @media (max-width: 768px) {
        .schedule-section {
            padding: 100px 0 40px;
        }

        .schedule-grid {
            grid-template-columns: 1fr;
        }

        .schedule-card .route {
            font-size: 16px;
        }

        .filter-bar {
            flex-direction: column;
            padding: 16px;
        }

        .filter-group {
            min-width: 100%;
        }

        .filter-actions {
            width: 100%;
            flex-direction: column;
        }

        .filter-actions .btn-filter,
        .filter-actions .btn-reset {
            width: 100%;
            justify-content: center;
        }

        .filter-stats {
            flex-direction: column;
            gap: 8px;
        }
    }

    @media (max-width: 480px) {
        .schedule-card {
            padding: 16px;
        }
    }
</style>

<!-- ===== SCHEDULE SECTION ===== -->
<section class="schedule-section">
    <div class="container">

        <h1>Bus Schedules</h1>
        <p class="subtitle">Find available buses for your journey</p>

        <!-- ===== FILTER BAR ===== -->
        <div class="filter-bar">
            <div class="filter-group">
                <label for="search"><i class="fas fa-search"></i> Search</label>
                <input type="text" id="search" name="search"
                    placeholder="City or bus name..."
                    value="<?php echo htmlspecialchars($search); ?>">
            </div>

            <div class="filter-group">
                <label for="route_filter"><i class="fas fa-route"></i> Route</label>
                <select id="route_filter" name="route_filter">
                    <option value="0">All Routes</option>
                    <?php foreach ($routes as $route): ?>
                        <option value="<?php echo $route['route_id']; ?>"
                            <?php echo $route_filter == $route['route_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($route['original_city']); ?> →
                            <?php echo htmlspecialchars($route['destination']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label for="date_filter"><i class="fas fa-calendar"></i> Date</label>
                <input type="date" id="date_filter" name="date_filter"
                    value="<?php echo htmlspecialchars($date_filter); ?>">
            </div>

            <div class="filter-group">
                <label for="sort_by"><i class="fas fa-sort"></i> Sort By</label>
                <select id="sort_by" name="sort_by">
                    <option value="departure_asc" <?php echo $sort_by == 'departure_asc' ? 'selected' : ''; ?>>Departure (Earliest)</option>
                    <option value="departure_desc" <?php echo $sort_by == 'departure_desc' ? 'selected' : ''; ?>>Departure (Latest)</option>
                    <option value="price_asc" <?php echo $sort_by == 'price_asc' ? 'selected' : ''; ?>>Price (Low to High)</option>
                    <option value="price_desc" <?php echo $sort_by == 'price_desc' ? 'selected' : ''; ?>>Price (High to Low)</option>
                    <option value="seats_asc" <?php echo $sort_by == 'seats_asc' ? 'selected' : ''; ?>>Seats (Lowest)</option>
                    <option value="seats_desc" <?php echo $sort_by == 'seats_desc' ? 'selected' : ''; ?>>Seats (Highest)</option>
                </select>
            </div>

            <div class="filter-actions">
                <button type="button" class="btn-filter" id="applyFilters">
                    <i class="fas fa-filter"></i> Apply Filters
                </button>
                <a href="schedule.php" class="btn-reset">
                    <i class="fas fa-undo"></i> Reset
                </a>
            </div>
        </div>

        <!-- ===== FILTER STATS ===== -->
        <?php if ($total_schedules > 0): ?>
            <div class="filter-stats">
                <div class="stat-item">
                    <i class="fas fa-bus"></i>
                    <strong><?php echo $total_schedules; ?></strong> schedules found
                </div>
                <div class="stat-item">
                    <i class="fas fa-chair"></i>
                    <strong><?php echo $available_seats_total; ?></strong> total seats available
                </div>
                <?php if (!empty($search)): ?>
                    <div class="stat-item">
                        <i class="fas fa-search"></i>
                        Searching for: <strong>"<?php echo htmlspecialchars($search); ?>"</strong>
                    </div>
                <?php endif; ?>
                <?php if ($route_filter > 0): ?>
                    <?php
                    $route_name = '';
                    foreach ($routes as $r) {
                        if ($r['route_id'] == $route_filter) {
                            $route_name = $r['original_city'] . ' → ' . $r['destination'];
                            break;
                        }
                    }
                    ?>
                    <div class="stat-item">
                        <i class="fas fa-route"></i>
                        Route: <strong><?php echo htmlspecialchars($route_name); ?></strong>
                    </div>
                <?php endif; ?>
                <?php if (!empty($date_filter)): ?>
                    <div class="stat-item">
                        <i class="fas fa-calendar"></i>
                        Date: <strong><?php echo date('d M Y', strtotime($date_filter)); ?></strong>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- ===== SCHEDULE LIST ===== -->
        <?php if ($total_schedules > 0): ?>
            <div class="schedule-grid">
                <?php foreach ($schedules as $schedule): ?>
                    <div class="schedule-card">
                        <div class="route">
                            <?php echo htmlspecialchars($schedule['original_city']); ?>
                            <i class="fas fa-arrow-right"></i>
                            <?php echo htmlspecialchars($schedule['destination']); ?>
                        </div>

                        <div class="bus-info">
                            <span><i class="fas fa-bus"></i> <?php echo htmlspecialchars($schedule['bus_name']); ?></span>
                            <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($schedule['bus_type']); ?></span>
                            <span><i class="fas fa-chair"></i> <?php echo $schedule['available_seats']; ?> seats left</span>
                        </div>

                        <div class="status-badge available">Available</div>

                        <div class="time-info">
                            <div class="time">
                                <?php echo date('H:i', strtotime($schedule['departure_time'])); ?>
                                <span><?php echo date('d M Y', strtotime($schedule['departure_time'])); ?></span>
                            </div>
                            <div class="time" style="text-align: center;">
                                <i class="fas fa-clock" style="color: #94A3B8;"></i>
                                <span>Departure</span>
                            </div>
                            <div class="time" style="text-align: right;">
                                <?php echo date('H:i', strtotime($schedule['arrival_time'])); ?>
                                <span><?php echo date('d M Y', strtotime($schedule['arrival_time'])); ?></span>
                            </div>
                        </div>

                        <div class="footer-info">
                            <div class="price">
                                <?php
                                $display_price = isset($schedule['price']) && $schedule['price'] > 0
                                    ? $schedule['price']
                                    : (isset($schedule['base_fare']) ? $schedule['base_fare'] : 0);
                                ?>
                                XAF <?php echo number_format($display_price, 0); ?>
                                <span>/ seat</span>
                            </div>
                            <a href="booking.php?schedule_id=<?php echo $schedule['schedule_id']; ?>" class="btn-book">
                                <i class="fas fa-ticket-alt"></i> Book
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-schedules">
                <i class="fas fa-calendar-times"></i>
                <h3>No Schedules Found</h3>
                <p>Try adjusting your search filters or check back later for new schedules.</p>
                <a href="schedule.php" class="btn-filter" style="margin-top:16px; text-decoration:none; display:inline-block;">
                    <i class="fas fa-undo"></i> Clear All Filters
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ===== FILTER JAVASCRIPT ===== -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get filter elements
        const search = document.getElementById('search');
        const routeFilter = document.getElementById('route_filter');
        const dateFilter = document.getElementById('date_filter');
        const sortBy = document.getElementById('sort_by');
        const applyBtn = document.getElementById('applyFilters');

        // Apply filters when button is clicked
        if (applyBtn) {
            applyBtn.addEventListener('click', function() {
                applyFilters();
            });
        }

        // Apply filters on Enter key in search field
        if (search) {
            search.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    applyFilters();
                }
            });
        }

        // Apply filters when date changes
        if (dateFilter) {
            dateFilter.addEventListener('change', function() {
                applyFilters();
            });
        }

        // Apply filters when sort changes
        if (sortBy) {
            sortBy.addEventListener('change', function() {
                applyFilters();
            });
        }

        // Apply filters when route changes
        if (routeFilter) {
            routeFilter.addEventListener('change', function() {
                applyFilters();
            });
        }

        function applyFilters() {
            const params = new URLSearchParams();

            const searchVal = search ? search.value.trim() : '';
            const routeVal = routeFilter ? routeFilter.value : '0';
            const dateVal = dateFilter ? dateFilter.value : '';
            const sortVal = sortBy ? sortBy.value : 'departure_asc';

            if (searchVal) params.set('search', searchVal);
            if (routeVal && routeVal !== '0') params.set('route_filter', routeVal);
            if (dateVal) params.set('date_filter', dateVal);
            if (sortVal && sortVal !== 'departure_asc') params.set('sort_by', sortVal);

            window.location.href = 'schedule.php?' + params.toString();
        }

        console.log('🔍 Schedule filter ready');
    });
</script>

<?php
// Include footer
include 'includes/footer.php';
?>