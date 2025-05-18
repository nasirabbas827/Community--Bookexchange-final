<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <a class="navbar-brand" href="index.php">Community Book Exchange</a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ml-auto">
            <?php
            if (isset($_SESSION["id"]) && !empty($_SESSION["id"])) {
                echo '<li class="nav-item"><a class="nav-link" href="home.php">Home</a></li>';
                echo '<li class="nav-item"><a class="nav-link" href="update_profile.php">Update Profile</a></li>';
                echo '<li class="nav-item"><a class="nav-link" href="order_history.php">My Orders</a></li>';
                echo '<li class="nav-item"><a class="nav-link" href="view_offers.php">View Offers</a></li>';
                echo '<li class="nav-item"><a class="nav-link" href="contact_support.php">Customer Support</a></li>';
                echo '<li class="nav-item"><a class="nav-link" href="view_messages.php">View Messages</a></li>';
                echo '<li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>';
            } else {
                echo '<li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>';
                echo '<li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>';
                echo '<li class="nav-item"><a class="nav-link" href="./admin/admin_login.php">Admin Login</a></li>';
            }
            ?>
        </ul>
    </div>
</nav>
