// Mobile Navigation Toggle
document.addEventListener("DOMContentLoaded", function () {
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
});

// Handle Report Form Submission
function handleReportSubmission(e) {
  e.preventDefault();

  const formData = new FormData(e.target);
  const reportData = {
    nama: formData.get("nama"),
    email: formData.get("email"),
    telepon: formData.get("telepon"),
    kategori: formData.get("kategori"),
    judul: formData.get("judul"),
    deskripsi: formData.get("deskripsi"),
    lokasi: formData.get("lokasi"),
    tanggal: new Date().toISOString().split("T")[0],
    status: "pending",
    id: generateReportId(),
  };

  // Simpan ke localStorage (simulasi database)
  saveReport(reportData);

  // Show success message
  showNotification(
    "Laporan berhasil dikirim! ID Laporan: " + reportData.id,
    "success"
  );

  // Reset form
  e.target.reset();
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
        // Set session data
        sessionStorage.setItem("userLoggedIn", "true");
        sessionStorage.setItem("userRole", data.user.role);
        sessionStorage.setItem("userEmail", data.user.email);
        sessionStorage.setItem("currentUser", JSON.stringify(data.user));

        // Show success message
        showNotification(`Welcome ${data.user.fullName}!`, "success");

        // Redirect based on role
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
        // Show error message
        showNotification(data.error || "Login failed", "error");
      }
    })
    .catch((error) => {
      console.error("Login error:", error);
      // Fallback to localStorage authentication
      handleLoginFallback(username, password);
    });
}

// Fallback login using localStorage
function handleLoginFallback(username, password) {
  // Simple authentication check for default accounts
  if (username === "admin" && password === "admin123") {
    // Set session for admin
    sessionStorage.setItem("userLoggedIn", "true");
    sessionStorage.setItem("userRole", "admin");
    sessionStorage.setItem("userEmail", "admin@gmail.com");
    sessionStorage.setItem(
      "currentUser",
      JSON.stringify({
        username: "admin",
        fullName: "Administrator",
        role: "admin",
        email: "admin@gmail.com",
      })
    );
    showNotification("Welcome Administrator!", "success");
    setTimeout(() => {
      window.location.href = "admin/admin-dashboard.html";
    }, 1000);
    return;
  }

  if (username === "user" && password === "user123") {
    // Set session for default user
    sessionStorage.setItem("userLoggedIn", "true");
    sessionStorage.setItem("userRole", "user");
    sessionStorage.setItem("userEmail", "user@gmail.com");
    sessionStorage.setItem(
      "currentUser",
      JSON.stringify({
        username: "user",
        fullName: "Demo User",
        role: "user",
        email: "user@gmail.com",
      })
    );
    showNotification("Welcome Demo User!", "success");
    setTimeout(() => {
      window.location.href = "user/user-dashboard.html";
    }, 1000);
    return;
  }

  // Check registered users from localStorage
  const users = getUsers();
  const user = users.find(
    (u) => u.username === username && u.password === password
  );

  if (user) {
    sessionStorage.setItem("userLoggedIn", "true");
    sessionStorage.setItem("userRole", "user");
    sessionStorage.setItem("userEmail", user.email);
    sessionStorage.setItem("currentUser", JSON.stringify(user));
    showNotification(`Welcome ${user.fullName}!`, "success");
    setTimeout(() => {
      window.location.href = "user/user-dashboard.html";
    }, 1000);
  } else {
    showNotification("Username atau password salah!", "error");
  }
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
        // Show success message and redirect to login
        showRegistrationSuccess(data.username);
      } else {
        // Show error message
        showNotification(data.error || "Registration failed", "error");
      }
    })
    .catch((error) => {
      console.error("Registration error:", error);
      // Fallback to localStorage if database fails
      handleRegistrationFallback(userData);
    });

  // Reset form
  e.target.reset();
}

// Fallback registration using localStorage
function handleRegistrationFallback(userData) {
  // Check if username already exists in localStorage
  const users = getUsers();
  if (users.find((u) => u.username === userData.username)) {
    showNotification("Username sudah digunakan! Pilih username lain.", "error");
    return;
  }

  // Check if email already exists in localStorage
  if (users.find((u) => u.email === userData.email)) {
    showNotification("Email sudah terdaftar! Gunakan email lain.", "error");
    return;
  }

  // Save user to localStorage as fallback
  const userToSave = {
    ...userData,
    userId: generateUserId(),
    registeredDate: new Date().toISOString().split("T")[0],
  };
  delete userToSave.confirmPassword;

  saveUser(userToSave);
  showRegistrationSuccess(userData.username);
}

// Generate unique report ID
function generateReportId() {
  const timestamp = Date.now();
  const random = Math.floor(Math.random() * 1000);
  return `RPT${timestamp}${random}`;
}

// Generate unique user ID
function generateUserId() {
  const timestamp = Date.now();
  const random = Math.floor(Math.random() * 1000);
  return `USR${timestamp}${random}`;
}

// Save report to localStorage
function saveReport(reportData) {
  let reports = JSON.parse(localStorage.getItem("reports") || "[]");
  reports.push(reportData);
  localStorage.setItem("reports", JSON.stringify(reports));
}

// Get all reports from localStorage
function getReports() {
  return JSON.parse(localStorage.getItem("reports") || "[]");
}

// Save user to localStorage
function saveUser(userData) {
  let users = JSON.parse(localStorage.getItem("users") || "[]");
  // Remove confirmPassword before saving
  const { confirmPassword, agreeTerms, ...userToSave } = userData;
  users.push(userToSave);
  localStorage.setItem("users", JSON.stringify(users));
}

// Get all users from localStorage
function getUsers() {
  return JSON.parse(localStorage.getItem("users") || "[]");
}

// Validate register form
function validateRegisterForm(userData) {
  // Check required fields
  if (
    !userData.fullName ||
    !userData.email ||
    !userData.phone ||
    !userData.address ||
    !userData.nik ||
    !userData.username ||
    !userData.password
  ) {
    showNotification("Semua field harus diisi!", "error");
    return false;
  }

  // Check email format
  if (!validateEmail(userData.email)) {
    showNotification("Format email tidak valid!", "error");
    return false;
  }

  // Check phone format
  if (!validatePhone(userData.phone)) {
    showNotification("Format nomor telepon tidak valid!", "error");
    return false;
  }

  // Check NIK (16 digits)
  if (!/^\d{16}$/.test(userData.nik)) {
    showNotification("NIK harus 16 digit angka!", "error");
    return false;
  }

  // Check password length
  if (userData.password.length < 6) {
    showNotification("Password minimal 6 karakter!", "error");
    return false;
  }
  // Check password confirmation
  if (userData.password !== userData.confirmPassword) {
    showNotification("Konfirmasi password tidak cocok!", "error");
    return false;
  }
  return true;
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
});

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

// Initialize demo data on page load
initializeDemoData();
