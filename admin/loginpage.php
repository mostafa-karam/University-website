<?php
include 'connect.php'; // Database connection

// Initialize an error message variable
$errorMessage = "";

if (isset($_POST['signIn'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $password = md5($password);
    if (empty($email) || empty($password)) {
        $errorMessage = "Email and Password are required.";
    } else {
        $sql = "SELECT * FROM users WHERE email='$email' and password='$password'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            session_start();
            $row = $result->fetch_assoc();
            $_SESSION['email'] = $row['email'];
            header("Location: admin.php");
            exit();
        } else {
            $errorMessage = "Invalid email or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - University Website</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script>
        let timer; // Variable to hold the timer

        // Function to show the modal
        function showModal(message) {
            document.getElementById('modal-message').innerText = message;
            document.getElementById('alert-modal').style.display = 'block';

            // Start the progress bar
            startProgressBar(4000); // 3000 milliseconds = 3 seconds
        }

        // Function to start the progress bar
        function startProgressBar(duration) {
            const progressBar = document.querySelector('.progress-bar');
            let width = 0;
            const interval = 10; // Update every 10 milliseconds
            const totalSteps = duration / interval;

            timer = setInterval(() => {
                if (width >= 100) {
                    clearInterval(timer);
                    closeModal();
                } else {
                    width += (100 / totalSteps);
                    progressBar.style.width = width + '%';
                }
            }, interval);
        }

        // Function to close the modal
        function closeModal() {
            clearInterval(timer); // Clear the timer
            document.getElementById('alert-modal').style.display = 'none';
            document.querySelector('.progress-bar').style.width = '0'; // Reset progress bar
        }

        // Show alert if there is an error message
        window.onload = function() {
            <?php if ($errorMessage): ?>
                showModal('<?php echo $errorMessage; ?>');

            <?php endif; ?>
        }
    </script>
</head>

<body>
    <header>
        <div class="container">
            <img src="../img/au-logo.svg" class="logo_pic" alt="Alexandria University Logo">
        </div>
    </header>
    <div id="alert-modal" class="modal">
        <div class="modal-content">
            <span class="close-button" id="close-modal" onclick="closeModal()">&times;</span>
            <p id="modal-message"></p>
            <div class="progress-container">
                <div class="progress-bar"></div>
            </div>
        </div>
    </div>
    <section class="login">
        <div class="login-container">
            <h2>Login to Your Account</h2>
            <form id="login-form" method="post" action="">
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" name="email" id="email" placeholder="Email" required>
                    <small id="email-error" class="error-message"></small>
                </div>
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>

                    <input type="password" name="password" id="password" placeholder="Password" required>
                    <small id="password-error" class="error-message"></small>
                </div>
                <div class="button-container">
                    <button type="submit" class="button" value="Sign In" name="signIn">Login</button>
                    <a href="registepage.php" class="button back-button">signup</a>
                    <a href="../index.html" class="button back-button">Back</a>
                </div>
            </form>

        </div>
    </section>
    <footer>
        <div class="container">
            <p>&copy; 2024 Alex University . All rights reserved.</p>
        </div>
    </footer>
</body>

</html>