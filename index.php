<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$room_type = isset($_GET['room_type']) ? sanitize($_GET['room_type']) : '';
$max_price = isset($_GET['max_price']) ? (int)$_GET['max_price'] : 500;

$query = "SELECT * FROM rooms WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (room_number LIKE :search OR description LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($room_type) {
    $query .= " AND room_type = :room_type";
    $params[':room_type'] = $room_type;
}

$query .= " AND price_per_night <= :max_price";
$params[':max_price'] = $max_price;

$query .= " ORDER BY room_type, price_per_night";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$rooms = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Luxury Stays - Premium Hotel Experience</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <style>
        :root {
            --primary-blue: #1e3a5f;
            --primary-pink: #2d4a7c;
            --accent-blue: #4a90e2;
            --accent-pink: #5aa3e0;
            --dark-text: #e8f4f8;
            --light-bg: #0d1621;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0a0e27 0%, #1a1f3a 100%);
            min-height: 100vh;
            color: var(--dark-text);
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-pink) 100%);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            padding: 1rem 0;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.8rem;
            color: white !important;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .nav-link {
            color: white !important;
            font-weight: 500;
            margin: 0 0.5rem;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            transform: translateY(-2px);
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .btn-custom {
            background: linear-gradient(135deg, var(--accent-blue) 0%, var(--accent-pink) 100%);
            border: none;
            color: white;
            padding: 0.6rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.3);
            color: white;
        }

        .hero-section {
            background: linear-gradient(135deg, rgba(30, 58, 95, 0.9) 0%, rgba(45, 74, 124, 0.9) 100%),
                        url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 600"><rect fill="%230d1621" width="1200" height="600"/></svg>');
            background-size: cover;
            padding: 5rem 0;
            margin-bottom: 3rem;
            border-radius: 0 0 50px 50px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }

        .hero-section h1 {
            font-size: 3.5rem;
            font-weight: 800;
            color: white;
            text-shadow: 3px 3px 6px rgba(0,0,0,0.3);
            margin-bottom: 1.5rem;
        }

        .hero-section p {
            font-size: 1.3rem;
            color: white;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .search-card {
            background: #1a1f3a;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
            margin-bottom: 3rem;
            border: 3px solid var(--accent-blue);
            position: relative;
        }

        .search-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            border-radius: 20px;
            padding: 3px;
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-pink));
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            z-index: -1;
        }

        .form-control, .form-select {
            border-radius: 15px;
            border: 2px solid #2d4a7c;
            background: #0d1621;
            color: var(--dark-text);
            padding: 0.8rem;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--accent-blue);
            background: #151e30;
            box-shadow: 0 0 0 0.2rem rgba(74, 144, 226, 0.25);
            color: var(--dark-text);
        }

        .room-card {
            background: #1a1f3a;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
            transition: all 0.4s ease;
            height: 100%;
            border: 2px solid #2d4a7c;
        }

        .room-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 12px 30px rgba(74, 144, 226, 0.4);
            border-color: var(--accent-pink);
        }

        .room-card-header {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-pink) 100%);
            color: white;
            padding: 1.5rem;
            text-align: center;
        }

        .room-card-header h3 {
            margin: 0;
            font-weight: 700;
            font-size: 1.5rem;
        }

        .room-card-body {
            padding: 1.5rem;
        }

        .room-type-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .badge-single {
            background: linear-gradient(135deg, #FFE5EC 0%, #FFC0CB 100%);
            color: #C71585;
        }

        .badge-double {
            background: linear-gradient(135deg, #E0F4FF 0%, #B0E0E6 100%);
            color: #006994;
        }

        .badge-suite {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            color: #8B4513;
        }

        .price-tag {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-pink));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 1rem 0;
        }

        .room-features {
            list-style: none;
            padding: 0;
            margin: 1rem 0;
        }

        .room-features li {
            padding: 0.5rem 0;
            color: var(--dark-text);
        }

        .room-features i {
            color: var(--accent-blue);
            margin-right: 0.5rem;
            width: 20px;
        }

        .status-badge {
            padding: 0.4rem 1rem;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-available {
            background: linear-gradient(135deg, #90EE90 0%, #98FB98 100%);
            color: #006400;
        }

        .status-occupied {
            background: linear-gradient(135deg, #FFB6C1 0%, #FFC0CB 100%);
            color: #8B0000;
        }

        .status-maintenance {
            background: linear-gradient(135deg, #FFE5B4 0%, #FFDAB9 100%);
            color: #8B4500;
        }

        footer {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-pink) 100%);
            color: white;
            padding: 2rem 0;
            margin-top: 4rem;
            box-shadow: 0 -4px 12px rgba(0,0,0,0.2);
        }

        .filter-label {
            font-weight: 600;
            color: var(--dark-text);
            margin-bottom: 0.5rem;
            display: block;
        }

        .price-display {
            font-weight: 700;
            color: var(--accent-pink);
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-hotel"></i> Luxury Stays
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="fas fa-home"></i> Home</a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                        </li>
                        <?php if (isAdmin()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="manage_rooms.php"><i class="fas fa-cogs"></i> Manage Rooms</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="manage_bookings.php"><i class="fas fa-calendar-check"></i> Bookings</a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <span class="nav-link"><i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-custom ms-2" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="btn btn-custom" href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-custom ms-2" href="register.php"><i class="fas fa-user-plus"></i> Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="hero-section">
        <div class="container text-center">
            <h1><i class="fas fa-gem"></i> Welcome to Luxury Stays</h1>
            <p class="lead">Experience Premium Comfort & Unmatched Hospitality</p>
        </div>
    </div>

    <div class="container">
        <div class="search-card">
            <h3 class="text-center mb-4" style="color: var(--dark-text); font-weight: 700;">
                <i class="fas fa-search"></i> Find Your Perfect Room
            </h3>
            <form method="GET" action="index.php">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="filter-label"><i class="fas fa-keyboard"></i> Search</label>
                        <input type="text" name="search" class="form-control" placeholder="Room number or description" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="filter-label"><i class="fas fa-bed"></i> Room Type</label>
                        <select name="room_type" class="form-select">
                            <option value="">All Types</option>
                            <option value="single" <?php echo $room_type === 'single' ? 'selected' : ''; ?>>Single</option>
                            <option value="double" <?php echo $room_type === 'double' ? 'selected' : ''; ?>>Double</option>
                            <option value="suite" <?php echo $room_type === 'suite' ? 'selected' : ''; ?>>Suite</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="filter-label"><i class="fas fa-rupee-sign"></i> Max Price: <span class="price-display">₹<?php echo $max_price; ?></span></label>
                        <input type="range" name="max_price" class="form-range" min="50" max="500" step="10" value="<?php echo $max_price; ?>" oninput="this.previousElementSibling.querySelector('.price-display').textContent='₹'+this.value">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-custom w-100">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <h2 class="text-center mb-4" style="color: var(--dark-text); font-weight: 700;">
            <i class="fas fa-door-open"></i> Available Rooms
        </h2>

        <div class="row g-4 mb-5">
            <?php if (empty($rooms)): ?>
                <div class="col-12">
                    <div class="alert alert-info text-center" style="border-radius: 20px; background: #1a1f3a; border: 2px solid #2d4a7c; color: var(--dark-text);">
                        <i class="fas fa-info-circle"></i> No rooms found matching your criteria.
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($rooms as $room): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="room-card">
                            <div class="room-card-header">
                                <h3><i class="fas fa-door-open"></i> Room <?php echo htmlspecialchars($room['room_number']); ?></h3>
                            </div>
                            <div class="room-card-body">
                                <div class="text-center">
                                    <span class="room-type-badge badge-<?php echo $room['room_type']; ?>">
                                        <i class="fas fa-bed"></i> <?php echo ucfirst($room['room_type']); ?> Room
                                    </span>
                                </div>

                                <div class="price-tag text-center">
                                    ₹<?php echo number_format($room['price_per_night'], 2); ?> <small style="font-size: 1rem;">/night</small>
                                </div>

                                <ul class="room-features">
                                    <li><i class="fas fa-users"></i> Max Occupancy: <?php echo $room['max_occupancy']; ?> guest(s)</li>
                                    <li><i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($room['description']); ?></li>
                                    <li>
                                        <i class="fas fa-check-circle"></i>
                                        <span class="status-badge status-<?php echo $room['status']; ?>">
                                            <?php echo ucfirst($room['status']); ?>
                                        </span>
                                    </li>
                                </ul>

                                <?php if ($room['status'] === 'available'): ?>
                                    <?php if (isLoggedIn()): ?>
                                        <a href="make_booking.php?room_id=<?php echo $room['room_id']; ?>" class="btn btn-custom w-100 mt-3">
                                            <i class="fas fa-calendar-check"></i> Book Now
                                        </a>
                                    <?php else: ?>
                                        <a href="login.php" class="btn btn-custom w-100 mt-3">
                                            <i class="fas fa-sign-in-alt"></i> Login to Book
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <button class="btn btn-secondary w-100 mt-3" disabled>
                                        <i class="fas fa-times-circle"></i> Not Available
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <footer class="text-center">
        <div class="container">
            <p class="mb-0"><i class="fas fa-hotel"></i> &copy; 2024 Luxury Stays. All rights reserved.</p>
            <p class="mb-0"><i class="fas fa-heart"></i> Premium Hotel Management System</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
