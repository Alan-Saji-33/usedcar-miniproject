<?php
// Database configuration
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "car_rental_db";

// Create connection
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Create tables if they don't exist
$sql_users = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    user_type ENUM('admin', 'seller', 'buyer') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

$sql_cars = "CREATE TABLE IF NOT EXISTS cars (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seller_id INT NOT NULL,
    model VARCHAR(100) NOT NULL,
    brand VARCHAR(50) NOT NULL,
    year INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    km_driven INT NOT NULL,
    fuel_type ENUM('Petrol', 'Diesel', 'Electric', 'Hybrid', 'CNG') NOT NULL,
    transmission ENUM('Automatic', 'Manual') NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    description TEXT,
    is_sold BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(id)
)";

$sql_favorites = "CREATE TABLE IF NOT EXISTS favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    car_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (car_id) REFERENCES cars(id),
    UNIQUE KEY unique_favorite (user_id, car_id)
)";

mysqli_query($conn, $sql_users);
mysqli_query($conn, $sql_cars);
mysqli_query($conn, $sql_favorites);

// Initialize session
session_start();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['register'])) {
        // Registration logic
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $user_type = mysqli_real_escape_string($conn, $_POST['user_type']);
        
        $sql = "INSERT INTO users (username, password, email, user_type) VALUES ('$username', '$password', '$email', '$user_type')";
        
        if (mysqli_query($conn, $sql)) {
            $_SESSION['message'] = "Registration successful! Please login.";
            header("Location: ".$_SERVER['PHP_SELF']);
            exit();
        } else {
            $_SESSION['error'] = "Error: " . mysqli_error($conn);
        }
    } elseif (isset($_POST['login'])) {
        // Login logic
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $password = $_POST['password'];
        
        $sql = "SELECT * FROM users WHERE username='$username'";
        $result = mysqli_query($conn, $sql);
        
        if (mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_type'] = $user['user_type'];
                header("Location: ".$_SERVER['PHP_SELF']);
                exit();
            } else {
                $_SESSION['error'] = "Invalid password!";
            }
        } else {
            $_SESSION['error'] = "User not found!";
        }
    } elseif (isset($_POST['add_car'])) {
        // Add car logic
        if ($_SESSION['user_type'] == 'seller' || $_SESSION['user_type'] == 'admin') {
            $model = mysqli_real_escape_string($conn, $_POST['model']);
            $brand = mysqli_real_escape_string($conn, $_POST['brand']);
            $year = mysqli_real_escape_string($conn, $_POST['year']);
            $price = mysqli_real_escape_string($conn, $_POST['price']);
            $km_driven = mysqli_real_escape_string($conn, $_POST['km_driven']);
            $fuel_type = mysqli_real_escape_string($conn, $_POST['fuel_type']);
            $transmission = mysqli_real_escape_string($conn, $_POST['transmission']);
            $description = mysqli_real_escape_string($conn, $_POST['description']);
            $seller_id = $_SESSION['user_id'];
            
            // Handle image upload
            $target_dir = "uploads/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $target_file = $target_dir . basename($_FILES["image"]["name"]);
            $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
            $new_filename = uniqid() . '.' . $imageFileType;
            $target_file = $target_dir . $new_filename;
            
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $sql = "INSERT INTO cars (seller_id, model, brand, year, price, km_driven, fuel_type, transmission, image_path, description) 
                        VALUES ('$seller_id', '$model', '$brand', '$year', '$price', '$km_driven', '$fuel_type', '$transmission', '$target_file', '$description')";
                
                if (mysqli_query($conn, $sql)) {
                    $_SESSION['message'] = "Car added successfully!";
                } else {
                    $_SESSION['error'] = "Error: " . mysqli_error($conn);
                }
            } else {
                $_SESSION['error'] = "Sorry, there was an error uploading your file.";
            }
        }
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    } elseif (isset($_POST['update_car'])) {
        // Update car logic
        if ($_SESSION['user_type'] == 'seller' || $_SESSION['user_type'] == 'admin') {
            $car_id = mysqli_real_escape_string($conn, $_POST['car_id']);
            $model = mysqli_real_escape_string($conn, $_POST['model']);
            $brand = mysqli_real_escape_string($conn, $_POST['brand']);
            $year = mysqli_real_escape_string($conn, $_POST['year']);
            $price = mysqli_real_escape_string($conn, $_POST['price']);
            $km_driven = mysqli_real_escape_string($conn, $_POST['km_driven']);
            $fuel_type = mysqli_real_escape_string($conn, $_POST['fuel_type']);
            $transmission = mysqli_real_escape_string($conn, $_POST['transmission']);
            $description = mysqli_real_escape_string($conn, $_POST['description']);
            
            $sql = "UPDATE cars SET 
                    model='$model', 
                    brand='$brand', 
                    year='$year', 
                    price='$price', 
                    km_driven='$km_driven', 
                    fuel_type='$fuel_type', 
                    transmission='$transmission', 
                    description='$description' 
                    WHERE id='$car_id'";
            
            if (mysqli_query($conn, $sql)) {
                $_SESSION['message'] = "Car updated successfully!";
            } else {
                $_SESSION['error'] = "Error: " . mysqli_error($conn);
            }
        }
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    } elseif (isset($_POST['delete_car'])) {
        // Delete car logic
        if ($_SESSION['user_type'] == 'seller' || $_SESSION['user_type'] == 'admin') {
            $car_id = mysqli_real_escape_string($conn, $_POST['car_id']);
            
            $sql = "DELETE FROM cars WHERE id='$car_id'";
            
            if (mysqli_query($conn, $sql)) {
                $_SESSION['message'] = "Car deleted successfully!";
            } else {
                $_SESSION['error'] = "Error: " . mysqli_error($conn);
            }
        }
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    } elseif (isset($_POST['mark_sold'])) {
        // Mark as sold logic
        if ($_SESSION['user_type'] == 'seller' || $_SESSION['user_type'] == 'admin') {
            $car_id = mysqli_real_escape_string($conn, $_POST['car_id']);
            
            $sql = "UPDATE cars SET is_sold=TRUE WHERE id='$car_id'";
            
            if (mysqli_query($conn, $sql)) {
                $_SESSION['message'] = "Car marked as sold!";
            } else {
                $_SESSION['error'] = "Error: " . mysqli_error($conn);
            }
        }
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    } elseif (isset($_POST['toggle_favorite'])) {
        // Toggle favorite logic
        if (isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
            $car_id = mysqli_real_escape_string($conn, $_POST['car_id']);
            
            // Check if already favorited
            $check_sql = "SELECT * FROM favorites WHERE user_id='$user_id' AND car_id='$car_id'";
            $check_result = mysqli_query($conn, $check_sql);
            
            if (mysqli_num_rows($check_result) > 0) {
                // Remove favorite
                $sql = "DELETE FROM favorites WHERE user_id='$user_id' AND car_id='$car_id'";
                $action = "removed from";
            } else {
                // Add favorite
                $sql = "INSERT INTO favorites (user_id, car_id) VALUES ('$user_id', '$car_id')";
                $action = "added to";
            }
            
            if (mysqli_query($conn, $sql)) {
                $_SESSION['message'] = "Car $action your favorites!";
            } else {
                $_SESSION['error'] = "Error: " . mysqli_error($conn);
            }
        }
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    }
}

// Get cars for listing
$search_where = "";
if (isset($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $min_price = isset($_GET['min_price']) ? (int)$_GET['min_price'] : 0;
    $max_price = isset($_GET['max_price']) ? (int)$_GET['max_price'] : 1000000;
    $fuel_type = isset($_GET['fuel_type']) ? mysqli_real_escape_string($conn, $_GET['fuel_type']) : '';
    $transmission = isset($_GET['transmission']) ? mysqli_real_escape_string($conn, $_GET['transmission']) : '';
    
    $search_where = "WHERE (model LIKE '%$search%' OR brand LIKE '%$search%' OR description LIKE '%$search%') 
                     AND price BETWEEN $min_price AND $max_price 
                     AND is_sold = FALSE";
    
    if (!empty($fuel_type)) {
        $search_where .= " AND fuel_type = '$fuel_type'";
    }
    
    if (!empty($transmission)) {
        $search_where .= " AND transmission = '$transmission'";
    }
}

$sql_cars_list = "SELECT cars.*, users.username as seller_name FROM cars 
                  JOIN users ON cars.seller_id = users.id 
                  $search_where 
                  ORDER BY created_at DESC 
                  LIMIT 12";
$cars_result = mysqli_query($conn, $sql_cars_list);

// Get favorite cars for logged in user
$favorites = array();
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $sql_favorites = "SELECT car_id FROM favorites WHERE user_id='$user_id'";
    $favorites_result = mysqli_query($conn, $sql_favorites);
    
    while ($row = mysqli_fetch_assoc($favorites_result)) {
        $favorites[] = $row['car_id'];
    }
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Used Car Rental System</title>
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2ecc71;
            --dark-color: #2c3e50;
            --light-color: #ecf0f1;
            --danger-color: #e74c3c;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            background-color: var(--dark-color);
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: white;
            text-decoration: none;
        }
        
        .logo span {
            color: var(--secondary-color);
        }
        
        nav ul {
            display: flex;
            list-style: none;
        }
        
        nav ul li {
            margin-left: 20px;
        }
        
        nav ul li a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        nav ul li a:hover {
            color: var(--secondary-color);
        }
        
        .auth-buttons .btn {
            margin-left: 10px;
        }
        
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #2980b9;
        }
        
        .btn-secondary {
            background-color: var(--secondary-color);
        }
        
        .btn-secondary:hover {
            background-color: #27ae60;
        }
        
        .btn-danger {
            background-color: var(--danger-color);
        }
        
        .btn-danger:hover {
            background-color: #c0392b;
        }
        
        .hero {
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('https://images.unsplash.com/photo-1489824904134-891ab64532f1?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 80px 0;
            text-align: center;
            margin-bottom: 40px;
        }
        
        .hero h1 {
            font-size: 48px;
            margin-bottom: 20px;
        }
        
        .hero p {
            font-size: 20px;
            max-width: 700px;
            margin: 0 auto 30px;
        }
        
        .search-box {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 40px;
        }
        
        .search-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .cars-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .car-card {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .car-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .car-image {
            height: 180px;
            overflow: hidden;
        }
        
        .car-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }
        
        .car-card:hover .car-image img {
            transform: scale(1.05);
        }
        
        .car-details {
            padding: 20px;
        }
        
        .car-title {
            font-size: 20px;
            margin-bottom: 10px;
            color: var(--dark-color);
        }
        
        .car-price {
            font-size: 22px;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .car-specs {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .car-spec {
            background-color: var(--light-color);
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .car-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .favorite-btn {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #ccc;
            transition: color 0.3s;
        }
        
        .favorite-btn.active {
            color: var(--danger-color);
        }
        
        .favorite-btn:hover {
            color: var(--danger-color);
        }
        
        .sold-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: var(--danger-color);
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
            z-index: 1;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-title {
            font-size: 24px;
            color: var(--dark-color);
        }
        
        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #777;
        }
        
        .close-btn:hover {
            color: var(--danger-color);
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        footer {
            background-color: var(--dark-color);
            color: white;
            padding: 30px 0;
            text-align: center;
            margin-top: 40px;
        }
        
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                text-align: center;
            }
            
            nav ul {
                margin-top: 20px;
                justify-content: center;
            }
            
            .auth-buttons {
                margin-top: 20px;
            }
            
            .hero h1 {
                font-size: 36px;
            }
            
            .hero p {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container header-content">
            <a href="#" class="logo">Car<span>Rent</span></a>
            <nav>
                <ul>
                    <li><a href="#">Home</a></li>
                    <li><a href="#cars">Cars</a></li>
                    <li><a href="#about">About</a></li>
                    <li><a href="#contact">Contact</a></li>
                </ul>
            </nav>
            <div class="auth-buttons">
                <?php if (isset($_SESSION['username'])): ?>
                    <span>Welcome, <?php echo $_SESSION['username']; ?> (<?php echo $_SESSION['user_type']; ?>)</span>
                    <a href="?logout" class="btn btn-danger">Logout</a>
                <?php else: ?>
                    <a href="#login-modal" class="btn" onclick="openModal('login-modal')">Login</a>
                    <a href="#register-modal" class="btn btn-secondary" onclick="openModal('register-modal')">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <section class="hero">
        <div class="container">
            <h1>Find Your Perfect Used Car</h1>
            <p>Browse through thousands of quality used cars from trusted sellers across the country.</p>
            <a href="#cars" class="btn">Browse Cars</a>
        </div>
    </section>

    <div class="container">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="search-box">
            <h2>Search Cars</h2>
            <form method="GET" class="search-form">
                <div class="form-group">
                    <label for="search">Keywords</label>
                    <input type="text" id="search" name="search" class="form-control" placeholder="Model, brand..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="min_price">Min Price ($)</label>
                    <input type="number" id="min_price" name="min_price" class="form-control" min="0" value="<?php echo isset($_GET['min_price']) ? htmlspecialchars($_GET['min_price']) : '0'; ?>">
                </div>
                <div class="form-group">
                    <label for="max_price">Max Price ($)</label>
                    <input type="number" id="max_price" name="max_price" class="form-control" min="0" value="<?php echo isset($_GET['max_price']) ? htmlspecialchars($_GET['max_price']) : '100000'; ?>">
                </div>
                <div class="form-group">
                    <label for="fuel_type">Fuel Type</label>
                    <select id="fuel_type" name="fuel_type" class="form-control">
                        <option value="">Any</option>
                        <option value="Petrol" <?php echo (isset($_GET['fuel_type']) && $_GET['fuel_type'] == 'Petrol') ? 'selected' : ''; ?>>Petrol</option>
                        <option value="Diesel" <?php echo (isset($_GET['fuel_type']) && $_GET['fuel_type'] == 'Diesel') ? 'selected' : ''; ?>>Diesel</option>
                        <option value="Electric" <?php echo (isset($_GET['fuel_type']) && $_GET['fuel_type'] == 'Electric') ? 'selected' : ''; ?>>Electric</option>
                        <option value="Hybrid" <?php echo (isset($_GET['fuel_type']) && $_GET['fuel_type'] == 'Hybrid') ? 'selected' : ''; ?>>Hybrid</option>
                        <option value="CNG" <?php echo (isset($_GET['fuel_type']) && $_GET['fuel_type'] == 'CNG') ? 'selected' : ''; ?>>CNG</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="transmission">Transmission</label>
                    <select id="transmission" name="transmission" class="form-control">
                        <option value="">Any</option>
                        <option value="Automatic" <?php echo (isset($_GET['transmission']) && $_GET['transmission'] == 'Automatic') ? 'selected' : ''; ?>>Automatic</option>
                        <option value="Manual" <?php echo (isset($_GET['transmission']) && $_GET['transmission'] == 'Manual') ? 'selected' : ''; ?>>Manual</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-secondary">Search</button>
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn">Reset</a>
                </div>
            </form>
        </div>

        <section id="cars">
            <div class="section-header">
                <h2>Available Cars</h2>
                <?php if (isset($_SESSION['user_type']) && ($_SESSION['user_type'] == 'seller' || $_SESSION['user_type'] == 'admin')): ?>
                    <button class="btn" onclick="openModal('add-car-modal')">Add New Car</button>
                <?php endif; ?>
            </div>
            
            <div class="cars-grid">
                <?php if (mysqli_num_rows($cars_result) > 0): ?>
                    <?php while ($car = mysqli_fetch_assoc($cars_result)): ?>
                        <div class="car-card">
                            <?php if ($car['is_sold']): ?>
                                <div class="sold-badge">SOLD</div>
                            <?php endif; ?>
                            <div class="car-image">
                                <img src="<?php echo $car['image_path']; ?>" alt="<?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?>">
                            </div>
                            <div class="car-details">
                                <h3 class="car-title"><?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?></h3>
                                <div class="car-price">$<?php echo number_format($car['price']); ?></div>
                                <div class="car-specs">
                                    <span class="car-spec"><?php echo $car['year']; ?></span>
                                    <span class="car-spec"><?php echo number_format($car['km_driven']); ?> km</span>
                                    <span class="car-spec"><?php echo $car['fuel_type']; ?></span>
                                    <span class="car-spec"><?php echo $car['transmission']; ?></span>
                                </div>
                                <p><?php echo htmlspecialchars(substr($car['description'], 0, 100)); ?>...</p>
                                <div class="car-actions">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                                        <button type="button" class="favorite-btn <?php echo in_array($car['id'], $favorites) ? 'active' : ''; ?>" 
                                                onclick="toggleFavorite(this, <?php echo $car['id']; ?>)">
                                            ♥
                                        </button>
                                    </form>
                                    <a href="#" class="btn" onclick="openModal('car-details-modal-<?php echo $car['id']; ?>')">Details</a>
                                </div>
                            </div>
                        </div>

                        <!-- Car Details Modal -->
                        <div id="car-details-modal-<?php echo $car['id']; ?>" class="modal">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h3 class="modal-title"><?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?></h3>
                                    <button class="close-btn" onclick="closeModal('car-details-modal-<?php echo $car['id']; ?>')">×</button>
                                </div>
                                <div style="display: flex; gap: 20px; margin-bottom: 20px;">
                                    <div style="flex: 1;">
                                        <img src="<?php echo $car['image_path']; ?>" alt="<?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?>" style="width: 100%; border-radius: 8px;">
                                    </div>
                                    <div style="flex: 1;">
                                        <p><strong>Price:</strong> $<?php echo number_format($car['price']); ?></p>
                                        <p><strong>Year:</strong> <?php echo $car['year']; ?></p>
                                        <p><strong>Kilometers:</strong> <?php echo number_format($car['km_driven']); ?> km</p>
                                        <p><strong>Fuel Type:</strong> <?php echo $car['fuel_type']; ?></p>
                                        <p><strong>Transmission:</strong> <?php echo $car['transmission']; ?></p>
                                        <p><strong>Seller:</strong> <?php echo htmlspecialchars($car['seller_name']); ?></p>
                                    </div>
                                </div>
                                <div>
                                    <h4>Description</h4>
                                    <p><?php echo nl2br(htmlspecialchars($car['description'])); ?></p>
                                </div>
                                <?php if (isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $car['seller_id'] || $_SESSION['user_type'] == 'admin')): ?>
                                    <div style="margin-top: 20px; display: flex; gap: 10px;">
                                        <button class="btn" onclick="openEditModal(<?php echo $car['id']; ?>)">Edit</button>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                                            <button type="submit" name="delete_car" class="btn btn-danger">Delete</button>
                                        </form>
                                        <?php if (!$car['is_sold']): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                                                <button type="submit" name="mark_sold" class="btn btn-secondary">Mark as Sold</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Edit Car Modal -->
                        <div id="edit-car-modal-<?php echo $car['id']; ?>" class="modal">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h3 class="modal-title">Edit Car</h3>
                                    <button class="close-btn" onclick="closeModal('edit-car-modal-<?php echo $car['id']; ?>')">×</button>
                                </div>
                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                                    <div class="form-group">
                                        <label for="edit-brand-<?php echo $car['id']; ?>">Brand</label>
                                        <input type="text" id="edit-brand-<?php echo $car['id']; ?>" name="brand" class="form-control" value="<?php echo htmlspecialchars($car['brand']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="edit-model-<?php echo $car['id']; ?>">Model</label>
                                        <input type="text" id="edit-model-<?php echo $car['id']; ?>" name="model" class="form-control" value="<?php echo htmlspecialchars($car['model']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="edit-year-<?php echo $car['id']; ?>">Year</label>
                                        <input type="number" id="edit-year-<?php echo $car['id']; ?>" name="year" class="form-control" min="1900" max="<?php echo date('Y'); ?>" value="<?php echo $car['year']; ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="edit-price-<?php echo $car['id']; ?>">Price ($)</label>
                                        <input type="number" id="edit-price-<?php echo $car['id']; ?>" name="price" class="form-control" min="0" step="0.01" value="<?php echo $car['price']; ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="edit-km_driven-<?php echo $car['id']; ?>">Kilometers Driven</label>
                                        <input type="number" id="edit-km_driven-<?php echo $car['id']; ?>" name="km_driven" class="form-control" min="0" value="<?php echo $car['km_driven']; ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="edit-fuel_type-<?php echo $car['id']; ?>">Fuel Type</label>
                                        <select id="edit-fuel_type-<?php echo $car['id']; ?>" name="fuel_type" class="form-control" required>
                                            <option value="Petrol" <?php echo $car['fuel_type'] == 'Petrol' ? 'selected' : ''; ?>>Petrol</option>
                                            <option value="Diesel" <?php echo $car['fuel_type'] == 'Diesel' ? 'selected' : ''; ?>>Diesel</option>
                                            <option value="Electric" <?php echo $car['fuel_type'] == 'Electric' ? 'selected' : ''; ?>>Electric</option>
                                            <option value="Hybrid" <?php echo $car['fuel_type'] == 'Hybrid' ? 'selected' : ''; ?>>Hybrid</option>
                                            <option value="CNG" <?php echo $car['fuel_type'] == 'CNG' ? 'selected' : ''; ?>>CNG</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="edit-transmission-<?php echo $car['id']; ?>">Transmission</label>
                                        <select id="edit-transmission-<?php echo $car['id']; ?>" name="transmission" class="form-control" required>
                                            <option value="Automatic" <?php echo $car['transmission'] == 'Automatic' ? 'selected' : ''; ?>>Automatic</option>
                                            <option value="Manual" <?php echo $car['transmission'] == 'Manual' ? 'selected' : ''; ?>>Manual</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="edit-description-<?php echo $car['id']; ?>">Description</label>
                                        <textarea id="edit-description-<?php echo $car['id']; ?>" name="description" class="form-control" rows="4" required><?php echo htmlspecialchars($car['description']); ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <button type="submit" name="update_car" class="btn btn-secondary">Update Car</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No cars found matching your criteria.</p>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <!-- Login Modal -->
    <div id="login-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Login</h3>
                <button class="close-btn" onclick="closeModal('login-modal')">×</button>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label for="login-username">Username</label>
                    <input type="text" id="login-username" name="username" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="login-password">Password</label>
                    <input type="password" id="login-password" name="password" class="form-control" required>
                </div>
                <div class="form-group">
                    <button type="submit" name="login" class="btn">Login</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Register Modal -->
    <div id="register-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Register</h3>
                <button class="close-btn" onclick="closeModal('register-modal')">×</button>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label for="register-username">Username</label>
                    <input type="text" id="register-username" name="username" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="register-email">Email</label>
                    <input type="email" id="register-email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="register-password">Password</label>
                    <input type="password" id="register-password" name="password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="register-user_type">User Type</label>
                    <select id="register-user_type" name="user_type" class="form-control" required>
                        <option value="buyer">Buyer</option>
                        <option value="seller">Seller</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" name="register" class="btn btn-secondary">Register</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Car Modal -->
    <div id="add-car-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Add New Car</h3>
                <button class="close-btn" onclick="closeModal('add-car-modal')">×</button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="add-brand">Brand</label>
                    <input type="text" id="add-brand" name="brand" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="add-model">Model</label>
                    <input type="text" id="add-model" name="model" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="add-year">Year</label>
                    <input type="number" id="add-year" name="year" class="form-control" min="1900" max="<?php echo date('Y'); ?>" required>
                </div>
                <div class="form-group">
                    <label for="add-price">Price ($)</label>
                    <input type="number" id="add-price" name="price" class="form-control" min="0" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="add-km_driven">Kilometers Driven</label>
                    <input type="number" id="add-km_driven" name="km_driven" class="form-control" min="0" required>
                </div>
                <div class="form-group">
                    <label for="add-fuel_type">Fuel Type</label>
                    <select id="add-fuel_type" name="fuel_type" class="form-control" required>
                        <option value="Petrol">Petrol</option>
                        <option value="Diesel">Diesel</option>
                        <option value="Electric">Electric</option>
                        <option value="Hybrid">Hybrid</option>
                        <option value="CNG">CNG</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="add-transmission">Transmission</label>
                    <select id="add-transmission" name="transmission" class="form-control" required>
                        <option value="Automatic">Automatic</option>
                        <option value="Manual">Manual</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="add-image">Car Image</label>
                    <input type="file" id="add-image" name="image" class="form-control" accept="image/*" required>
                </div>
                <div class="form-group">
                    <label for="add-description">Description</label>
                    <textarea id="add-description" name="description" class="form-control" rows="4" required></textarea>
                </div>
                <div class="form-group">
                    <button type="submit" name="add_car" class="btn btn-secondary">Add Car</button>
                </div>
            </form>
        </div>
    </div>

    <footer>
        <div class="container">
            <p>&copy; 2023 CarRent. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        function openEditModal(carId) {
            closeModal('car-details-modal-' + carId);
            openModal('edit-car-modal-' + carId);
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        }
        
        // Toggle favorite
        function toggleFavorite(button, carId) {
            if (!<?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>) {
                openModal('login-modal');
                return;
            }
            
            button.classList.toggle('active');
            
            // Submit form
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'car_id';
            input.value = carId;
            
            const submit = document.createElement('input');
            submit.type = 'hidden';
            submit.name = 'toggle_favorite';
            
            form.appendChild(input);
            form.appendChild(submit);
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html>
