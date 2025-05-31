// Mobile Navigation Toggle
document.addEventListener("DOMContentLoaded", function () {
  const hamburger = document.querySelector(".hamburger");
  const navMenu = document.querySelector(".nav-menu");

  if (hamburger && navMenu) {
    // This condition correctly wraps all mobile nav code
    hamburger.addEventListener("click", function () {
      hamburger.classList.toggle("active");
      navMenu.classList.toggle("active");
    });

    // Close mobile menu when clicking on a link
    // This part is now correctly nested inside the 'if' block
    const navLinks = document.querySelectorAll(".nav-link");
    navLinks.forEach((link) => {
      link.addEventListener("click", () => {
        hamburger.classList.remove("active");
        navMenu.classList.remove("active");
      });
    });
  } // End of the 'if (hamburger && navMenu)' block

  // Form Submission Handler for the main index.html form (if still used)
  const reportForm = document.getElementById("reportForm");
  if (reportForm) {
    reportForm.addEventListener("submit", handleReportSubmission);
  }

  // NEW: Form Submission Handler for the user's report form on laporan.php
  const userReportForm = document.getElementById("userReportForm");
  if (userReportForm) {
    userReportForm.addEventListener("submit", handleReportSubmission);
  }

  // Login Form Handler
  const loginForm = document.getElementById("loginForm");
  if (loginForm) {
    loginForm.addEventListener("submit", handleLogin);
  }

  // Register Form Handler
  const registerForm = document.getElementById("registerForm");
  if (registerForm) {
    registerForm.addEventListener("submit", handleRegister);
  }

  // Initialize demo data if none exists
  initializeDemoData();
}); // This is the single closing for the DOMContentLoaded function and listener.

// ... The rest of your script.js file (e.g., smooth scrolling, utility functions) should follow here ...
// DO NOT include any duplicate or extra 'DOMContentLoaded' blocks or standalone '}' characters.

// Smooth scrolling for anchor links (Modified to handle href="#")
document.addEventListener("click", function (e) {
  if (e.target.matches('a[href^="#"]')) {
    e.preventDefault(); // Prevent default hash behavior
    const href = e.target.getAttribute("href");
    if (href === "#") {
      // If href is just '#', do nothing or scroll to top
      window.scrollTo({ top: 0, behavior: "smooth" });
      return;
    }
    const target = document.querySelector(href);
    if (target) {
      target.scrollIntoView({
        behavior: "smooth",
        block: "start",
      });
    }
  }
});

// Handle Report Form Submission (AJAX to PHP handler)
function handleReportSubmission(e) {
  e.preventDefault();

  const form = e.target;
  const formData = new FormData(form); // FormData handles file uploads automatically

  // Show loading notification
  showNotification("Mengirim laporan...", "info");

  // Determine the correct PHP handler based on the form's ID or context
  // Since both index.html and laporan.php (for logged-in users) use user/php/user_handler.php
  // for submission, this URL remains consistent.
  fetch("../user/php/user_handler.php?action=submit_report", {
    method: "POST",
    body: formData, // FormData directly as body, fetch sets Content-Type automatically
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showNotification(
          data.message + " ID Laporan: " + data.reportId,
          "success"
        );
        form.reset(); // Reset form on success
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
  e.preventDefault();

  const username = document.getElementById("username").value;
  const password = document.getElementById("password").value;

  // Show loading notification
  showNotification("Authenticating...", "info");

  // Login using database API
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
        sessionStorage.setItem("userId", data.user.id); // Store user ID
        sessionStorage.setItem("userName", data.user.fullName); // Store user's full name
        sessionStorage.setItem("currentUser", JSON.stringify(data.user)); // Store full user object

        showNotification(
          `Welcome ${data.user.fullName || data.user.username}!`,
          "success"
        );

        // Redirect based on role
        if (data.user.role === "admin") {
          setTimeout(() => {
            window.location.href = "admin/admin-dashboard.html";
          }, 1000);
        } else {
          setTimeout(() => {
            window.location.href = "user/user-dashboard.html"; // Redirect to user dashboard
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
    return;
  }

  // Show loading notification
  showNotification("Mendaftarkan akun...", "info");
  // Register user using database API
  fetch("shared/php/register_handler.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json", // This implies JSON body
    },
    body: JSON.stringify({
      // So we stringify here
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
        // Show success message and redirect to login
        showRegistrationSuccess(data.username);
      } else {
        showNotification(data.error || "Registration failed", "error");
      }
    })
    .catch((error) => {
      console.error("Registration error:", error);
      showNotification("Terjadi kesalahan jaringan saat mendaftar", "error");
    });

  // Reset form
  e.target.reset();
}

// Show registration success with account creation indicator
function showRegistrationSuccess(username) {
  // Remove existing notification
  const existingNotification = document.querySelector(".notification");
  if (existingNotification) {
    existingNotification.remove();
  }

  // Create success notification with account created indicator
  const notification = document.createElement("div");
  notification.className = "notification notification-success account-created";
  notification.innerHTML = `
    <div class="success-content">
      <i class="fas fa-check-circle success-icon"></i>
      <div class="success-text">
        <h4>Akun Berhasil Dibuat!</h4>
        <p>Username: <strong>${username}</strong></p>
        <p>Anda akan dialihkan ke halaman login dalam <span id="countdown">3</span> detik...</p>
      </div>
    </div>
    <button class="notification-close">&times;</button>
  `;

  // Add custom styles for registration success
  notification.style.cssText = `
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #d4edda;
    color: #155724;
    padding: 2rem;
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    z-index: 1000;
    min-width: 400px;
    max-width: 500px;
    animation: popIn 0.3s ease;
    border: 2px solid #c3e6cb;
  `;

  // Add animation and custom styles
  if (!document.querySelector("#registrationStyles")) {
    const style = document.createElement("style");
    style.id = "registrationStyles";
    style.textContent = `
      @keyframes popIn {
        from { transform: translate(-50%, -50%) scale(0.8); opacity: 0; }
        to { transform: translate(-50%, -50%) scale(1); opacity: 1; }
      }
      .account-created .success-content {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
      }
      .account-created .success-icon {
        font-size: 2rem;
        color: #28a745;
        margin-top: 0.25rem;
      }
      .account-created .success-text h4 {
        margin: 0 0 0.5rem 0;
        font-size: 1.25rem;
        color: #155724;
      }
      .account-created .success-text p {
        margin: 0.25rem 0;
        color: #155724;
      }
      .account-created .notification-close {
        position: absolute;
        top: 0.5rem;
        right: 0.5rem;
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: #155724;
      }
    `;
    document.head.appendChild(style);
  }

  // Add backdrop
  const backdrop = document.createElement("div");
  backdrop.style.cssText = `
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 999;
  `;

  // Add to page
  document.body.appendChild(backdrop);
  document.body.appendChild(notification);

  // Countdown timer
  let countdown = 3;
  const countdownElement = notification.querySelector("#countdown");
  const timer = setInterval(() => {
    countdown--;
    if (countdownElement) {
      countdownElement.textContent = countdown;
    }
    if (countdown <= 0) {
      clearInterval(timer);
      backdrop.remove();
      notification.remove();
      window.location.href = "login.html";
    }
  }, 1000);

  // Close button functionality
  const closeBtn = notification.querySelector(".notification-close");
  closeBtn.addEventListener("click", () => {
    clearInterval(timer);
    backdrop.remove();
    notification.remove();
    window.location.href = "login.html";
  });

  // Click backdrop to close
  backdrop.addEventListener("click", () => {
    clearInterval(timer);
    backdrop.remove();
    notification.remove();
    window.location.href = "login.html";
  });
}

// Show notification
function showNotification(message, type = "info") {
  // Remove existing notification
  const existingNotification = document.querySelector(".notification");
  if (existingNotification) {
    existingNotification.remove();
  }

  // Create notification element
  const notification = document.createElement("div");
  notification.className = `notification notification-${type}`;
  notification.innerHTML = `
        <span>${message}</span>
        <button class="notification-close">&times;</button>
    `;

  // Add styles
  notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${
          type === "success"
            ? "#d4edda"
            : type === "error"
            ? "#f8d7da"
            : "#cce5ff"
        };
        color: ${
          type === "success"
            ? "#155724"
            : type === "error"
            ? "#721c24"
            : "#0056b3"
        };
        padding: 1rem 1.5rem;
        border-radius: 5px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        z-index: 1000;
        display: flex;
        align-items: center;
        gap: 1rem;
        min-width: 300px;
        animation: slideIn 0.3s ease;
    `;

  // Add animation keyframes
  if (!document.querySelector("#notificationStyles")) {
    const style = document.createElement("style");
    style.id = "notificationStyles";
    style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            .notification-close {
                background: none;
                border: none;
                font-size: 1.2rem;
                cursor: pointer;
                color: inherit;
            }
        `;
    document.head.appendChild(style);
  }

  // Add to page
  document.body.appendChild(notification);

  // Close button functionality
  const closeBtn = notification.querySelector(".notification-close");
  closeBtn.addEventListener("click", () => notification.remove());

  // Auto remove after 5 seconds
  setTimeout(() => {
    if (notification.parentNode) {
      notification.remove();
    }
  }, 5000);
}

// Form validation enhancement
function validateForm(form) {
  const requiredFields = form.querySelectorAll("[required]");
  let isValid = true;

  requiredFields.forEach((field) => {
    if (!field.value.trim()) {
      field.style.borderColor = "#e74c3c";
      isValid = false;
    } else {
      field.style.borderColor = "#ddd";
    }
  });

  return isValid;
}

// Email validation
function validateEmail(email) {
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return re.test(email);
}

// Phone validation (Indonesian format)
function validatePhone(phone) {
  const re = /^(\+62|62|0)[0-9]{9,13}$/;
  return re.test(phone.replace(/\s|-/g, ""));
}

// Add real-time validation to forms
document.addEventListener("input", function (e) {
  if (e.target.matches('input[type="email"]')) {
    if (e.target.value && !validateEmail(e.target.value)) {
      e.target.style.borderColor = "#e74c3c";
    } else {
      e.target.style.borderColor = "#ddd";
    }
  }

  if (e.target.matches('input[type="tel"]')) {
    if (e.target.value && !validatePhone(e.target.value)) {
      e.target.style.borderColor = "#e74c3c";
    } else {
      e.target.style.borderColor = "#ddd";
    }
  }
});

// Initialize demo data if none exists
function initializeDemoData() {
  const reports = getReports();
  if (reports.length === 0) {
    const demoReports = [
      {
        id: "RPT001",
        nama: "Ahmad Wijaya",
        email: "ahmad@email.com",
        telepon: "081234567890",
        kategori: "infrastruktur",
        judul: "Jalan Rusak di Jl. Merdeka",
        deskripsi: "Jalan berlubang besar yang membahayakan pengendara",
        lokasi: "Jl. Merdeka No. 45, Jakarta",
        tanggal: "2025-05-20",
        status: "pending",
      },
      {
        id: "RPT002",
        nama: "Siti Nurhaliza",
        email: "siti@email.com",
        telepon: "082345678901",
        kategori: "lingkungan",
        judul: "Sampah Menumpuk di TPS",
        deskripsi: "TPS tidak dibersihkan selama seminggu, menimbulkan bau",
        lokasi: "TPS Kelurahan Menteng",
        tanggal: "2025-05-22",
        status: "in_progress",
      },
      {
        id: "RPT003",
        nama: "Budi Santoso",
        email: "budi@email.com",
        telepon: "083456789012",
        kategori: "sosial",
        judul: "Lampu Penerangan Jalan Mati",
        deskripsi: "Lampu PJU mati total di sepanjang jalan, rawan kejahatan",
        lokasi: "Jl. Sudirman Km 5",
        tanggal: "2025-05-25",
        status: "completed",
      },
    ];

    localStorage.setItem("reports", JSON.stringify(demoReports));
  }
}

// THIS FUNCTION IS ADDED FOR THE DEMO DATA INITIALIZATION
function getReports() {
  try {
    return JSON.parse(localStorage.getItem("reports") || "[]");
  } catch (e) {
    console.error("Error parsing reports from localStorage:", e);
    return [];
  }
}

// Add this function to your shared/js/script.js file
function validateRegisterForm(userData) {
  let isValid = true;
  let errorMessage = "";

  // Helper to show error notifications
  function showFieldNotification(field, message) {
    // You can enhance this to highlight the specific field
    showNotification(message, "error");
  }

  // Check required fields (using existing validateForm if it can be adapted)
  // For simplicity, let's re-implement basic checks for clarity here:
  if (!userData.fullName.trim()) {
    errorMessage += "Nama Lengkap tidak boleh kosong. ";
    isValid = false;
  }
  if (!userData.email.trim()) {
    errorMessage += "Email tidak boleh kosong. ";
    isValid = false;
  } else if (!validateEmail(userData.email)) {
    errorMessage += "Format email tidak valid. ";
    isValid = false;
  }
  if (!userData.phone.trim()) {
    errorMessage += "No. Telepon tidak boleh kosong. ";
    isValid = false;
  } else if (!validatePhone(userData.phone)) {
    errorMessage +=
      "Format No. Telepon tidak valid (gunakan format Indonesia). ";
    isValid = false;
  }
  if (!userData.address.trim()) {
    errorMessage += "Alamat tidak boleh kosong. ";
    isValid = false;
  }
  if (!userData.nik.trim()) {
    errorMessage += "NIK tidak boleh kosong. ";
    isValid = false;
  } else if (!/^\d{16}$/.test(userData.nik)) {
    errorMessage += "NIK harus 16 digit angka. ";
    isValid = false;
  }
  if (!userData.username.trim()) {
    errorMessage += "Username tidak boleh kosong. ";
    isValid = false;
  }
  if (!userData.password.trim()) {
    errorMessage += "Password tidak boleh kosong. ";
    isValid = false;
  } else if (userData.password.length < 6) {
    // Align with shared/php/register_handler.php
    errorMessage += "Password minimal 6 karakter. ";
    isValid = false;
  }
  // Optional: Add stricter password complexity check here to match proses_register.php if desired
  // else if (!/[A-Z]/.test(userData.password) || !/[a-z]/.test(userData.password) || !/[0-9]/.test(userData.password) || !/[^A-Za-z0-9]/.test(userData.password)) {
  //   errorMessage += "Password harus berisi kombinasi huruf kapital, huruf kecil, angka, dan karakter khusus. ";
  //   isValid = false;
  // }

  if (!userData.confirmPassword.trim()) {
    errorMessage += "Konfirmasi Password tidak boleh kosong. ";
    isValid = false;
  } else if (userData.password !== userData.confirmPassword) {
    errorMessage += "Konfirmasi Password tidak cocok dengan Password. ";
    isValid = false;
  }

  if (!isValid) {
    showNotification(errorMessage.trim(), "error");
  }

  return isValid;
}

// Add this function to admin.js (and user-dashboard.js)
function handleChangePasswordFrontend() {
  const oldPassword = document.getElementById("oldPassword").value;
  const newPassword = document.getElementById("newPassword").value;
  const confirmNewPassword =
    document.getElementById("confirmNewPassword").value;

  if (!oldPassword || !newPassword || !confirmNewPassword) {
    showNotification("Semua field kata sandi harus diisi.", "error");
    return;
  }

  if (newPassword !== confirmNewPassword) {
    showNotification(
      "Kata sandi baru dan konfirmasi kata sandi tidak cocok.",
      "error"
    );
    return;
  }

  if (newPassword.length < 6) {
    showNotification("Kata sandi baru minimal 6 karakter.", "error");
    return;
  }

  showNotification("Mengubah kata sandi...", "info");

  fetch("../shared/php/auth.php", {
    // Path to the shared auth handler
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      action: "change_password",
      old_password: oldPassword,
      new_password: newPassword,
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showNotification(data.message, "success");
        // Clear password fields on success
        document.getElementById("oldPassword").value = "";
        document.getElementById("newPassword").value = "";
        document.getElementById("confirmNewPassword").value = "";
        // Optionally close modal or reload profile details
        closeUserProfileModal();
      } else {
        showNotification(data.error || "Gagal mengubah kata sandi.", "error");
      }
    })
    .catch((error) => {
      console.error("Change password error:", error);
      showNotification(
        "Terjadi kesalahan jaringan saat mengubah kata sandi.",
        "error"
      );
    });
}
