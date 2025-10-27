<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$is_admin = isAdmin();

if ($is_admin) {
    $stmt = $pdo->query("SELECT COUNT(*) as total_rooms FROM rooms");
    $total_rooms = $stmt->fetch()['total_rooms'];

    $stmt = $pdo->query("SELECT COUNT(*) as total_bookings FROM reservations");
    $total_bookings = $stmt->fetch()['total_bookings'];

    $stmt = $pdo->query("SELECT SUM(total_amount) as total_revenue FROM reservations WHERE status != 'cancelled'");
    $total_revenue = $stmt->fetch()['total_revenue'] ?? 0;

    $stmt = $pdo->query("SELECT COUNT(*) as available_rooms FROM rooms WHERE status = 'available'");
    $available_rooms = $stmt->fetch()['available_rooms'];

    $stmt = $pdo->prepare("
        SELECT r.*, rm.room_number, rm.room_type, u.username, u.email
        FROM reservations r
        JOIN rooms rm ON r.room_id = rm.room_id
        JOIN users u ON r.user_id = u.user_id
        ORDER BY r.created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $recent_bookings = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare("
        SELECT r.*, rm.room_number, rm.room_type, rm.price_per_night
        FROM reservations r
        JOIN rooms rm ON r.room_id = rm.room_id
        WHERE r.user_id = :user_id
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([':user_id' => $user_id]);
    $user_bookings = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT COUNT(*) as total_bookings FROM reservations WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $user_id]);
    $total_user_bookings = $stmt->fetch()['total_bookings'];

    $stmt = $pdo->prepare("SELECT COUNT(*) as pending_bookings FROM reservations WHERE user_id = :user_id AND status = 'pending'");
    $stmt->execute([':user_id' => $user_id]);
    $pending_bookings = $stmt->fetch()['pending_bookings'];

    $stmt = $pdo->prepare("SELECT SUM(total_amount) as total_spent FROM reservations WHERE user_id = :user_id AND status != 'cancelled'");
    $stmt->execute([':user_id' => $user_id]);
    $total_spent = $stmt->fetch()['total_spent'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Luxury Stays</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        .dashboard-header {
            background: #1a1f3a;
            border-radius: 20px;
            padding: 2rem;
            margin: 2rem 0;
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
            border: 2px solid #2d4a7c;
        }

        .dashboard-header h1 {
            color: var(--dark-text);
            font-weight: 800;
            margin: 0;
        }

        .stat-card {
            background: #1a1f3a;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
            transition: all 0.3s ease;
            height: 100%;
            border-left: 5px solid;
            border: 2px solid #2d4a7c;
            border-left: 5px solid;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(74, 144, 226, 0.4);
        }

        .stat-card.blue {
            border-color: var(--accent-blue);
        }

        .stat-card.pink {
            border-color: var(--accent-pink);
        }

        .stat-card.green {
            border-color: #90EE90;
        }

        .stat-card.orange {
            border-color: #FFA500;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
            margin-bottom: 1rem;
        }

        .stat-icon.blue {
            background: linear-gradient(135deg, var(--accent-blue), var(--primary-blue));
        }

        .stat-icon.pink {
            background: linear-gradient(135deg, var(--accent-pink), var(--primary-pink));
        }

        .stat-icon.green {
            background: linear-gradient(135deg, #90EE90, #98FB98);
        }

        .stat-icon.orange {
            background: linear-gradient(135deg, #FFA500, #FFD700);
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--dark-text);
            margin: 0.5rem 0;
        }

        .stat-label {
            font-size: 1rem;
            color: #a8b8cc;
            font-weight: 600;
        }

        .table-card {
            background: #1a1f3a;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
            margin-top: 2rem;
            border: 2px solid #2d4a7c;
        }

        .table-card h3 {
            color: var(--dark-text);
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-pink) 100%);
            color: white;
        }

        .table thead th {
            border: none;
            font-weight: 600;
            padding: 1rem;
        }

        .table thead th:first-child {
            border-radius: 10px 0 0 0;
        }

        .table thead th:last-child {
            border-radius: 0 10px 0 0;
        }

        .table tbody tr {
            transition: all 0.2s ease;
            color: var(--dark-text);
        }

        .table tbody tr:hover {
            background-color: #252d48;
        }

        .status-badge {
            padding: 0.4rem 1rem;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-pending {
            background: linear-gradient(135deg, #FFE5B4 0%, #FFDAB9 100%);
            color: #8B4500;
        }

        .status-confirmed {
            background: linear-gradient(135deg, #90EE90 0%, #98FB98 100%);
            color: #006400;
        }

        .status-cancelled {
            background: linear-gradient(135deg, #FFB6C1 0%, #FFC0CB 100%);
            color: #8B0000;
        }

        .status-completed {
            background: linear-gradient(135deg, #87CEEB 0%, #B0E0E6 100%);
            color: #006994;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #a8b8cc;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
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
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    </li>
                    <?php if ($is_admin): ?>
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
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-header">
            <h1>
                <i class="fas fa-tachometer-alt"></i>
                <?php echo $is_admin ? 'Admin Dashboard' : 'My Dashboard'; ?>
            </h1>
            <p class="mb-0 text-muted">Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
        </div>

        <?php if ($is_admin): ?>
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="stat-card blue">
                        <div class="stat-icon blue">
                            <i class="fas fa-door-open"></i>
                        </div>
                        <div class="stat-value"><?php echo $total_rooms; ?></div>
                        <div class="stat-label">Total Rooms</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card pink">
                        <div class="stat-icon pink">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-value"><?php echo $total_bookings; ?></div>
                        <div class="stat-label">Total Bookings</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card green">
                        <div class="stat-icon green">
                            <i class="fas fa-rupee-sign"></i>
                        </div>
                        <div class="stat-value">₹<?php echo number_format($total_revenue, 2); ?></div>
                        <div class="stat-label">Total Revenue</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card orange">
                        <div class="stat-icon orange">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-value"><?php echo $available_rooms; ?></div>
                        <div class="stat-label">Available Rooms</div>
                    </div>
                </div>
            </div>

            <div class="table-card">
                <h3><i class="fas fa-history"></i> Recent Bookings</h3>
                <?php if (empty($recent_bookings)): ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <p>No bookings yet</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Guest</th>
                                    <th>Room</th>
                                    <th>Check In</th>
                                    <th>Check Out</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_bookings as $booking): ?>
                                    <tr>
                                        <td>#<?php echo $booking['reservation_id']; ?></td>
                                        <td><?php echo htmlspecialchars($booking['username']); ?></td>
                                        <td>Room <?php echo htmlspecialchars($booking['room_number']); ?> (<?php echo ucfirst($booking['room_type']); ?>)</td>
                                        <td><?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?></td>
                                        <td>₹<?php echo number_format($booking['total_amount'], 2); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $booking['status']; ?>">
                                                <?php echo ucfirst($booking['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="stat-card blue">
                        <div class="stat-icon blue">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-value"><?php echo $total_user_bookings; ?></div>
                        <div class="stat-label">Total Bookings</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card orange">
                        <div class="stat-icon orange">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-value"><?php echo $pending_bookings; ?></div>
                        <div class="stat-label">Pending Bookings</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card green">
                        <div class="stat-icon green">
                            <i class="fas fa-rupee-sign"></i>
                        </div>
                        <div class="stat-value">₹<?php echo number_format($total_spent, 2); ?></div>
                        <div class="stat-label">Total Spent</div>
                    </div>
                </div>
            </div>

            <div class="table-card">
                <h3><i class="fas fa-history"></i> My Bookings</h3>
                <?php if (empty($user_bookings)): ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <p>You haven't made any bookings yet</p>
                        <a href="index.php" class="btn btn-custom mt-3">
                            <i class="fas fa-search"></i> Browse Rooms
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Booking ID</th>
                                    <th>Room</th>
                                    <th>Check In</th>
                                    <th>Check Out</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($user_bookings as $booking): ?>
                                    <tr>
                                        <td>#<?php echo $booking['reservation_id']; ?></td>
                                        <td>Room <?php echo htmlspecialchars($booking['room_number']); ?> (<?php echo ucfirst($booking['room_type']); ?>)</td>
                                        <td><?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?></td>
                                        <td>₹<?php echo number_format($booking['total_amount'], 2); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $booking['status']; ?>">
                                                <?php echo ucfirst($booking['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
