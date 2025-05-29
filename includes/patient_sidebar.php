<?php
// Prevent direct access to this file
if (!defined('INCLUDED')) {
    header("Location: login.php");
    exit();
}
?>

<div class="sidebar">
    <h3>Patient Dashboard</h3>
    <ul>
        <li><a href="patient_dashboard.php">Dashboard</a></li>
        <li><a href="view_profile.php">My profile</a></li>
        <li><a href="book_appointment.php">Appointments</a></li>
        <li><a href="patient_chat.php">Messaging</a></li>
        <li><a href="logout.php" onclick="return confirmLogout()">Logout</a></li>
    </ul>
</div>

<script>
  // Add icons to sidebar menu items for the patient
  function addSidebarIcons() {
    const sidebarLinks = document.querySelectorAll('.sidebar ul li a');
    const icons = {
      'Dashboard': '🏠',
      'My profile': '👤',
      'Update Profile': '✏️',
      'Appointments': '📅',
      'Messaging': '💬',
      'Reports': '📋',
      'Logout': '🚪'
    };
    
    sidebarLinks.forEach(link => {
      const linkText = link.textContent.trim();
      if (icons[linkText]) {
        link.innerHTML = `<span class="medical-icon">${icons[linkText]}</span> ${linkText}`;
      }
    });
    
    // Highlight active page
    const currentPage = window.location.pathname.split('/').pop();
    sidebarLinks.forEach(link => {
      if (link.getAttribute('href') === currentPage) {
        link.classList.add('active');
      }
    });
  }

  function adjustSidebar() {
    const header = document.querySelector('header');
    const navbar = document.querySelector('.navbar');
    const navbarCollapse = document.querySelector('.navbar-collapse');
    const footer = document.querySelector('footer');
    const sidebar = document.querySelector('.sidebar');
    
    if (sidebar && navbar && footer) {
        const navbarHeight = navbar.offsetHeight;
        const navCollapseHeight = navbarCollapse && navbarCollapse.classList.contains('show') ? 
            navbarCollapse.offsetHeight : 0;
        const windowHeight = window.innerHeight;
        
        // Set initial position and height considering navbar collapse state
        sidebar.style.position = 'fixed';
        sidebar.style.top = (navbarHeight + navCollapseHeight) + 'px';
        sidebar.style.height = (windowHeight - navbarHeight - navCollapseHeight) + 'px';
        
        // Adjust main content margin
        const mainContent = document.querySelector('.main-content');
        if (mainContent) {
            mainContent.style.marginLeft = (sidebar.offsetWidth + 20) + 'px';
        }
    }
  }

  // Function to handle scrolling
  function handleScroll() {
    const navbar = document.querySelector('.navbar');
    const navbarCollapse = document.querySelector('.navbar-collapse');
    const footer = document.querySelector('footer');
    const sidebar = document.querySelector('.sidebar');
    
    if (navbar && sidebar && footer) {
        const navbarHeight = navbar.offsetHeight;
        const navCollapseHeight = navbarCollapse && navbarCollapse.classList.contains('show') ? 
            navbarCollapse.offsetHeight : 0;
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const windowHeight = window.innerHeight;
        
        // Calculate footer position relative to viewport
        const footerRect = footer.getBoundingClientRect();
        const footerTop = footerRect.top;
        
        if (scrollTop > 0) {
            // When scrolling down, adjust top position
            const topPosition = Math.min(navbarHeight, navCollapseHeight > 0 ? navbarHeight + navCollapseHeight : 0);
            sidebar.style.top = topPosition + 'px';
            
            // Adjust sidebar height if footer is in view
            if (footerTop < windowHeight) {
                // Reduce sidebar height to stop at footer
                sidebar.style.height = (footerTop - topPosition) + 'px';
            } else {
                // Reset sidebar height to fill viewport
                sidebar.style.height = (windowHeight - topPosition) + 'px';
            }
        } else {
            // When at top of page, position sidebar below navbar
            const topPosition = navbarHeight + navCollapseHeight;
            sidebar.style.top = topPosition + 'px';
            sidebar.style.height = (windowHeight - topPosition) + 'px';
        }
    }
  }
  
  // Call all functions when the DOM is loaded
  document.addEventListener('DOMContentLoaded', function() {
    addSidebarIcons();
    adjustSidebar();
    handleScroll(); // Initial check
    window.addEventListener('scroll', handleScroll);
  });
  
  // Adjust sidebar on window resize
  window.addEventListener('resize', function() {
    adjustSidebar();
    handleScroll(); // Check after resize
  });

  function confirmLogout() {
    return confirm("Are you sure you want to log out?");
}
</script>
