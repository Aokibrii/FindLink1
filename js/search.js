document.addEventListener("DOMContentLoaded", function () {
  // Search toggle functionality
  const searchContainer = document.getElementById("searchContainer");
  const searchToggle = document.getElementById("searchToggle");
  const searchInput = document.getElementById("searchInput");

  if (searchToggle && searchContainer && searchInput) {
    searchToggle.addEventListener("click", function () {
      searchContainer.classList.toggle("collapsed");
      searchContainer.classList.toggle("expanded");

      if (searchContainer.classList.contains("expanded")) {
        setTimeout(() => {
          searchInput.focus();
        }, 300);
      } else {
        // Collapse search and hide suggestions
        const suggestions = document.getElementById("suggestions");
        if (suggestions) {
          suggestions.style.display = "none";
        }
      }
    });

    // Close search when clicking outside
    document.addEventListener("click", function (event) {
      if (
        !searchContainer.contains(event.target) &&
        searchContainer.classList.contains("expanded")
      ) {
        // Only collapse if there's no text in the search field
        if (searchInput.value.trim() === "") {
          searchContainer.classList.remove("expanded");
          searchContainer.classList.add("collapsed");
        }
      }
    });
  }

  // Clear search input functionality
  const clearBtn = document.getElementById("clearSearch");
  if (clearBtn && searchInput) {
    clearBtn.addEventListener("click", function () {
      searchInput.value = "";
      searchInput.focus();
      const suggestions = document.getElementById("suggestions");
      if (suggestions) {
        suggestions.style.display = "none";
      }

      // If there was a search term before clearing, redirect to page without search
      if (window.location.search.includes("search=")) {
        window.location.href = "user_page.php";
      }
    });
  }

  // Filter functionality
  const filterBtns = document.querySelectorAll(".filter-btn");
  const itemCards = document.querySelectorAll(".item-card");

  if (filterBtns.length > 0 && itemCards.length > 0) {
    filterBtns.forEach((btn) => {
      btn.addEventListener("click", function () {
        // Remove active class from all buttons
        filterBtns.forEach((b) => b.classList.remove("active"));
        // Add active class to clicked button
        this.classList.add("active");

        const filter = this.getAttribute("data-filter");

        itemCards.forEach((card) => {
          if (filter === "all" || card.getAttribute("data-type") === filter) {
            card.style.display = "flex";
          } else {
            card.style.display = "none";
          }
        });
      });
    });
  }

  // Initialize login success modal if it exists
  const modalElement = document.getElementById("userLoginSuccessModal");
  if (modalElement && typeof bootstrap !== "undefined") {
    try {
      const loginModal = new bootstrap.Modal(modalElement);
      loginModal.show();

      // Hide after 4 seconds if user doesn't click the continue button
      setTimeout(function () {
        loginModal.hide();
      }, 4000);
    } catch (error) {
      console.error("Error showing login modal:", error);
    }
  }
});

function showSuggestions(str) {
  const suggestionsDiv = document.getElementById("suggestions");
  if (!suggestionsDiv) return;

  if (str.length === 0) {
    suggestionsDiv.innerHTML = "";
    suggestionsDiv.style.display = "none";
    return;
  }

  // Only search after user has typed at least 2 characters
  if (str.length < 2) return;

  const xhr = new XMLHttpRequest();
  xhr.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
      const results = JSON.parse(this.responseText);

      if (results.length > 0) {
        let html = "";
        results.forEach((item) => {
          const badgeClass = item.item_type === "Found" ? "Found" : "Lost";
          const location = item.location ? ` - ${item.location}` : "";
          const description = item.description
            ? `<br><small style="color: #666; font-size: 12px;">${item.description.substring(
                0,
                60
              )}${item.description.length > 60 ? "..." : ""}</small>`
            : "";
          html += `<div class="suggestion" onclick="selectSuggestion('${item.title.replace(
            /'/g,
            "\\'"
          )}')">
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <div>
                <strong>${item.title}</strong>${location}
                ${description}
              </div>
              <span class="item-type ${badgeClass}">${item.item_type}</span>
            </div>
          </div>`;
        });
        suggestionsDiv.innerHTML = html;
        suggestionsDiv.style.display = "block";
      } else {
        suggestionsDiv.innerHTML =
          '<div class="no-suggestions">No items found</div>';
        suggestionsDiv.style.display = "block";
      }
    }
  };
  xhr.open(
    "GET",
    "user_page.php?ajax_search=1&q=" + encodeURIComponent(str),
    true
  );
  xhr.send();
}

function selectSuggestion(text) {
  const searchInput = document.getElementById("searchInput");
  const suggestionsDiv = document.getElementById("suggestions");
  const searchForm = document.getElementById("searchForm");

  if (searchInput && suggestionsDiv && searchForm) {
    searchInput.value = text;
    suggestionsDiv.style.display = "none";
    searchForm.submit();
  }
}

// Close suggestions when clicking outside
document.addEventListener("click", function (event) {
  const suggestionsDiv = document.getElementById("suggestions");
  const searchInput = document.getElementById("searchInput");

  if (
    suggestionsDiv &&
    searchInput &&
    event.target !== searchInput &&
    event.target !== suggestionsDiv
  ) {
    suggestionsDiv.style.display = "none";
  }
});
