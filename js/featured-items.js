/**
 * Adjusts the featured items layout when sidebar is toggled
 */
function adjustFeaturedItems() {
  // Get featured items container
  const featuredItemsContainer = document.querySelector(
    ".featured-items-section"
  );

  if (featuredItemsContainer) {
    // Recalculate container width based on available space
    const sideNav = document.getElementById("sideNav");
    const isExpanded = sideNav.classList.contains("expanded");

    // Apply proper width adjustment without affecting other content
    if (window.innerWidth <= 768) {
      // For mobile view
      if (isExpanded) {
        featuredItemsContainer.style.width = "calc(100% - 200px)";
        featuredItemsContainer.style.marginLeft = "200px";
      } else {
        featuredItemsContainer.style.width = "100%";
        featuredItemsContainer.style.marginLeft = "0";
      }
    } else {
      // For desktop view
      if (isExpanded) {
        featuredItemsContainer.style.marginLeft = "0";
      }
    }

    // Trigger reflow for items inside the container
    const itemCards = document.querySelectorAll(".item-card");
    if (itemCards.length > 0) {
      // Recalculate item sizes if needed based on new container width
      itemCards.forEach((item) => {
        // Apply smooth transition
        item.style.transition = "all 0.3s ease";
      });
    }
  }
}

// Run on page load
document.addEventListener("DOMContentLoaded", function () {
  // Initial adjustment
  setTimeout(adjustFeaturedItems, 300);

  // Listen for sidebar toggle
  const toggleBtn = document.getElementById("toggleNav");
  if (toggleBtn) {
    toggleBtn.addEventListener("click", function () {
      setTimeout(adjustFeaturedItems, 300);
    });
  }

  // Listen for window resize
  window.addEventListener("resize", adjustFeaturedItems);
});
