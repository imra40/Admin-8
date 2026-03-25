<?php
if (!function_exists('htmlspecialchars')) {
    function htmlspecialchars($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}
?>
<!-- Navbar -->
<div class="glass sticky top-0 z-50 border-b border-white/10">
    <div class="flex justify-between items-center px-4 md:px-6 py-4">
        <div class="flex items-center space-x-4 md:space-x-6">
            <a href="dashboard.php" class="text-xl md:text-2xl font-extrabold text-accent hover:opacity-80 transition flex items-center px-3 py-1.5 rounded-lg hover:bg-accent/10">⚡ KEY MANAGER</a>
            <nav class="hidden lg:flex items-center space-x-1">
                <a href="dashboard.php" class="px-4 py-2 rounded-lg hover:bg-accent/10 hover:text-accent transition flex items-center"><i class="bi bi-speedometer2 mr-2"></i>Dashboard</a>
                <a href="users.php" class="px-4 py-2 rounded-lg hover:bg-accent/10 hover:text-accent transition flex items-center"><i class="bi bi-people mr-2"></i>Users</a>
                <a href="resellers.php" class="px-4 py-2 rounded-lg hover:bg-accent/10 hover:text-accent transition flex items-center"><i class="bi bi-person-badge mr-2"></i>Resellers</a>
                <a href="reseller-prices.php" class="px-4 py-2 rounded-lg hover:bg-accent/10 hover:text-accent transition flex items-center"><i class="bi bi-tags mr-2"></i>Prices</a>
                <a href="products.php" class="px-4 py-2 rounded-lg hover:bg-accent/10 hover:text-accent transition flex items-center"><i class="bi bi-box-seam mr-2"></i>Products</a>
                <a href="keys.php" class="px-4 py-2 rounded-lg hover:bg-accent/10 hover:text-accent transition flex items-center"><i class="bi bi-key mr-2"></i>Keys</a>
                <a href="transactions.php" class="px-4 py-2 rounded-lg hover:bg-accent/10 hover:text-accent transition flex items-center"><i class="bi bi-wallet2 mr-2"></i>Transactions</a>
                <a href="settings.php" class="px-4 py-2 rounded-lg hover:bg-accent/10 hover:text-accent transition flex items-center"><i class="bi bi-gear mr-2"></i>Settings</a>
            </nav>
            <!-- Mobile menu button -->
            <button class="lg:hidden text-accent focus:outline-none" onclick="toggleMobileMenu()">
                <i class="bi bi-list text-2xl"></i>
            </button>
        </div>
        <div class="flex items-center space-x-2 md:space-x-3">
            <span class="hidden sm:block text-gray-400 text-sm md:text-base"><?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Admin'; ?></span>
            <a href="../logout.php" class="bg-accent hover:opacity-90 text-white px-3 md:px-4 py-2 rounded-lg font-medium transition text-sm md:text-base">Logout</a>
        </div>
    </div>
    <!-- Mobile menu -->
    <div id="mobileMenu" class="hidden lg:hidden border-t border-white/10">
        <nav class="flex flex-col px-4 py-2 space-y-1">
            <a href="dashboard.php" class="px-4 py-2 rounded-lg hover:bg-accent/10 hover:text-accent transition"><i class="bi bi-speedometer2 mr-2"></i>Dashboard</a>
            <a href="users.php" class="px-4 py-2 rounded-lg hover:bg-accent/10 hover:text-accent transition"><i class="bi bi-people mr-2"></i>Users</a>
            <a href="resellers.php" class="px-4 py-2 rounded-lg hover:bg-accent/10 hover:text-accent transition"><i class="bi bi-person-badge mr-2"></i>Resellers</a>
            <a href="reseller-prices.php" class="px-4 py-2 rounded-lg hover:bg-accent/10 hover:text-accent transition"><i class="bi bi-tags mr-2"></i>Prices</a>
            <a href="products.php" class="px-4 py-2 rounded-lg hover:bg-accent/10 hover:text-accent transition"><i class="bi bi-box-seam mr-2"></i>Products</a>
            <a href="keys.php" class="px-4 py-2 rounded-lg hover:bg-accent/10 hover:text-accent transition"><i class="bi bi-key mr-2"></i>Keys</a>
            <a href="transactions.php" class="px-4 py-2 rounded-lg hover:bg-accent/10 hover:text-accent transition"><i class="bi bi-wallet2 mr-2"></i>Transactions</a>
            <a href="settings.php" class="px-4 py-2 rounded-lg hover:bg-accent/10 hover:text-accent transition"><i class="bi bi-gear mr-2"></i>Settings</a>
        </nav>
    </div>
</div>

<script>
function toggleMobileMenu() {
    const menu = document.getElementById('mobileMenu');
    menu.classList.toggle('hidden');
}
</script>

