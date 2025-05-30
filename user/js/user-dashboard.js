// Mobile Navigation Toggle
console.log("DEBUG: script.js loaded."); // ADD THIS
document.addEventListener("DOMContentLoaded", function () {
  console.log("DEBUG: DOMContentLoaded fired."); // ADD THIS
  const hamburger = document.querySelector(".hamburger");
  const navMenu = document.querySelector(".nav-menu");

  if (hamburger && navMenu) {
    hamburger.addEventListener("click", function () {
      hamburger.classList.toggle("active");
      navMenu.classList.toggle("active");
    });
  }

  // Close mobile menu when clicking on a link
  const navLinks = document.querySelectorAll(".nav-link");
  navLinks.forEach((link) => {
    link.addEventListener("click", () => {
      hamburger.classList.remove("active");
      navMenu.classList.remove("active");
    });
  });

  // Form Submission Handler
  const reportForm = document.getElementById("reportForm");
  if (reportForm) {
    reportForm.addEventListener("submit", handleReportSubmission);
    console.log("DEBUG: Report form listener attached."); // ADD THIS
  }
  // Login Form Handler
  const loginForm = document.getElementById("loginForm");
  if (loginForm) {
    loginForm.addEventListener("submit", handleLogin);
    console.log("DEBUG: Login form listener attached."); // ADD THIS
  }

  // Register Form Handler
  const registerForm = document.getElementById("registerForm");
  if (registerForm) {
    registerForm.addEventListener("submit", handleRegister);
    console.log("DEBUG: Register form listener attached."); // ADD THIS
  }

  // Initialize demo data if none exists
  initializeDemoData();
  console.log("DEBUG: initializeDemoData called."); // ADD THIS
}); // This closes the DOMContentLoaded listener

// Smooth scrolling for anchor links
document.addEventListener("click", function (e) {
  if (e.target.matches('a[href^="#"]')) {
    e.preventDefault();
    const target = document.querySelector(e.target.getAttribute("href"));
    if (target) {
      target.scrollIntoView({
        behavior: "smooth",
        block: "start",
      });
    }
  }
}); // Correctly closes the click event listener. This is the last character for this listener.

// Handle Report Form Submission (AJAX to PHP handler)
function handleReportSubmission(e) {
  console.log("DEBUG: handleReportSubmission triggered."); // ADD THIS
  e.preventDefault();

  const form = e.target;
  const formData = new FormData(form);

  showNotification("Mengirim laporan...", "info");

  fetch("user/php/user_handler.php?action=submit_report", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showNotification(
          data.message + " ID Laporan: " + data.reportId,
          "success"
        );
        form.reset();
      } else {
        showNotification(data.error || "Gagal mengirim laporan", "error");
      }
    })
    .catch((error) => {
      console.error("Report submission error:", error);
      showNotification("Terjadi kesalahan saat mengirim laporan", "error");
    });
}

// Handle Login
function handleLogin(e) {
  console.log("DEBUG: handleLogin triggered."); // ADD THIS
  e.preventDefault();

  const username = document.getElementById("username").value;
  const password = document.getElementById("password").value;

  showNotification("Authenticating...", "info");

  fetch("shared/php/login_handler.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      username: username,
      password: password,
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        sessionStorage.setItem("userLoggedIn", "true");
        sessionStorage.setItem("userRole", data.user.role);
        sessionStorage.setItem("userEmail", data.user.email);
        sessionStorage.setItem("userId", data.user.id);
        sessionStorage.setItem("userName", data.user.fullName);
        sessionStorage.setItem("currentUser", JSON.stringify(data.user));

        showNotification(
          `Welcome ${data.user.fullName || data.user.username}!`,
          "success"
        );

        if (data.user.role === "admin") {
          setTimeout(() => {
            window.location.href = "admin/admin-dashboard.html";
          }, 1000);
        } else {
          setTimeout(() => {
            window.location.href = "user/user-dashboard.html";
          }, 1000);
        }
      } else {
        showNotification(data.error || "Login failed", "error");
      }
    })
    .catch((error) => {
      console.error("Login error:", error);
      showNotification("Terjadi kesalahan jaringan saat login", "error");
    });
}

// Handle Register
function handleRegister(e) {
  console.log("DEBUG: handleRegister triggered."); // ADD THIS
  e.preventDefault();

  const formData = new FormData(e.target);
  const userData = {
    fullName: formData.get("fullName"),
    email: formData.get("email"),
    phone: formData.get("phone"),
    address: formData.get("address"),
    nik: formData.get("nik"),
    username: formData.get("username"),
    password: formData.get("password"),
    confirmPassword: formData.get("confirmPassword"),
  };

  // Validation
  if (!validateRegisterForm(userData)) {
    console.log("DEBUG: Registration form validation failed."); // ADD THIS
    return;
  }
  console.log("DEBUG: Registration form validation passed."); // ADD THIS

  showNotification("Mendaftarkan akun...", "info");

  fetch("shared/php/register_handler.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      fullName: userData.fullName,
      email: userData.email,
      phone: userData.phone,
      address: userData.address,
      nik: userData.nik,
      username: userData.username,
      password: userData.password,
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showRegistrationSuccess(data.username);
      } else {
        showNotification(data.error || "Registration failed", "error");
      }
    })
    .catch((error) => {
      console.error("Registration error:", error);
      showNotification("Terjadi kesalahan jaringan saat mendaftar", "error");
    });

  e.target.reset();
}

// Show registration success with account creation indicator
function showRegistrationSuccess(username) {
  // ... (function body) ...
}

// Show notification
function showNotification(message, type = "info") {
  // ... (function body) ...
}

// Form validation enhancement
function validateForm(form) {
  // ... (function body) ...
}

// Email validation
function validateEmail(email) {
  // ... (function body) ...
}

// Phone validation (Indonesian format)
function validatePhone(phone) {
  // ... (function body) ...
}

// Add real-time validation to forms
document.addEventListener("input", function (e) {
  // ... (function body) ...
});

// Initialize demo data if none exists
function initializeDemoData() {
  // ... (function body) ...
}

function getReports() {
  // Assuming this is defined somewhere, if not, it will cause an error
  try {
    return JSON.parse(localStorage.getItem("reports") || "[]");
  } catch (e) {
    console.error("Error parsing reports from localStorage:", e);
    return [];
  }
}
