function showForm(formId) {
  // Hide welcome message
  const welcomeMessage = document.querySelector(".welcome-message");
  if (welcomeMessage) {
    welcomeMessage.style.display = "none";
  }

  // Hide all forms first
  document.getElementById("login-form").classList.remove("active");
  document.getElementById("register-form").classList.remove("active");

  // Show the selected form
  document.getElementById(formId).classList.add("active");

  // Add centered class to container
  document.querySelector(".container").classList.add("forms-active");
}

function showWelcome() {
  // Show welcome message
  document.querySelector(".welcome-message").style.display = "block";

  // Hide all forms
  document.getElementById("login-form").classList.remove("active");
  document.getElementById("register-form").classList.remove("active");

  // Remove centered class from container
  document.querySelector(".container").classList.remove("forms-active");
}

document.addEventListener("DOMContentLoaded", function () {
  // Logout Modal Handler
  const logoutButtons = document.querySelectorAll(".logout-btn");
  const logoutModal = document.getElementById("logout-modal");

  if (logoutModal) {
    const logoutProceedBtn = document.getElementById("logout-proceed-btn");
    const logoutCancelBtn = document.getElementById("logout-cancel-btn");
    let lastFocusedElement = null;

    const openLogoutModal = () => {
      lastFocusedElement = document.activeElement;
      logoutModal.classList.add("active");
      document.body.style.overflow = "hidden";
      setTimeout(
        () => document.querySelector("#logout-modal button")?.focus(),
        10
      );
    };

    const closeLogoutModal = () => {
      logoutModal.classList.remove("active");
      document.body.style.overflow = "";
      lastFocusedElement?.focus();
    };

    logoutButtons.forEach((btn) =>
      btn.addEventListener("click", (e) => {
        e.preventDefault();
        openLogoutModal();
      })
    );

    logoutCancelBtn?.addEventListener("click", closeLogoutModal);
    logoutProceedBtn?.addEventListener(
      "click",
      () => (window.location.href = "logout.php")
    );

    logoutModal.addEventListener("mousedown", (e) => {
      if (e.target === logoutModal) closeLogoutModal();
    });

    logoutModal.addEventListener("keydown", (e) => {
      if (e.key === "Escape") closeLogoutModal();
      if (e.key === "Tab") {
        const focusable = logoutModal.querySelectorAll("button");
        if (focusable.length === 0) return;

        if (e.shiftKey && document.activeElement === focusable[0]) {
          focusable[focusable.length - 1].focus();
          e.preventDefault();
        } else if (
          !e.shiftKey &&
          document.activeElement === focusable[focusable.length - 1]
        ) {
          focusable[0].focus();
          e.preventDefault();
        }
      }
    });
  }

  // Welcome Modal
  const welcomeModal = document.getElementById("userLoginSuccessModal");
  if (welcomeModal) {
    const modal = new bootstrap.Modal(welcomeModal);
    modal.show();
    setTimeout(() => modal.hide(), 3000);
  }

  // Profile Image Preview
  const fileInput = document.querySelector('input[name="profile_img"]');
  if (fileInput) {
    fileInput.addEventListener("change", function () {
      if (this.files?.[0]) {
        const preview = document.querySelector(".profile-img-preview img");
        if (preview) {
          const reader = new FileReader();
          reader.onload = (e) => (preview.src = e.target.result);
          reader.readAsDataURL(this.files[0]);
        }
      }
    });
  }

  // Edit Profile Modal
  const editModal = document.getElementById("editModal");
  if (editModal) {
    // Close modals when clicking outside
    document.addEventListener("click", (e) => {
      if (e.target === editModal) closeEditModal();
    });

    // Close modals when pressing Escape key
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && editModal.classList.contains("active")) {
        closeEditModal();
      }
    });
  }
});

async function confirmAction(message) {
  const modal = document.getElementById("confirmation-modal");
  const modalMessage = document.getElementById("confirmation-message");
  const proceedBtn = document.getElementById("confirm-proceed-btn");
  const cancelBtn = document.getElementById("confirm-cancel-btn");

  if (!modal || !modalMessage || !proceedBtn || !cancelBtn) {
    console.error("Required elements for confirmation modal are missing.");
    return false;
  }

  modalMessage.textContent = message;
  modal.style.display = "block";

  return new Promise((resolve) => {
    proceedBtn.onclick = () => {
      modal.style.display = "none";
      resolve(true);
    };
    cancelBtn.onclick = () => {
      modal.style.display = "none";
      resolve(false);
    };
  });
}

function togglePasswordVisibility(passwordId, eyeCloseId, eyeOpenId) {
  const passwordInput = document.getElementById(passwordId);
  const eyeClose = document.getElementById(eyeCloseId);
  const eyeOpen = document.getElementById(eyeOpenId);

  if (passwordInput.type === "password") {
    passwordInput.type = "text";
    eyeClose.style.display = "none";
    eyeOpen.style.display = "inline";
  } else {
    passwordInput.type = "password";
    eyeClose.style.display = "inline";
    eyeOpen.style.display = "none";
  }
}

function confirmRegister() {
  const password = document.getElementById("register-password").value;
  const confirmPassword = document.getElementById("confirm-password").value;
  if (password !== confirmPassword) {
    alert("Passwords do not match!");
    return false;
  }
  return true;
}

// Edit Profile Modal Functions
function openEditModal() {
  const modal = document.getElementById("editModal");
  modal.classList.add("active");
  document.body.style.overflow = "hidden";
}

function closeEditModal() {
  const modal = document.getElementById("editModal");
  modal.classList.remove("active");
  document.body.style.overflow = "";
}
