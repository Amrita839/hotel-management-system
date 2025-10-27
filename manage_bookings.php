<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

requireLogin();
requireAdmin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $reservation_id = (int)$_POST['reservation_id'];
    $status = sanitize($_POST['status']);

    $stmt = $pdo->prepare("UPDATE reservations SET status = :status WHERE reservation_id = :reservation_id");
    if ($stmt->execute([':status' => $status, ':reservation_id' => $reservation_id])) {
        $success = 'Booking status updated successfully!';
    } else {
        $error = 'Failed to update booking status';
    }
}

$stmt = $pdo->prepare("
    SELECT r.*, rm.room_number, rm.room_type, u.username, u.email
    FROM reservations r
    JOIN rooms rm ON r.room_id = rm.room_id
    JOIN users u ON r.user_id = u.user_id
    ORDER BY r.created_at DESC
");
$stmt->execute();
$bookings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings - Luxury Stays</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-blue: #1e3a5f;
            --primary-pink: #2d4a7c;
            --accent-blue: #4a90e2;
            --accent-pink: #5aa3e0;
            --dark-text: #e8f4f8;
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

        .page-header {
            background: #1a1f3a;
            border-radius: 20px;
            padding: 2rem;
            margin: 2rem 0;
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
            border: 2px solid #2d4a7c;
        }

        .page-header h1 {
            color: var(--dark-text);
            font-weight: 800;
            margin: 0;
        }

        .table-card {
            background: #1a1f3a;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
            margin-bottom: 2rem;
            border: 2px solid #2d4a7c;
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

        .btn-sm {
            padding: 0.4rem 0.8rem;
            border-radius: 10px;
            font-size: 0.875rem;
        }

        .alert {
            border-radius: 15px;
            border: none;
        }

        .modal-content {
            border-radius: 20px;
            border: none;
            background: #1a1f3a;
            border: 2px solid #2d4a7c;
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-pink) 100%);
            color: white;
            border-radius: 20px 20px 0 0;
            border: none;
        }

        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }

        .modal-body {
            background: #1a1f3a;
            color: var(--dark-text);
        }

        .modal-footer {
            background: #1a1f3a;
            border-top: 2px solid #2d4a7c;
        }

        .form-select {
            border-radius: 15px;
            border: 2px solid #2d4a7c;
            background: #0d1621;
            color: var(--dark-text);
            padding: 0.8rem;
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
                        <a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_rooms.php"><i class="fas fa-cogs"></i> Manage Rooms</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="manage_bookings.php"><i class="fas fa-calendar-check"></i> Bookings</a>
                    </li>
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
        <div class="page-header">
            <h1><i class="fas fa-calendar-check"></i> Manage Bookings</h1>
            <p class="mb-0 text-muted">View and manage all reservations</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <div class="table-card">
            <?php if (empty($bookings)): ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <p>No bookings found</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Guest</th>
                                <th>Email</th>
                                <th>Room</th>
                                <th>Check In</th>
                                <th>Check Out</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td><strong>#<?php echo $booking['reservation_id']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($booking['username']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['email']); ?></td>
                                    <td>
                                        Room <?php echo htmlspecialchars($booking['room_number']); ?><br>
                                        <small class="text-muted"><?php echo ucfirst($booking['room_type']); ?></small>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?></td>
                                    <td><strong>₹<?php echo number_format($booking['total_amount'], 2); ?></strong></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $booking['status']; ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-primary btn-sm" onclick="updateStatus(<?php echo $booking['reservation_id']; ?>, '<?php echo $booking['status']; ?>')">
                                            <i class="fas fa-edit"></i> Update
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="modal fade" id="updateStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Update Booking Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="reservation_id" id="update_reservation_id">
                        <div class="mb-3">
                            <label class="form-label">Booking Status</label>
                            <select name="status" id="update_status" class="form-select" required>
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-custom">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateStatus(reservationId, currentStatus) {
            document.getElementById('update_reservation_id').value = reservationId;
            document.getElementById('update_status').value = currentStatus;

            const modal = new bootstrap.Modal(document.getElementById('updateStatusModal'));
            modal.show();
        }
    </script>
</body>
</html>
