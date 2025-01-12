<?php

include 'connect.php';
// Initialize an error message variable
$errorMessage = "";

if (isset($_POST['signUp'])) {
    $firstName = $_POST['fName'];
    $lastName = $_POST['lName'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $password = md5($password);

    $checkEmail = "SELECT * From users where email='$email'";
    $result = $conn->query($checkEmail);
    if ($result->num_rows > 0) {
        $errorMessage = "Email Address Already Exists !";
    } else {
        $insertQuery = "INSERT INTO users(firstName,lastName,email,password)
                    VALUES ('$firstName','$lastName','$email','$password')";
        if ($conn->query($insertQuery) == TRUE) {
            header("location: admin.php");
        } else {
            echo "Error:" . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - University Website</title>
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
            <h2>Create Your Account</h2>
            <form action="" method="POST">
                <div class="form-group">
                    <label for="fname"><i class="fas fa-user"></i> First Name</label>
                    <input type="text" name="fName" id="fName" placeholder="First Name" required>
                </div>
                <div class="form-group">
                    <label for="lName"><i class="fas fa-user"></i> Last Name</label>
                    <input type="text" name="lName" id="lName" placeholder="Last Name" required>
                </div>
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" name="email" id="email" placeholder="Email" required>
                </div>
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" name="password" id="password" placeholder="Password" required>

                </div>
                <div class="button-container">
                    <button type="submit" class="button" value="Sign Up" name="signUp">Register</button>
                    <a href="loginpage.php" class="button back-button">Back</a>
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