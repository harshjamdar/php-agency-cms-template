            </div>
        </div>
    </main>

    <!-- Initialize Lucide Icons -->
    <script>
        lucide.createIcons();

        // Mobile Menu Toggle
        const menuBtn = document.getElementById('mobile-menu-btn');
        const sidebar = document.getElementById('admin-sidebar');

        if (menuBtn && sidebar) {
            menuBtn.addEventListener('click', () => {
                sidebar.classList.toggle('hidden');
            });
        }
    </script>
</body>
</html>