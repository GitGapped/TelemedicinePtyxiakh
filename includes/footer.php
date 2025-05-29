    </div>
    <!-- Footer -->
    <footer class="footer">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12 text-center">
                    <p class="mb-0">&copy; <?php echo date("Y"); ?> Telehealth Application. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content') || document.querySelector('main.col-md-9');
            const footer = document.querySelector('.footer');
            const navbarToggleBtn = document.querySelector('.navbar-toggler');
            const sidebarToggleBtn = document.querySelector('.sidebar-toggler');
            const navbarCollapse = document.querySelector('.navbar-collapse');

            // Function to update sidebar position based on navbar height
            function updateSidebarPosition() {
                const navHeight = navbarCollapse.classList.contains('show') ? 
                    navbarCollapse.offsetHeight : 0;
                document.documentElement.style.setProperty('--nav-height', navHeight + 'px');
            }

            // Sidebar toggle button event
            if (sidebarToggleBtn) {
                sidebarToggleBtn.addEventListener('click', function(e) {
                    e.stopPropagation(); // Prevent event bubbling
                    sidebar.classList.toggle('show');
                    if (mainContent) mainContent.classList.toggle('expanded');
                    if (footer) footer.classList.toggle('expanded');
                });
            }
            
            // Navbar toggle button no longer toggles sidebar
            if (navbarToggleBtn) {
                navbarToggleBtn.addEventListener('click', function() {
                    // Wait for the navbar collapse animation to complete
                    setTimeout(updateSidebarPosition, 350);
                });
            }
            
            // Listen for Bootstrap's collapse events
            if (navbarCollapse) {
                navbarCollapse.addEventListener('shown.bs.collapse', updateSidebarPosition);
                navbarCollapse.addEventListener('hidden.bs.collapse', updateSidebarPosition);
            }

            // Initialize sidebar position
            updateSidebarPosition();
            
            // Adjust on window resize
            window.addEventListener('resize', updateSidebarPosition);
            
            // Close sidebar when clicking outside
            document.addEventListener('click', function(event) {
                // Check if sidebar is visible and click is outside sidebar and toggle button
                if (sidebar && sidebar.classList.contains('show') && 
                    !sidebar.contains(event.target) && 
                    !sidebarToggleBtn.contains(event.target)) {
                    sidebar.classList.remove('show');
                    if (mainContent) mainContent.classList.remove('expanded');
                    if (footer) footer.classList.remove('expanded');
                }
            });
            
            // Scroll to bottom of chat box (if exists)
            const chatBox = document.querySelector('.chat-box');
            if (chatBox) {
                chatBox.scrollTop = chatBox.scrollHeight;
            }
        });
    </script>
</body>
</html>

