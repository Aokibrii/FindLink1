/**
 * Profile page functionality
 */

// Handle modal functionality
function openEditModal() {
  document.getElementById("editModal").classList.add("active");
}

function closeEditModal() {
  document.getElementById("editModal").classList.remove("active");
}

function confirmLogout() {
  const logoutModal = document.getElementById("logout-modal");
  logoutModal.classList.add("active");

  // Remove any previous event listeners
  const proceedBtn = document.getElementById("logout-proceed-btn");
  const cancelBtn = document.getElementById("logout-cancel-btn");

  const oldProceedBtn = proceedBtn.cloneNode(true);
  const oldCancelBtn = cancelBtn.cloneNode(true);

  proceedBtn.parentNode.replaceChild(oldProceedBtn, proceedBtn);
  cancelBtn.parentNode.replaceChild(oldCancelBtn, cancelBtn);

  // Add new event listeners
  oldProceedBtn.addEventListener("click", function () {
    window.location.href = "logout.php";
  });

  oldCancelBtn.addEventListener("click", function () {
    logoutModal.classList.remove("active");
  });
}

// Handle profile image preview
function setupProfileImagePreview() {
  const fileInput = document.querySelector('input[name="profile_img"]');
  const previewImg = document.getElementById("profilePreviewImage");

  if (!fileInput || !previewImg) {
    console.error("Profile image preview elements not found");
    return;
  }

  fileInput.addEventListener("change", function (event) {
    if (this.files && this.files[0]) {
      console.log("File selected for preview");

      // Create FileReader to read the image
      const reader = new FileReader();

      reader.addEventListener("load", function (e) {
        console.log("File loaded into reader");
        previewImg.src = e.target.result;
      });

      reader.addEventListener("error", function () {
        console.error("Error reading file");
      });

      // Read the file as a data URL
      reader.readAsDataURL(this.files[0]);
    }
  });
}

// Initialize all profile functionality
document.addEventListener("DOMContentLoaded", function () {
  console.log("Profile page initialized");

  // Setup profile image preview
  setupProfileImagePreview();

  // Set click handlers for edit and logout buttons if they exist
  const editBtn = document.querySelector(".edit-btn");
  const logoutBtn = document.querySelector(".logout-btn");

  if (editBtn) {
    editBtn.addEventListener("click", openEditModal);
  }

  if (logoutBtn) {
    logoutBtn.addEventListener("click", confirmLogout);
  }
});
