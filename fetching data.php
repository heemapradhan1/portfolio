<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: login.html");
    exit();
}

$servername = "localhost";
$username_db = "root";
$password_db = "";
$dbname = "myportfolio";

// Create connection
$conn = new mysqli($servername, $username_db, $password_db, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];

// Fetch user information
$sql = "SELECT id, username, email FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Fetch uploaded files
$sql_files = "SELECT file_name, file_path FROM uploaded_files WHERE username = ?";
$stmt_files = $conn->prepare($sql_files);
$stmt_files->bind_param("s", $user['username']);
$stmt_files->execute();
$result_files = $stmt_files->get_result();
$uploaded_files = $result_files->fetch_all(MYSQLI_ASSOC);
$stmt_files->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <style>
        :root {
            --primary-color: #1a73e8;
            --secondary-color: #424242;
            --background-color: #f5f5f5;
            --card-background-color: #ffffff;
            --text-color: #333333;
            --link-color: #1a73e8;
            --danger-color: #d32f2f;
            --hover-color: rgba(0, 0, 0, 0.05);
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--background-color);
            margin: 0;
            padding: 0;
            color: var(--text-color);
            display: flex;
            flex-direction: column;
            height: 100vh;
        }
        .loading-spinner {
            display: none;
            border: 8px solid #f3f3f3;
            border-radius: 50%;
            border-top: 8px solid var(--primary-color);
            width: 60px;
            height: 60px;
            animation: spin 2s linear infinite;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .header {
            background-color: var(--card-background-color);
            color: var(--primary-color);
            text-align: center;
            position: relative;
            flex-shrink: 0;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        .header h1 {
            margin: 0;
        }
        .logout-btn {
            position: absolute;
            right: 20px;
            top: 20px;
            background-color: var(--danger-color);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s ease;
        }
        .logout-btn:hover {
            background-color: #b71c1c;
        }
        .container {
            display: flex;
            flex: 1;
            height: 100%;
        }
        .sidebar {
            width: 250px;
            background-color: var(--secondary-color);
            color: white;
            padding: 20px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .sidebar h2 {
            margin: 0 0 20px;
        }
        .sidebar a {
            color: white;
            text-decoration: none;
            padding: 10px;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .sidebar a:hover {
            background-color: var(--hover-color);
        }
        .dashboard {
            flex: 1;
            padding: 20px;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }
        .dashboard h2 {
            margin-top: 0;
            color: var(--primary-color);
        }
        .cards {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        .card {
            flex: 1;
            min-width: 300px;
            max-width: 500px;
            background-color: var(--card-background-color);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        .card h3 {
            margin-top: 0;
            color: var(--primary-color);
        }
        .card p {
            line-height: 1.6;
        }
        .upload-form {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .upload-form input[type="file"] {
            width: 100%;
            padding: 10px;
            margin-top: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .upload-form input[type="submit"] {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 5px;
            margin-top: 10px;
            transition: background-color 0.3s;
        }
        .upload-form input[type="submit"]:hover {
            background-color: #1558b0;
        }
        .tooltip {
            position: relative;
            display: inline-block;
        }
        .tooltip .tooltiptext {
            visibility: hidden;
            width: 120px;
            background-color: var(--secondary-color);
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 5px;
            position: absolute;
            z-index: 1;
            bottom: 125%; 
            left: 50%;
            margin-left: -60px;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }
    </style>
</head>
<body>
<div class="loading-spinner" id="loadingSpinner"></div>

<div class="header">
    <h1>Welcome, <?php echo $user['username']; ?>!</h1>
    <a href="logout.php" class="logout-btn">Logout</a>
</div>

<div class="container">
    <div class="sidebar">
        <h2>Menu</h2>
        <a href="#" class="tooltip">Dashboard<span class="tooltiptext">Go to Dashboard</span></a>
        <a href="#" class="tooltip">Settings<span class="tooltiptext">Adjust Settings</span></a>
        <a href="#" class="tooltip">Profile<span class="tooltiptext">View Profile</span></a>
    </div>

    <div class="dashboard">
        <h2>Welcome to your dashboard</h2>
        <div class="cards">
            <?php
            if ($user) {
                echo "<div class='card'>";
                echo "<h3>User Information</h3>";
                echo "<p><strong>ID:</strong> " . $user["id"] . "</p>";
                echo "<p><strong>Username:</strong> " . $user["username"] . "</p>";
                echo "<p><strong>Email:</strong> " . $user["email"] . "</p>";
                echo "</div>";
            } else {
                echo "<p>No user found</p>";
            }
            ?>

            <div class="card">
                <h3>Upload Photo</h3>
                <form action="upload.php" method="post" enctype="multipart/form-data" class="upload-form">
                    <label for="file">Choose file to upload:</label>
                    <input type="file" name="file" id="file" onchange="previewFile()"><br>
                    <img id="preview" src="#" alt="Image preview" style="display: none; max-width: 100%; height: auto; margin-top: 10px;">
                    <input type="submit" value="Upload File" name="submit">
                </form>
            </div>
            
            <?php
            if (!empty($uploaded_files)) {
                foreach ($uploaded_files as $file) {
                    echo "<div class='card'>";
                    echo "<h3>Uploaded Image</h3>";
                    echo "<img src='" . $file['file_path'] . "' alt='Uploaded Image' style='max-width: 100%; height: auto;'>";
                    echo "<p>File Name: " . $file['file_name'] . "</p>";
                    echo "</div>";
                }
            }
            ?>
        </div>
    </div>
</div>

<script>
    document.onreadystatechange = function () {
        var state = document.readyState;
        if (state === 'interactive') {
            document.getElementById('loadingSpinner').style.display = 'block';
        } else if (state === 'complete') {
            document.getElementById('loadingSpinner').style.display = 'none';
        }
    }

    function previewFile() {
        const preview = document.getElementById('preview');
        const file = document.getElementById('file').files[0];
        const reader = new FileReader();

        reader.addEventListener('load', function () {
            preview.src = reader.result;
            preview.style.display = 'block';
        }, false);

        if (file) {
            reader.readAsDataURL(file);
        }
    }
</script>
</body>
</html>
