<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

requireLogin();
requireAdmin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'add') {
            $room_number = sanitize($_POST['room_number']);
            $room_type = sanitize($_POST['room_type']);
            $price_per_night = (float)$_POST['price_per_night'];
            $max_occupancy = (int)$_POST['max_occupancy'];
            $description = sanitize($_POST['description']);
            $status = sanitize($_POST['status']);

            if (empty($room_number) || empty($room_type) || empty($price_per_night) || empty($max_occupancy)) {
                $error = 'Please fill in all required fields';
            } else {
                $stmt = $pdo->prepare("SELECT * FROM rooms WHERE room_number = :room_number");
                $stmt->execute([':room_number' => $room_number]);

                if ($stmt->fetch()) {
                    $error = 'Room number already exists';
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO rooms (room_number, room_type, price_per_night, max_occupancy, description, status)
                        VALUES (:room_number, :room_type, :price_per_night, :max_occupancy, :description, :status)
                    ");

                    if ($stmt->execute([
                        ':room_number' => $room_number,
                        ':room_type' => $room_type,
                        ':price_per_night' => $price_per_night,
                        ':max_occupancy' => $max_occupancy,
                        ':description' => $description,
                        ':status' => $status
                    ])) {
                        $success = 'Room added successfully!';
                    } else {
                        $error = 'Failed to add room';
                    }
                }
            }
        } elseif ($action === 'edit') {
            $room_id = (int)$_POST['room_id'];
            $room_number = sanitize($_POST['room_number']);
            $room_type = sanitize($_POST['room_type']);
            $price_per_night = (float)$_POST['price_per_night'];
            $max_occupancy = (int)$_POST['max_occupancy'];
            $description = sanitize($_POST['description']);
            $status = sanitize($_POST['status']);

            $stmt = $pdo->prepare("
                UPDATE rooms
                SET room_number = :room_number, room_type = :room_type, price_per_night = :price_per_night,
                    max_occupancy = :max_occupancy, description = :description, status = :status
                WHERE room_id = :room_id
            ");

            if ($stmt->execute([
                ':room_id' => $room_id,
                ':room_number' => $room_number,
                ':room_type' => $room_type,
                ':price_per_night' => $price_per_night,
                ':max_occupancy' => $max_occupancy,
                ':description' => $description,
                ':status' => $status
            ])) {
                $success = 'Room updated successfully!';
            } else {
                $error = 'Failed to update room';
            }
        } elseif ($action === 'delete') {
            $room_id = (int)$_POST['room_id'];

            $stmt = $pdo->prepare("SELECT COUNT(*) as booking_count FROM reservations WHERE room_id = :room_id AND status != 'cancelled'");
            $stmt->execute([':room_id' => $room_id]);
            $result = $stmt->fetch();

            if ($result['booking_count'] > 0) {
                $error = 'Cannot delete room with active bookings';
            } else {
                $stmt = $pdo->prepare("DELETE FROM rooms WHERE room_id = :room_id");
                if ($stmt->execute([':room_id' => $room_id])) {
                    $success = 'Room deleted successfully!';
                } else {
                    $error = 'Failed to delete room';
                }
            }
        }
    }
}

$stmt = $pdo->query("SELECT * FROM rooms ORDER BY room_number");
$rooms = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Rooms - Luxury Stays</title>
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

        .btn-sm {
            padding: 0.4rem 0.8rem;
            border-radius: 10px;
            font-size: 0.875rem;
        }

        .alert {
            border-radius: 15px;
            border: none;
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
                        <a class="nav-link active" href="manage_rooms.php"><i class="fas fa-cogs"></i> Manage Rooms</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_bookings.php"><i class="fas fa-calendar-check"></i> Bookings</a>
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
            <div class="d-flex justify-content-between align-items-center">
                <h1><i class="fas fa-cogs"></i> Manage Rooms</h1>
                <button class="btn btn-custom" data-bs-toggle="modal" data-bs-target="#addRoomModal">
                    <i class="fas fa-plus"></i> Add New Room
                </button>
            </div>
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
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Room #</th>
                            <th>Type</th>
                            <th>Price/Night</th>
                            <th>Occupancy</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rooms as $room): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($room['room_number']); ?></strong></td>
                                <td><?php echo ucfirst($room['room_type']); ?></td>
                                <td>$<?php echo number_format($room['price_per_night'], 2); ?></td>
                                <td><?php echo $room['max_occupancy']; ?> guest(s)</td>
                                <td><?php echo htmlspecialchars(substr($room['description'], 0, 50)); ?>...</td>
                                <td>
                                    <span class="status-badge status-<?php echo $room['status']; ?>">
                                        <?php echo ucfirst($room['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-warning btn-sm" onclick="editRoom(<?php echo htmlspecialchars(json_encode($room)); ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="deleteRoom(<?php echo $room['room_id']; ?>, '<?php echo htmlspecialchars($room['room_number']); ?>')">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addRoomModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus"></i> Add New Room</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Room Number</label>
                            <input type="text" name="room_number" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Room Type</label>
                            <select name="room_type" class="form-select" required>
                                <option value="single">Single</option>
                                <option value="double">Double</option>
                                <option value="suite">Suite</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price per Night</label>
                            <input type="number" name="price_per_night" class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Max Occupancy</label>
                            <input type="number" name="max_occupancy" class="form-control" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="available">Available</option>
                                <option value="occupied">Occupied</option>
                                <option value="maintenance">Maintenance</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-custom">Add Room</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editRoomModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Room</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="room_id" id="edit_room_id">
                        <div class="mb-3">
                            <label class="form-label">Room Number</label>
                            <input type="text" name="room_number" id="edit_room_number" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Room Type</label>
                            <select name="room_type" id="edit_room_type" class="form-select" required>
                                <option value="single">Single</option>
                                <option value="double">Double</option>
                                <option value="suite">Suite</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price per Night</label>
                            <input type="number" name="price_per_night" id="edit_price_per_night" class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Max Occupancy</label>
                            <input type="number" name="max_occupancy" id="edit_max_occupancy" class="form-control" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="edit_status" class="form-select" required>
                                <option value="available">Available</option>
                                <option value="occupied">Occupied</option>
                                <option value="maintenance">Maintenance</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-custom">Update Room</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <form method="POST" action="" id="deleteForm">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="room_id" id="delete_room_id">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editRoom(room) {
            document.getElementById('edit_room_id').value = room.room_id;
            document.getElementById('edit_room_number').value = room.room_number;
            document.getElementById('edit_room_type').value = room.room_type;
            document.getElementById('edit_price_per_night').value = room.price_per_night;
            document.getElementById('edit_max_occupancy').value = room.max_occupancy;
            document.getElementById('edit_description').value = room.description;
            document.getElementById('edit_status').value = room.status;

            const modal = new bootstrap.Modal(document.getElementById('editRoomModal'));
            modal.show();
        }

        function deleteRoom(roomId, roomNumber) {
            if (confirm('Are you sure you want to delete Room ' + roomNumber + '?')) {
                document.getElementById('delete_room_id').value = roomId;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</body>
</html>
