<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: login.html");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["file"])) {
    // $username = $_SESSION['username'];  // Adjusted to use 'username'
    $file_name = basename($_FILES["file"]["name"]);
    $file_tmp = $_FILES["file"]["tmp_name"];
    $upload_dir = "C:/xampp/htdocs/my_portfolio/image_upload/";

    // Ensure the upload directory exists
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $target_file = $upload_dir . $file_name;

    if (move_uploaded_file($file_tmp, $target_file)) {
        // Save the file information to the database
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

        // Prepare statement
        $stmt = $conn->prepare("INSERT INTO uploaded_files (username, file_name, file_path) VALUES (?, ?, ?)");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }

        $relative_file_path = "image_upload/" . $file_name;
        $stmt->bind_param("sss", $username, $file_name, $relative_file_path);

        if ($stmt->execute()) {
            echo "File uploaded and information stored successfully.";
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
        $conn->close();
    } else {
        echo "Error uploading file.";
    }
} else {
    echo "No file uploaded or invalid request.";
}
?>
