<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "myportfolio";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create tables if not exist
$conn->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
)");

$conn->query("CREATE TABLE IF NOT EXISTS uploaded_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50),
    email VARCHAR(100),
    file_name VARCHAR(255),
    file_path VARCHAR(255),
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS active_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    session_id VARCHAR(255),
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)");

// Handle signup, login, logout, and file upload
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['signup'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['Password'];
        $confirm_password = $_POST['Repeat_Password'];

        // Validation
        if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
            echo "All fields are required!";
            exit();
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo "Invalid email format!";
            exit();
        }

        if ($password !== $confirm_password) {
            echo "Passwords do not match!";
            exit();
        }

        // Password validation
        if (strlen($password) < 8) {
            echo "Password should be at least 8 characters long!";
            exit();
        }
        if (!preg_match('/[A-Z]/', $password)) {
            echo "Password should contain at least one uppercase letter!";
            exit();
        }
        if (!preg_match('/[a-z]/', $password)) {
            echo "Password should contain at least one lowercase letter!";
            exit();
        }
        if (!preg_match('/[0-9]/', $password)) {
            echo "Password should contain at least one digit!";
            exit();
        }
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            echo "Password should contain at least one special character!";
            exit();
        }

        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        $sql = "INSERT INTO users (username, email, password) VALUES ('$username', '$email', '$hashed_password')";

        if ($conn->query($sql) === TRUE) {
            echo "Signup successful!";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    } elseif (isset($_POST['login'])) {
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        // Validation
        if (empty($email) || empty($password)) {
            echo "Email and password are required!";
            exit();
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo "Invalid email format!";
            exit();
        }

        // Password validation
        if (strlen($password) < 8) {
            echo "Password should be at least 8 characters long!";
            exit();
        }

        $sql = "SELECT * FROM users WHERE email='$email'";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();

            if (password_verify($password, $row['password'])) {
                $user_id = $row['id'];
                $session_check_sql = "SELECT * FROM active_sessions WHERE user_id='$user_id'";
                $session_check_result = $conn->query($session_check_sql);

                if ($session_check_result->num_rows > 0) {
                    echo "User is already logged in!";
                    exit();
                } else {
                    $session_id = session_id();
                    $_SESSION['user_id'] = $user_id;

                    $create_session_sql = "INSERT INTO active_sessions (user_id, session_id) VALUES ('$user_id', '$session_id')";

                    if ($conn->query($create_session_sql) === TRUE) {
                        echo "Login successful!";
                        header("Location: fetching data.php");
                        exit();
                    } else {
                        echo "Error: " . $create_session_sql . "<br>" . $conn->error;
                    }
                }
            } else {
                echo "Invalid email or password!";
            }
        } else {
            echo "Invalid email or password!";
        }
    } elseif (isset($_POST['logout'])) {
        if (isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
            $session_id = session_id();

            $delete_session_sql = "DELETE FROM active_sessions WHERE user_id='$user_id' AND session_id='$session_id'";

            if ($conn->query($delete_session_sql) === TRUE) {
                session_destroy();
                echo "Logout successful!";
                header("Location: login.html");
            } else {
                echo "Error: " . $delete_session_sql . "<br>" . $conn->error;
            }
        } else {
            echo "No active session found!";
        }
    } elseif (isset($_FILES["photo"])) {
        if ($_FILES["photo"]["error"] == 0) {
            $file_name = $_FILES["photo"]["name"];
            $file_tmp = $_FILES["photo"]["tmp_name"];
            $upload_dir = "C:/xampp/htdocs/my_portfolio/image_upload/";
            $target_path = $upload_dir . basename($file_name);

            if (move_uploaded_file($file_tmp, $target_path)) {
                $username = $_POST['username'];
                $email = $_POST['email'];

                $sql = "INSERT INTO uploaded_files (username, email, file_name, file_path) VALUES ('$username', '$email', '$file_name', '$target_path')";

                if ($conn->query($sql) === TRUE) {
                    echo "File uploaded and information stored successfully.";
                } else {
                    echo "Error: " . $sql . "<br>" . $conn->error;
                }
            } else {
                echo "Error uploading file.";
            }
        } else {
            echo "Error: " . $_FILES["photo"]["error"];
        }
    }
}

$conn->close();
?>
