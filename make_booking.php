<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

requireLogin();

$error = '';
$success = '';

if (!isset($_GET['room_id'])) {
    redirect('index.php');
}

$room_id = (int)$_GET['room_id'];

$stmt = $pdo->prepare("SELECT * FROM rooms WHERE room_id = :room_id");
$stmt->execute([':room_id' => $room_id]);
$room = $stmt->fetch();

if (!$room) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $check_in = sanitize($_POST['check_in_date']);
    $check_out = sanitize($_POST['check_out_date']);

    if (empty($check_in) || empty($check_out)) {
        $error = 'Please select both check-in and check-out dates';
    } elseif (strtotime($check_in) < strtotime(date('Y-m-d'))) {
        $error = 'Check-in date cannot be in the past';
    } elseif (strtotime($check_out) <= strtotime($check_in)) {
        $error = 'Check-out date must be after check-in date';
    } else {
        $stmt = $pdo->prepare("
            SELECT * FROM reservations
            WHERE room_id = :room_id
            AND status != 'cancelled'
            AND (
                (check_in_date <= :check_in AND check_out_date > :check_in)
                OR (check_in_date < :check_out AND check_out_date >= :check_out)
                OR (check_in_date >= :check_in AND check_out_date <= :check_out)
            )
        ");
        $stmt->execute([
            ':room_id' => $room_id,
            ':check_in' => $check_in,
            ':check_out' => $check_out
        ]);

        if ($stmt->fetch()) {
            $error = 'Room is not available for the selected dates';
        } else {
            $days = (strtotime($check_out) - strtotime($check_in)) / (60 * 60 * 24);
            $total_amount = $days * $room['price_per_night'];

            $stmt = $pdo->prepare("
                INSERT INTO reservations (user_id, room_id, check_in_date, check_out_date, total_amount, status)
                VALUES (:user_id, :room_id, :check_in_date, :check_out_date, :total_amount, 'completed')
            ");

            if ($stmt->execute([
                ':user_id' => $_SESSION['user_id'],
                ':room_id' => $room_id,
                ':check_in_date' => $check_in,
                ':check_out_date' => $check_out,
                ':total_amount' => $total_amount
            ])) {
                $success = 'Booking successful! Redirecting to dashboard...';
                header("refresh:2;url=dashboard.php");
            } else {
                $error = 'Booking failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make Booking - Luxury Stays</title>
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

        .booking-container {
            max-width: 900px;
            margin: 3rem auto;
            padding: 0 1rem;
        }

        .booking-card {
            background: #1a1f3a;
            border-radius: 25px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
            overflow: hidden;
            border: 2px solid #2d4a7c;
        }

        .booking-header {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-pink) 100%);
            color: white;
            padding: 2.5rem;
            text-align: center;
        }

        .booking-header h2 {
            margin: 0;
            font-weight: 800;
            font-size: 2rem;
        }

        .booking-body {
            padding: 2.5rem;
        }

        .room-details {
            background: #0d1621;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 2px solid #2d4a7c;
        }

        .room-details h3 {
            color: var(--dark-text);
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        .detail-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }

        .detail-item i {
            width: 30px;
            color: var(--accent-blue);
            font-size: 1.2rem;
        }

        .price-highlight {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-pink));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .form-control {
            border-radius: 15px;
            border: 2px solid #2d4a7c;
            background: #0d1621;
            color: var(--dark-text);
            padding: 0.9rem 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--accent-blue);
            background: #151e30;
            box-shadow: 0 0 0 0.2rem rgba(74, 144, 226, 0.25);
            color: var(--dark-text);
        }

        .form-label {
            font-weight: 600;
            color: var(--dark-text);
            margin-bottom: 0.5rem;
        }

        .alert {
            border-radius: 15px;
            border: none;
        }

        .calculation-box {
            background: #0d1621;
            border-radius: 20px;
            padding: 1.5rem;
            margin-top: 1rem;
            border: 2px solid #2d4a7c;
        }

        .calculation-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
            color: var(--dark-text);
        }

        .calculation-total {
            border-top: 3px solid var(--accent-pink);
            padding-top: 1rem;
            margin-top: 1rem;
            font-weight: 800;
            font-size: 1.5rem;
            color: var(--dark-text);
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
                        <span class="nav-link"><i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-custom ms-2" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="booking-container">
        <div class="booking-card">
            <div class="booking-header">
                <h2><i class="fas fa-calendar-check"></i> Make a Booking</h2>
                <p class="mb-0">Complete your reservation</p>
            </div>
            <div class="booking-body">
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

                <div class="room-details">
                    <h3><i class="fas fa-door-open"></i> Room Details</h3>
                    <div class="detail-item">
                        <i class="fas fa-hashtag"></i>
                        <strong>Room Number:</strong>&nbsp;<?php echo htmlspecialchars($room['room_number']); ?>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-bed"></i>
                        <strong>Room Type:</strong>&nbsp;<?php echo ucfirst($room['room_type']); ?>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-users"></i>
                        <strong>Max Occupancy:</strong>&nbsp;<?php echo $room['max_occupancy']; ?> guest(s)
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-info-circle"></i>
                        <strong>Description:</strong>&nbsp;<?php echo htmlspecialchars($room['description']); ?>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-rupee-sign"></i>
                        <strong>Price per Night:</strong>&nbsp;
                        <span class="price-highlight">₹<?php echo number_format($room['price_per_night'], 2); ?></span>
                    </div>
                </div>

                <form method="POST" action="" id="bookingForm">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="check_in_date" class="form-label">
                                <i class="fas fa-calendar-alt"></i> Check-In Date
                            </label>
                            <input type="date" class="form-control" id="check_in_date" name="check_in_date"
                                   min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="check_out_date" class="form-label">
                                <i class="fas fa-calendar-alt"></i> Check-Out Date
                            </label>
                            <input type="date" class="form-control" id="check_out_date" name="check_out_date"
                                   min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                        </div>
                    </div>

                    <div class="calculation-box" id="calculationBox" style="display: none;">
                        <div class="calculation-row">
                            <span><i class="fas fa-moon"></i> Number of Nights:</span>
                            <strong id="nights">0</strong>
                        </div>
                        <div class="calculation-row">
                            <span><i class="fas fa-rupee-sign"></i> Price per Night:</span>
                            <strong>₹<?php echo number_format($room['price_per_night'], 2); ?></strong>
                        </div>
                        <div class="calculation-row calculation-total">
                            <span><i class="fas fa-calculator"></i> Total Amount:</span>
                            <span id="totalAmount">₹0.00</span>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-custom w-100 mt-4" style="padding: 1rem; font-size: 1.2rem;">
                        <i class="fas fa-check-circle"></i> Confirm Booking
                    </button>
                </form>

                <div class="text-center mt-3">
                    <a href="index.php" class="text-light" style="text-decoration: none; color: var(--dark-text) !important;">
                        <i class="fas fa-arrow-left"></i> Back to Rooms
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const checkInInput = document.getElementById('check_in_date');
        const checkOutInput = document.getElementById('check_out_date');
        const calculationBox = document.getElementById('calculationBox');
        const nightsSpan = document.getElementById('nights');
        const totalAmountSpan = document.getElementById('totalAmount');
        const pricePerNight = <?php echo $room['price_per_night']; ?>;

        function calculateTotal() {
            const checkIn = new Date(checkInInput.value);
            const checkOut = new Date(checkOutInput.value);

            if (checkInInput.value && checkOutInput.value && checkOut > checkIn) {
                const diffTime = Math.abs(checkOut - checkIn);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                const total = diffDays * pricePerNight;

                nightsSpan.textContent = diffDays;
                totalAmountSpan.textContent = '₹' + total.toFixed(2);
                calculationBox.style.display = 'block';
            } else {
                calculationBox.style.display = 'none';
            }
        }

        checkInInput.addEventListener('change', function() {
            const checkInDate = new Date(this.value);
            const minCheckOut = new Date(checkInDate);
            minCheckOut.setDate(minCheckOut.getDate() + 1);
            checkOutInput.min = minCheckOut.toISOString().split('T')[0];
            calculateTotal();
        });

        checkOutInput.addEventListener('change', calculateTotal);
    </script>
</body>
</html>
