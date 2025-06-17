        </main> <!-- .page-content kapanışı -->
    </div> <!-- .main-content-area kapanışı -->
</div> <!-- .owner-panel-wrapper kapanışı -->

<script>
    // Feather Icons'ı etkinleştir
    feather.replace();

    // Mobil menü toggle mantığı
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        const mobileMenuButton = document.getElementById('mobile-menu-toggle');
        
        function openMenu() {
            sidebar.classList.add('is-open');
        }
        
        function closeMenu() {
            sidebar.classList.remove('is-open');
        }

        if (mobileMenuButton) {
            mobileMenuButton.addEventListener('click', function(e) {
                e.stopPropagation();
                if (sidebar.classList.contains('is-open')) {
                    closeMenu();
                } else {
                    openMenu();
                }
            });
        }

        if(overlay) {
            overlay.addEventListener('click', closeMenu);
        }
    });
</script>

</body>
</html>
