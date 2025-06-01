// user-dashboard.js
// This file will now only contain logic specific to the user dashboard,
// including the user profile modal functions, user dashboard stats loading,
// and user's report list loading with search/filter.
// General UI logic (like hamburger menu, smooth scrolling) is in shared/js/script.js

document.addEventListener("DOMContentLoaded", function () {
  const reportForm = document.getElementById("reportForm");
  if (reportForm) {
    reportForm.addEventListener("submit", handleReportSubmission);
  }

  const loginForm = document.getElementById("loginForm");
  if (loginForm) {
    loginForm.addEventListener("submit", handleLogin);
  }

  const registerForm = document.getElementById("registerForm");
  if (registerForm) {
    registerForm.addEventListener("submit", handleRegister);
  }

  // Close user profile modal on outside click
  const userProfileModal = document.getElementById("userProfileModal");
  if (userProfileModal) {
    window.addEventListener("click", function (event) {
      // Check if the click is outside the modal content and not on the user link itself
      const modalContent = userProfileModal.querySelector(".modal-content");
      const userLink = document.querySelector(
        'a[onclick="showUserProfileModal(); return false;"]'
      ); // The 'User' link

      if (
        event.target == userProfileModal ||
        (modalContent &&
          !modalContent.contains(event.target) &&
          !userLink.contains(event.target))
      ) {
        // Only close if modal is currently visible
        if (userProfileModal.style.display === "block") {
          closeUserProfileModal();
        }
      }
    });
  }

  // Load dashboard data when the user dashboard page loads
  loadUserDashboardData();
  // NEW: Load user's reports when the page loads
  loadUserReports();

  // NEW: Add event listeners for search and filter
  const searchInput = document.getElementById("searchInput");
  if (searchInput) {
    searchInput.addEventListener("keyup", loadUserReports);
  }
  const statusFilterUser = document.getElementById("statusFilterUser");
  if (statusFilterUser) {
    statusFilterUser.addEventListener("change", loadUserReports);
  }
});

// Function to load user dashboard statistics
function loadUserDashboardData() {
  fetch("php/user_handler.php?action=get_user_stats")
    .then((response) => response.json())
    .then((data) => {
      if (data.error) {
        console.error("Error fetching user stats:", data.error);
        // Optionally set error messages for all stat cards
        document.getElementById("userTotalReports").textContent = "Error";
        document.getElementById("userPendingReports").textContent = "Error";
        document.getElementById("userInProgressReports").textContent = "Error";
        document.getElementById("userCompletedReports").textContent = "Error";
        document.getElementById("userRejectedReports").textContent = "Error"; // Add this
        return;
      }

      // Update statistics in user-dashboard.html
      document.getElementById("userTotalReports").textContent = data.total || 0;
      document.getElementById("userPendingReports").textContent =
        data.pending || 0;
      document.getElementById("userInProgressReports").textContent =
        data.in_progress || 0;
      document.getElementById("userCompletedReports").textContent =
        data.completed || 0;
      // ADD THIS LINE TO UPDATE THE REJECTED REPORTS COUNT
      document.getElementById("userRejectedReports").textContent =
        data.rejected || 0;
    })
    .catch((error) => {
      console.error("Error loading user dashboard data:", error);
      // Ensure error messages are displayed if fetch fails
      document.getElementById("userTotalReports").textContent = "Error";
      document.getElementById("userPendingReports").textContent = "Error";
      document.getElementById("userInProgressReports").textContent = "Error";
      document.getElementById("userCompletedReports").textContent = "Error";
      document.getElementById("userRejectedReports").textContent = "Error"; // Add this
    });
}
// NEW: Function to load user's reports
function loadUserReports() {
  const searchInput = document.getElementById("searchInput");
  const statusFilterUser = document.getElementById("statusFilterUser");
  const userReportsGrid = document.getElementById("userReportsGrid");

  const searchValue = searchInput ? searchInput.value : "";
  const statusFilterValue = statusFilterUser ? statusFilterUser.value : "";

  const params = new URLSearchParams({
    action: "get_user_reports",
  });
  if (searchValue) {
    params.append("search", searchValue);
  }
  if (statusFilterValue) {
    params.append("status", statusFilterValue);
  }

  fetch(`php/user_handler.php?${params.toString()}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.error) {
        console.error("Error fetching user reports:", data.error);
        userReportsGrid.innerHTML =
          '<p class="error-message">Gagal memuat laporan: ' +
          data.error +
          "</p>";
        return;
      }

      userReportsGrid.innerHTML = ""; // Clear previous reports

      if (data.reports && data.reports.length > 0) {
        data.reports.forEach((report) => {
          const reportCard = document.createElement("div");
          reportCard.classList.add("report-card");
          reportCard.innerHTML = `
                        <h3>${report.judul}</h3>
                        <p><strong>ID Laporan:</strong> ${report.id}</p>
                        <p><strong>Kategori:</strong> ${
                          report.category_text
                        }</p>
                        <p><strong>Lokasi:</strong> ${report.lokasi}</p>
                        <p><strong>Status:</strong> <span class="status-badge status-${
                          report.status
                        }">${report.status_text}</span></p>
                        <p><strong>Tanggal:</strong> ${
                          report.formatted_date
                        }</p>
                        ${
                          report.feedback_admin
                            ? `<p><strong>Feedback Admin:</strong> ${report.feedback_admin}</p>`
                            : ""
                        }
                        ${
                          report.foto_bukti_base64
                            ? `<img src="data:image/jpeg;base64,${report.foto_bukti_base64}" alt="Foto Bukti" class="report-image" style="max-width: 100%; height: auto; margin-top: 10px;">`
                            : ""
                        }
                        <div class="report-actions">
                            <button class="btn btn-primary btn-sm" onclick="viewUserReportDetail('${
                              report.id
                            }')">Lihat Detail</button>
                            ${
                              report.status === "pending"
                                ? `<button class="btn btn-danger btn-sm" onclick="deleteUserReport('${report.id}')">Hapus</button>`
                                : ""
                            }
                        </div>
                    `;
          userReportsGrid.appendChild(reportCard);
        });
      } else {
        userReportsGrid.innerHTML =
          '<p class="no-reports-message">Tidak ada laporan yang ditemukan.</p>';
      }
    })
    .catch((error) => {
      console.error("Error loading user reports:", error);
      userReportsGrid.innerHTML =
        '<p class="error-message">Terjadi kesalahan jaringan saat memuat laporan.</p>';
    });
}

// NEW: Function to view a single user report detail (for the modal)
function viewUserReportDetail(reportId) {
  const userReportModal = document.getElementById("userReportModal");
  const userReportDetails = document.getElementById("userReportDetails");

  fetch(`php/user_handler.php?action=get_report_detail&reportId=${reportId}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.error) {
        console.error("Error fetching report detail:", data.error);
        userReportDetails.innerHTML =
          '<p class="error-message">Gagal memuat detail laporan: ' +
          data.error +
          "</p>";
        return;
      }

      const report = data.report;
      const comments = data.comments;

      let commentsHtml = "";
      if (comments && comments.length > 0) {
        commentsHtml = '<h4>Komentar:</h4><div class="report-comments">';
        comments.forEach((comment) => {
          commentsHtml += `<p><strong>${comment.formatted_date}:</strong> ${comment.comment}</p>`;
        });
        commentsHtml += "</div>";
      }

      userReportDetails.innerHTML = `
                <div class="report-detail">
                    <div class="detail-grid">
                        <div><strong>ID Laporan:</strong> ${report.id}</div>
                        <div><strong>Judul:</strong> ${report.judul}</div>
                        <div><strong>Kategori:</strong> ${
                          report.category_text
                        }</div>
                        <div><strong>Lokasi:</strong> ${report.lokasi}</div>
                        <div><strong>Status:</strong> <span class="status-badge status-${
                          report.status
                        }">${report.status_text}</span></div>
                        <div><strong>Tanggal Laporan:</strong> ${
                          report.formatted_date
                        }</div>
                    </div>
                    <div style="margin: 1rem 0;">
                        <strong>Deskripsi:</strong>
                        <p style="margin-top: 0.5rem; padding: 1rem; background: #f8f9fa; border-radius: 5px;">${
                          report.deskripsi
                        }</p>
                    </div>
                    ${
                      report.foto_bukti_base64
                        ? `<div style="margin: 1rem 0;"><p><strong>Foto Bukti:</strong></p><img src="data:image/jpeg;base64,${report.foto_bukti_base64}" alt="Foto Bukti" class="report-image" style="max-width: 100%; height: auto; margin-top: 10px;"></div>`
                        : ""
                    }
                    ${
                      report.feedback_admin
                        ? `<div style="margin: 1rem 0;"><p><strong>Feedback Admin:</strong></p><p style="margin-top: 0.5rem; padding: 1rem; background: #e9ecef; border-radius: 5px;">${report.feedback_admin}</p></div>`
                        : ""
                    }
                    ${commentsHtml}
                </div>
            `;
      userReportModal.style.display = "block";
    })
    .catch((error) => {
      console.error("Error loading report detail:", error);
      userReportDetails.innerHTML =
        '<p class="error-message">Terjadi kesalahan jaringan saat memuat detail laporan.</p>';
    });
}

// NEW: Function to delete a user report
function deleteUserReport(reportId) {
  if (!confirm("Apakah Anda yakin ingin menghapus laporan ini?")) {
    return;
  }

  fetch("php/user_handler.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: `action=delete_user_report&reportId=${reportId}`,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showNotification(data.message, "success");
        loadUserReports(); // Reload reports after deletion
        loadUserDashboardData(); // Update stats
      } else {
        showNotification(data.error, "error");
      }
    })
    .catch((error) => {
      console.error("Error deleting report:", error);
      showNotification(
        "Terjadi kesalahan jaringan saat menghapus laporan.",
        "error"
      );
    });
}

// Function to show/toggle the user profile modal
function showUserProfileModal() {
  const userProfileModal = document.getElementById("userProfileModal");
  const profileFullName = document.getElementById("profileFullName");
  const profileUsername = document.getElementById("profileUsername");
  const profileEmail = document.getElementById("profileEmail");

  // Toggle display based on current state
  if (userProfileModal.style.display === "block") {
    userProfileModal.style.display = "none"; // Hide if already visible
    return; // Exit function
  }

  // If not visible, populate and show it
  const fullName = sessionStorage.getItem("userName") || "N/A";
  const email = sessionStorage.getItem("userEmail") || "N/A";

  let username = "N/A";
  const currentUserString = sessionStorage.getItem("currentUser");
  if (currentUserString) {
    try {
      const currentUser = JSON.parse(currentUserString);
      username = currentUser.username || "N/A";
    } catch (e) {
      console.error("Error parsing currentUser from sessionStorage:", e);
    }
  }

  profileFullName.textContent = fullName;
  profileUsername.textContent = username;
  profileEmail.textContent = email;

  userProfileModal.style.display = "block"; // Show the modal
}

// Function to close the user profile modal
function closeUserProfileModal() {
  const userProfileModal = document.getElementById("userProfileModal");
  userProfileModal.style.display = "none";
}

// The following functions are still needed if user-dashboard.js specifically calls them,
// but they are also in shared/js/script.js. If user-dashboard.js doesn't
// need these functions directly, they can be removed to reduce redundancy.
// For now, keeping them as they were in the provided user-dashboard.js.

// Handle Report Form Submission (AJAX to PHP handler)
// Note: This is also in shared/js/script.js. Consider removing if not directly used here.
function handleReportSubmission(e) {
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
// Note: This is also in shared/js/script.js. Consider removing if not directly used here.
function handleLogin(e) {
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
// Note: This is also in shared/js/script.js. Consider removing if not directly used here.
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

// getReports is needed by initializeDemoData. Keeping it here if initializeDemoData() is kept.
function getReports() {
  try {
    return JSON.parse(localStorage.getItem("reports") || "[]");
  } catch (e) {
    console.error("Error parsing reports from localStorage:", e);
    return [];
  }
}
