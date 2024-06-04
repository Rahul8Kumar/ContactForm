<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dbname";

// Create connection
$conn = new mysqli($servername, $username,$password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Generate a new token for the form to prevent duplicate submissions
if (empty($_POST)) {
    $token = bin2hex(random_bytes(32));
} else {
    $token = $_POST['token'];
}

// Initialize error message variable
$errorMessage = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate form fields
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    if (empty($name) || empty($phone) || empty($email) || empty($subject) || empty($message)) {
        $errorMessage = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = "Invalid email format.";
    } elseif (!is_numeric($phone)) {
        $errorMessage = "Phone number should be numeric.";
    } else {
        // Check for duplicate submissions using the hidden token
        $stmt = $conn->prepare("SELECT COUNT(*) FROM contact_form WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count > 0) {
            $errorMessage = "This form has already been submitted.";
        } else {
            // Save to database
            $stmt = $conn->prepare("INSERT INTO contact_form (name, phone, email, subject, message, token) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $name, $phone, $email, $subject, $message, $token);

            if ($stmt->execute()) {
                // Send email notification
                $to = "siteowner@example.com";
                $headers = "From: webmaster@example.com" . "\r\n" .
                           "Reply-To: $email" . "\r\n" .
                           "X-Mailer: PHP/" . phpversion();

                $mailBody = "Name: $name\nPhone: $phone\nEmail: $email\nSubject: $subject\n\n$message";
                mail($to, $subject, $mailBody, $headers);

                // Reset the token to prevent resubmission
                $token = bin2hex(random_bytes(32));

                // Redirect or display success message
                echo "Thank you for contacting us!";
                exit;
            } else {
                $errorMessage = "Error: " . $stmt->error;
            }

            $stmt->close();
        }
    }
}

$conn->close();
include 'form.php';
?>
