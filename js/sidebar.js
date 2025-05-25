document.addEventListener("DOMContentLoaded", function () {
  // Get necessary elements
  const sideNav = document.getElementById("sideNav");
  const mainContent = document.getElementById("mainContent");
  const toggleBtn = document.getElementById("toggleNav");

  // Check if navigation state is saved in localStorage
  const navExpanded = localStorage.getItem("navExpanded") === "true";

  // Apply initial state
  if (navExpanded) {
    sideNav.classList.add("expanded");
    mainContent.classList.add("nav-expanded");
  } else {
    sideNav.classList.remove("expanded");
    mainContent.classList.remove("nav-expanded");
  }

  // Toggle sidebar function
  function toggleSidebar() {
    sideNav.classList.toggle("expanded");
    mainContent.classList.toggle("nav-expanded");

    // Save state to localStorage
    localStorage.setItem("navExpanded", sideNav.classList.contains("expanded"));

    // For featured items or other content that might need adjustment
    if (typeof adjustFeaturedItems === "function") {
      setTimeout(adjustFeaturedItems, 300);
    }
  }

  // Add event listener to toggle button
  if (toggleBtn) {
    toggleBtn.addEventListener("click", function () {
      toggleSidebar();
    });
  }

  // Add menu toggle button for mobile if it doesn't exist
  if (window.innerWidth <= 768 && !document.querySelector(".menu-toggle")) {
    const menuToggle = document.createElement("button");
    menuToggle.className = "menu-toggle";
    menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
    document.body.appendChild(menuToggle);

    menuToggle.addEventListener("click", toggleSidebar);
  }

  // Handle window resize
  window.addEventListener("resize", function () {
    if (window.innerWidth <= 768) {
      // Switch to mobile view
      if (!navExpanded) {
        sideNav.classList.remove("expanded");
        mainContent.classList.remove("nav-expanded");
      }
    } else {
      // Switch to desktop view
      sideNav.classList.remove("expanded");
      mainContent.classList.remove("nav-expanded");
      mainContent.style.marginLeft = navExpanded ? "var(--sidebar-width)" : "0";
    }
  });
});
