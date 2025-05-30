// User Dashboard JavaScript
document.addEventListener("DOMContentLoaded", function () {
  // Check if user is logged in
  checkUserAccess();

  // Initialize dashboard
  loadUserDashboard();

  // Initialize search and filter
  initializeUserFilters();

  // Initialize modal
  initializeUserModal();
});

// Check user access
function checkUserAccess() {
  const userLoggedIn = sessionStorage.getItem("userLoggedIn");
  const userRole = sessionStorage.getItem("userRole");

  if (!userLoggedIn) {
    window.location.href = "../login.html";
    return;
  }

  // Users can access user dashboard regardless of role
  // Admin can also access user dashboard to see user view
}

// Logout function
function logout() {
  // Clear all session storage
  sessionStorage.removeItem("userLoggedIn");
  sessionStorage.removeItem("userRole");
  sessionStorage.removeItem("currentUser");
  sessionStorage.removeItem("userEmail");
  sessionStorage.clear(); // Clear all session data to be safe

  // Show logout message
  if (typeof showNotification === "function") {
    showNotification("Anda telah logout berhasil", "success");
  }

  // Redirect to main page
  window.location.href = "../index.html";
}

// Load user dashboard data
function loadUserDashboard() {
  // Load user statistics
  loadUserStats();

  // Load user reports
  loadUserReports();
}

// Load user statistics
function loadUserStats() {
  fetch("php/user_handler.php?action=get_user_stats")
    .then((response) => response.json())
    .then((data) => {
      if (data.error) {
        console.error("Error:", data.error);
        return;
      }

      // Update user statistics
      document.getElementById("userTotalReports").textContent = data.total || 0;
      document.getElementById("userPendingReports").textContent =
        data.pending || 0;
      document.getElementById("userInProgressReports").textContent =
        data.in_progress || 0;
      document.getElementById("userCompletedReports").textContent =
        data.completed || 0;
    })
    .catch((error) => {
      console.error("Error loading user stats:", error);
      // Fallback to localStorage
      loadUserStatsFromStorage();
    });
}

// Fallback function using localStorage
function loadUserStatsFromStorage() {
  const userEmail = sessionStorage.getItem("userEmail");
  const reports = getUserReports(userEmail);

  document.getElementById("userTotalReports").textContent = reports.length;
  document.getElementById("userPendingReports").textContent = reports.filter(
    (r) => r.status === "pending"
  ).length;
  document.getElementById("userInProgressReports").textContent = reports.filter(
    (r) => r.status === "in_progress"
  ).length;
  document.getElementById("userCompletedReports").textContent = reports.filter(
    (r) => r.status === "completed"
  ).length;
}

// Load user reports
function loadUserReports() {
  const statusFilter = document.getElementById("statusFilterUser").value;
  const searchTerm = document.getElementById("searchInput").value;

  const params = new URLSearchParams({
    action: "get_user_reports",
  });

  if (statusFilter) params.append("status", statusFilter);
  if (searchTerm) params.append("search", searchTerm);

  fetch(`php/user_handler.php?${params}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.error) {
        console.error("Error:", data.error);
        return;
      }

      displayUserReports(data.reports || []);
    })
    .catch((error) => {
      console.error("Error loading user reports:", error);
      // Fallback to localStorage
      const userEmail = sessionStorage.getItem("userEmail");
      const reports = getUserReports(userEmail);
      const filteredReports = filterUserReports(
        reports,
        statusFilter,
        searchTerm
      );
      displayUserReports(filteredReports);
    });
}

// Get user reports from localStorage (fallback)
function getUserReports(userEmail) {
  const allReports = JSON.parse(localStorage.getItem("reports") || "[]");
  return allReports.filter((report) => report.email === userEmail);
}

// Filter user reports
function filterUserReports(reports, statusFilter, searchTerm) {
  let filtered = reports;

  if (statusFilter) {
    filtered = filtered.filter((report) => report.status === statusFilter);
  }

  if (searchTerm) {
    const term = searchTerm.toLowerCase();
    filtered = filtered.filter(
      (report) =>
        report.judul.toLowerCase().includes(term) ||
        report.deskripsi.toLowerCase().includes(term) ||
        report.lokasi.toLowerCase().includes(term)
    );
  }

  return filtered;
}

// Display user reports
function displayUserReports(reports) {
  const container = document.getElementById("userReportsContainer");

  if (!container) {
    // If container doesn't exist, create it
    const reportsSection = document.querySelector(".user-reports-section");
    const newContainer = document.createElement("div");
    newContainer.id = "userReportsContainer";
    newContainer.className = "reports-grid";
    reportsSection.appendChild(newContainer);
  }

  const reportsContainer = document.getElementById("userReportsContainer");
  reportsContainer.innerHTML = "";

  if (reports.length === 0) {
    reportsContainer.innerHTML = `
      <div class="no-reports">
        <i class="fas fa-inbox"></i>
        <h3>Belum ada laporan</h3>
        <p>Anda belum membuat laporan apapun.</p>
        <a href="../index.html#laporan" class="btn btn-primary">Buat Laporan Pertama</a>
      </div>
    `;
    return;
  }

  reports.forEach((report) => {
    const reportCard = createReportCard(report);
    reportsContainer.appendChild(reportCard);
  });
}

// Create report card
function createReportCard(report) {
  const card = document.createElement("div");
  card.className = "report-card";

  card.innerHTML = `
    <div class="report-header">
      <h3>${report.judul}</h3>
      <span class="status-badge status-${report.status}">
        ${getStatusMessage(report.status)}
      </span>
    </div>
    
    <div class="report-body">
      <div class="report-meta">
        <div class="meta-item">
          <i class="fas fa-tag"></i>
          <span>${capitalizeFirst(report.kategori)}</span>
        </div>
        <div class="meta-item">
          <i class="fas fa-map-marker-alt"></i>
          <span>${report.lokasi}</span>
        </div>
        <div class="meta-item">
          <i class="fas fa-calendar"></i>
          <span>${formatDate(report.tanggal)}</span>
        </div>
      </div>
      
      <div class="report-description">
        ${
          report.deskripsi.length > 100
            ? report.deskripsi.substring(0, 100) + "..."
            : report.deskripsi
        }
      </div>
    </div>
    
    <div class="report-footer">
      <button onclick="viewUserReport('${report.id}')" class="btn btn-primary">
        <i class="fas fa-eye"></i> Detail
      </button>
      ${
        report.status === "pending"
          ? `<button onclick="deleteUserReport('${report.id}')" class="btn btn-danger">
             <i class="fas fa-trash"></i> Hapus
           </button>`
          : ""
      }
    </div>
  `;

  return card;
}

// Initialize user filters
function initializeUserFilters() {
  const statusFilter = document.getElementById("statusFilterUser");
  const searchInput = document.getElementById("searchInput");

  if (statusFilter) {
    statusFilter.addEventListener("change", loadUserReports);
  }

  if (searchInput) {
    // Debounce search input
    let searchTimeout;
    searchInput.addEventListener("input", function () {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        loadUserReports();
      }, 500);
    });
  }
}

// View user report details
function viewUserReport(reportId) {
  fetch(`php/user_handler.php?action=get_report_detail&reportId=${reportId}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.error) {
        console.error("Error:", data.error);
        return;
      }

      showUserReportModal(data.report, data.comments || []);
    })
    .catch((error) => {
      console.error("Error loading report details:", error);
      // Fallback to localStorage
      const allReports = JSON.parse(localStorage.getItem("reports") || "[]");
      const report = allReports.find((r) => r.id === reportId);
      if (report) {
        showUserReportModal(report, []);
      }
    });
}

// Show user report modal
function showUserReportModal(report, comments) {
  const modal = document.getElementById("userReportModal");

  if (!modal) {
    createUserReportModal();
  }

  const modalContent = document.getElementById("userReportDetails");
  modalContent.innerHTML = `
    <div class="user-report-detail">
      <div class="report-status-header">
        <h3>${report.judul}</h3>
        <span class="status-badge status-${report.status}">
          ${getStatusMessage(report.status)}
        </span>
      </div>
      
      <div class="detail-grid">
        <div class="detail-item">
          <label><i class="fas fa-hashtag"></i> ID Laporan</label>
          <span>${report.id}</span>
        </div>
        <div class="detail-item">
          <label><i class="fas fa-tag"></i> Kategori</label>
          <span>${capitalizeFirst(report.kategori)}</span>
        </div>
        <div class="detail-item">
          <label><i class="fas fa-map-marker-alt"></i> Lokasi</label>
          <span>${report.lokasi}</span>
        </div>
        <div class="detail-item">
          <label><i class="fas fa-calendar"></i> Tanggal</label>
          <span>${formatDate(report.tanggal)}</span>
        </div>
      </div>
      
      <div class="description-section">
        <label><i class="fas fa-file-alt"></i> Deskripsi Laporan</label>
        <div class="description-content">
          ${report.deskripsi}
        </div>
      </div>
      
      ${
        comments.length > 0
          ? `
        <div class="comments-section">
          <h4><i class="fas fa-comments"></i> Tanggapan</h4>
          ${comments
            .map(
              (comment) => `
            <div class="comment-item">
              <div class="comment-header">
                <strong>${comment.author || "Admin"}</strong>
                <span class="comment-date">${formatDate(
                  comment.created_at
                )}</span>
              </div>
              <div class="comment-content">${comment.content}</div>
            </div>
          `
            )
            .join("")}
        </div>
      `
          : ""
      }
    </div>
  `;

  document.getElementById("userReportModal").style.display = "block";
}

// Create user report modal if it doesn't exist
function createUserReportModal() {
  const modal = document.createElement("div");
  modal.id = "userReportModal";
  modal.className = "modal";
  modal.innerHTML = `
    <div class="modal-content">
      <span class="close">&times;</span>
      <h2>Detail Laporan</h2>
      <div id="userReportDetails"></div>
    </div>
  `;
  document.body.appendChild(modal);
}

// Delete user report (only pending reports)
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
        showNotification("Laporan berhasil dihapus", "success");
        loadUserDashboard(); // Reload dashboard
      } else {
        throw new Error(data.error || "Failed to delete report");
      }
    })
    .catch((error) => {
      console.error("Error deleting report:", error);
      // Fallback to localStorage
      deleteUserReportFromStorage(reportId);
    });
}

// Fallback delete function using localStorage
function deleteUserReportFromStorage(reportId) {
  const reports = JSON.parse(localStorage.getItem("reports") || "[]");
  const reportIndex = reports.findIndex((r) => r.id === reportId);

  if (reportIndex !== -1 && reports[reportIndex].status === "pending") {
    reports.splice(reportIndex, 1);
    localStorage.setItem("reports", JSON.stringify(reports));
    showNotification("Laporan berhasil dihapus", "success");
    loadUserDashboard();
  } else {
    showNotification("Laporan tidak dapat dihapus", "error");
  }
}

// Initialize user modal
function initializeUserModal() {
  // Modal will be created dynamically when needed
  document.addEventListener("click", function (e) {
    if (e.target.classList.contains("close")) {
      const modal = e.target.closest(".modal");
      if (modal) {
        modal.style.display = "none";
      }
    }
  });

  window.addEventListener("click", function (e) {
    if (e.target.classList.contains("modal")) {
      e.target.style.display = "none";
    }
  });
}

// Get status message
function getStatusMessage(status) {
  const statusMap = {
    pending: "Menunggu",
    in_progress: "Diproses",
    completed: "Selesai",
    rejected: "Ditolak",
  };
  return statusMap[status] || status;
}

// Utility functions
function capitalizeFirst(str) {
  return str.charAt(0).toUpperCase() + str.slice(1);
}

function formatDate(dateString) {
  const options = { year: "numeric", month: "short", day: "numeric" };
  return new Date(dateString).toLocaleDateString("id-ID", options);
}

function showNotification(message, type = "info") {
  // Create notification element
  const notification = document.createElement("div");
  notification.className = `notification notification-${type}`;
  notification.textContent = message;
  notification.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 1rem 1.5rem;
    border-radius: 5px;
    color: white;
    font-weight: 500;
    z-index: 1000;
    animation: slideIn 0.3s ease;
  `;

  // Set background color based on type
  const colors = {
    success: "#28a745",
    error: "#dc3545",
    warning: "#ffc107",
    info: "#17a2b8",
  };
  notification.style.backgroundColor = colors[type] || colors.info;

  // Add to document
  document.body.appendChild(notification);

  // Remove after 3 seconds
  setTimeout(() => {
    notification.style.animation = "slideOut 0.3s ease";
    setTimeout(() => {
      if (document.body.contains(notification)) {
        document.body.removeChild(notification);
      }
    }, 300);
  }, 3000);
}
