<!-- navbar -->
<nav class="navbar-container sticky-top">
    <div class="navbar-logo">
        <h3><a href="./">Toko Komputer</a></h3>
    </div>
    <div class="navbar-box">
        <ul class="navbar-list">
            <li><a href="./"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="./shop"><i class="fas fa-shopping-cart"></i> Shop</a></li>
            <?php if (!isset($_SESSION['login'])) { ?>
                <li><a href="./auth/login"><i class="fas fa-lock"></i> Signin</a></li>
            <?php } else { ?>
                <li><a href="./my-cart"><i class="fas fa-shopping-cart"></i> My Cart</a></li>
                <li><a href="./auth/logout"><i class="fas fa-lock"></i> Logout</a></li>
            <?php } ?>
        </ul>
    </div>
    <div class="navbar-toggle">
        <span></span>
    </div>
</nav>
<!-- akhir navbar -->

<script>
// Script untuk navbar toggle (mobile)
document.querySelector('.navbar-toggle').addEventListener('click', function() {
    this.classList.toggle('active');
    document.querySelector('.navbar-box').classList.toggle('active');
});
</script>