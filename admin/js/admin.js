// Admin Dashboard JavaScript
let currentReportId = null;

document.addEventListener("DOMContentLoaded", function () {
  // Check if user is logged in as admin
  checkAdminAccess();

  // Initialize dashboard
  loadDashboardData();
  loadReportsTable();
  initializeCharts();

  // Initialize modal
  initializeModal();

  // Initialize filters
  initializeFilters();

  // MODIFIED: Attach event listener for Admin Profile Link (instead of onclick in HTML)
  const adminProfileLink = document.getElementById("adminProfileLink");
  if (adminProfileLink) {
    adminProfileLink.addEventListener("click", function (event) {
      event.preventDefault(); // Prevent default link behavior
      showUserProfileModal();
    });
  }

  // Close user profile modal on outside click (for admin profile modal)
  const userProfileModal = document.getElementById("userProfileModal");
  if (userProfileModal) {
    window.addEventListener("click", function (event) {
      const modalContent = userProfileModal.querySelector(".modal-content");
      const adminLinkElement = document.getElementById("adminProfileLink"); // Get the element here

      if (
        event.target == userProfileModal ||
        (modalContent &&
          !modalContent.contains(event.target) &&
          adminLinkElement &&
          !adminLinkElement.contains(event.target))
      ) {
        if (userProfileModal.style.display === "block") {
          closeUserProfileModal();
        }
      }
    });
  }
});

// Check admin access
function checkAdminAccess() {
  const userLoggedIn = sessionStorage.getItem("userLoggedIn");
  const userRole = sessionStorage.getItem("userRole");

  if (!userLoggedIn || userRole !== "admin") {
    window.location.href = "../login.html";
    return;
  }
}

// Logout function
function logout() {
  // Clear all session storage
  sessionStorage.removeItem("userLoggedIn");
  sessionStorage.removeItem("userRole");
  sessionStorage.removeItem("currentUser");
  sessionStorage.clear(); // Clear all session data to be safe

  // Show logout message
  if (typeof showNotification === "function") {
    showNotification("Anda telah logout berhasil", "success");
  }

  // Redirect to main page
  window.location.href = "../index.html";
}

// Show specific section
function showSection(sectionId) {
  // Hide all sections
  const sections = document.querySelectorAll(".content-section");
  sections.forEach((section) => section.classList.remove("active"));

  // Show selected section
  const targetSection = document.getElementById(sectionId);
  if (targetSection) {
    targetSection.classList.add("active");
  }

  // Update sidebar active state
  const sidebarLinks = document.querySelectorAll(".sidebar-link");
  sidebarLinks.forEach((link) => link.classList.remove("active"));

  const activeLink = document.querySelector(
    `[onclick="showSection('${sectionId}')"]`
  );
  if (activeLink) {
    activeLink.classList.add("active");
  }

  // Refresh data for specific sections
  if (sectionId === "dashboard") {
    loadDashboardData();
  } else if (sectionId === "reports") {
    loadReportsTable();
  } else if (sectionId === "statistics") {
    updateCharts();
  }
}

// Load dashboard data (MODIFIED: Removed localStorage fallback)
function loadDashboardData() {
  fetch("php/admin_handler.php?action=get_dashboard_stats")
    .then((response) => response.json())
    .then((data) => {
      if (data.error) {
        console.error("Error fetching dashboard stats:", data.error);
        document.getElementById("totalReports").textContent = "Error";
        document.getElementById("pendingReports").textContent = "Error";
        document.getElementById("completedReports").textContent = "Error";
        document.getElementById("rejectedReports").textContent = "Error";
        return;
      }

      // Update statistics
      document.getElementById("totalReports").textContent = data.total || 0;
      document.getElementById("pendingReports").textContent = data.pending || 0;
      document.getElementById("completedReports").textContent =
        data.completed || 0;
      document.getElementById("rejectedReports").textContent =
        data.rejected || 0;
    })
    .catch((error) => {
      console.error("Error loading dashboard data:", error);
      document.getElementById("totalReports").textContent = "Error";
      document.getElementById("pendingReports").textContent = "Error";
      document.getElementById("completedReports").textContent = "Error";
      document.getElementById("rejectedReports").textContent = "Error";
    });

  // Load recent reports
  loadRecentReports();
}

// Load recent reports (MODIFIED: Removed localStorage fallback)
function loadRecentReports() {
  fetch("php/admin_handler.php?action=get_recent_reports&limit=5")
    .then((response) => response.json())
    .then((data) => {
      if (data.error) {
        console.error("Error fetching recent reports:", data.error);
        document.querySelector("#recentReportsTable tbody").innerHTML =
          '<tr><td colspan="5">Gagal memuat laporan terbaru: ' +
          data.error +
          "</td></tr>";
        return;
      }

      displayRecentReports(data.reports || []);
    })
    .catch((error) => {
      console.error("Error loading recent reports:", error);
      document.querySelector("#recentReportsTable tbody").innerHTML =
        '<tr><td colspan="5">Terjadi kesalahan jaringan saat memuat laporan terbaru.</td></tr>';
    });
}

// Display recent reports in table
function displayRecentReports(reports) {
  const tbody = document.querySelector("#recentReportsTable tbody");
  tbody.innerHTML = "";

  if (reports.length === 0) {
    tbody.innerHTML =
      '<tr><td colspan="5">Tidak ada laporan terbaru.</td></tr>';
    return;
  }

  reports.forEach((report) => {
    const row = document.createElement("tr");
    row.innerHTML = `
      <td>${report.id}</td>
      <td>${report.judul}</td>
      <td>${capitalizeFirst(report.kategori)}</td>
      <td><span class="status-badge status-${report.status}">${getStatusText(
      report.status
    )}</span></td>
      <td>${formatDate(report.created_at)}</td>
    `;
    tbody.appendChild(row);
  });
}

// Load reports table (MODIFIED: Removed localStorage fallback)
function loadReportsTable() {
  const statusFilter = document.getElementById("statusFilter").value;
  const categoryFilter = document.getElementById("categoryFilter").value;

  const params = new URLSearchParams({
    action: "get_all_reports",
  });

  if (statusFilter) params.append("status", statusFilter);
  if (categoryFilter) params.append("category", categoryFilter);

  fetch(`php/admin_handler.php?${params}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.error) {
        console.error("Error fetching all reports:", data.error);
        document.querySelector("#reportsTable tbody").innerHTML =
          '<tr><td colspan="7">Gagal memuat daftar laporan: ' +
          data.error +
          "</td></tr>";
        return;
      }

      displayReportsTable(data.reports || []);
    })
    .catch((error) => {
      console.error("Error loading reports:", error);
      document.querySelector("#reportsTable tbody").innerHTML =
        '<tr><td colspan="7">Terjadi kesalahan jaringan saat memuat daftar laporan.</td></tr>';
    });
}

// Display reports in table (MODIFIED: Added Delete button)
function displayReportsTable(reports) {
  const tbody = document.querySelector("#reportsTable tbody");
  tbody.innerHTML = "";

  if (reports.length === 0) {
    tbody.innerHTML =
      '<tr><td colspan="7">Tidak ada laporan yang ditemukan.</td></tr>';
    return;
  }

  reports.forEach((report) => {
    const row = document.createElement("tr");
    row.innerHTML = `
      <td>${report.id}</td>
      <td>${report.nama}</td>
      <td>${report.judul}</td>
      <td>${capitalizeFirst(report.kategori)}</td>
      <td><span class="status-badge status-${report.status}">${getStatusText(
      report.status
    )}</span></td>
      <td>${formatDate(report.created_at)}</td>
      <td>
        <button onclick="viewReport('${
          report.id
        }')" class="btn btn-primary btn-sm">
          Detail
        </button>
        <button onclick="deleteReport('${
          report.id
        }')" class="btn btn-danger btn-sm">
          Hapus
        </button>
      </td>
    `;
    tbody.appendChild(row);
  });
}

// Initialize filters
function initializeFilters() {
  document
    .getElementById("statusFilter")
    .addEventListener("change", loadReportsTable);
  document
    .getElementById("categoryFilter")
    .addEventListener("change", loadReportsTable);
}

// View report details (MODIFIED: Use API fetch, expects base64 for foto_bukti)
function viewReport(reportId) {
  fetch(`php/admin_handler.php?action=get_report_detail&id=${reportId}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.error) {
        console.error("Error fetching report detail:", data.error);
        document.getElementById("reportDetails").innerHTML =
          "<p>Gagal memuat detail laporan: " + data.error + "</p>";
        return;
      }

      const report = data.data; // Assuming data.data holds the report object

      if (!report) {
        alert("Laporan tidak ditemukan");
        return;
      }

      currentReportId = reportId;

      // Populate modal with report details
      document.getElementById("reportDetails").innerHTML = `
          <div class="report-detail">
            <div class="detail-grid">
              <div><strong>ID:</strong> ${report.id}</div>
              <div><strong>Nama:</strong> ${report.nama}</div>
              <div><strong>Email:</strong> ${report.email}</div>
              <div><strong>Telepon:</strong> ${report.telepon}</div>
              <div><strong>Status:</strong> <span class="status-badge status-${
                report.status
              }">${getStatusText(report.status)}</span></div>
              <div><strong>Kategori:</strong> ${capitalizeFirst(
                report.kategori
              )}</div>
              <div><strong>Lokasi:</strong> ${report.lokasi}</div>
              <div><strong>Tanggal:</strong> ${formatDate(
                report.created_at
              )}</div>
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
          </div>
        `;
      // Populate Admin Feedback textarea
      document.getElementById("adminFeedbackText").value =
        report.feedback_admin || "";

      // Set current status in dropdown
      document.getElementById("statusUpdate").value = report.status;

      // Show modal
      document.getElementById("reportModal").style.display = "block";
    })
    .catch((error) => {
      console.error("Error loading report detail:", error);
      document.getElementById("reportDetails").innerHTML =
        "<p>Terjadi kesalahan jaringan saat memuat detail laporan.</p>";
    });
}

// Update report status (MODIFIED: Removed localStorage fallback)
function updateReportStatus() {
  if (!currentReportId) {
    alert("Tidak ada laporan yang dipilih");
    return;
  }

  const newStatus = document.getElementById("statusUpdate").value;

  fetch("php/admin_handler.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      action: "update_report_status",
      reportId: currentReportId,
      status: newStatus,
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showNotification("Status laporan berhasil diperbarui", "success");
        document.getElementById("reportModal").style.display = "none";
        loadDashboardData();
        loadReportsTable();
      } else {
        showNotification(data.error || "Gagal memperbarui status", "error");
      }
    })
    .catch((error) => {
      console.error("Error updating status:", error);
      showNotification(
        "Terjadi kesalahan jaringan saat memperbarui status",
        "error"
      );
    });
}

// NEW: Function to delete a report (for admin)
function deleteReport(reportId) {
  if (
    !confirm(
      "Apakah Anda yakin ingin menghapus laporan ini? Ini akan menghapus semua data terkait laporan ini secara permanen."
    )
  ) {
    return;
  }

  fetch("php/admin_handler.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: `action=delete_report&reportId=${reportId}`,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showNotification(data.message, "success");
        loadReportsTable(); // Reload reports after deletion
        loadDashboardData(); // Update dashboard stats
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

// NEW: Function to add/update admin feedback
function addAdminFeedback() {
  if (!currentReportId) {
    showNotification(
      "Tidak ada laporan yang dipilih untuk ditambahkan feedback.",
      "error"
    );
    return;
  }
  const feedbackText = document.getElementById("adminFeedbackText").value;

  fetch("php/admin_handler.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      action: "add_admin_feedback",
      reportId: currentReportId,
      feedback: feedbackText,
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showNotification(data.message, "success");
        // Optionally, refresh just this report's detail or the whole table
        viewReport(currentReportId); // Reload the detail to show updated feedback
        loadReportsTable(); // Refresh table in case feedback causes changes elsewhere
      } else {
        showNotification(data.error, "error");
      }
    })
    .catch((error) => {
      console.error("Error adding feedback:", error);
      showNotification(
        "Terjadi kesalahan jaringan saat menambahkan feedback.",
        "error"
      );
    });
}

// Initialize modal
function initializeModal() {
  const modal = document.getElementById("reportModal");
  const closeBtn = document.getElementsByClassName("close")[0];

  closeBtn.onclick = function () {
    modal.style.display = "none";
  };

  window.onclick = function (event) {
    if (event.target == modal) {
      modal.style.display = "none";
    }
  };
}

// Initialize charts (MODIFIED: Removed localStorage fallback in create functions)
function initializeCharts() {
  createCategoryChart();
  createStatusChart();
}

// Create category chart (MODIFIED: Removed localStorage fallback)
function createCategoryChart() {
  const ctx = document.getElementById("categoryChart");
  if (!ctx) return;
  const chartInstance = Chart.getChart(ctx);
  if (chartInstance) {
    chartInstance.destroy();
  }

  fetch("php/admin_handler.php?action=get_statistics")
    .then((response) => response.json())
    .then((data) => {
      if (data.error) {
        console.error("Error fetching category statistics:", data.error);
        return;
      }

      const categoryData = data.categoryStats || [];

      new Chart(ctx, {
        type: "doughnut",
        data: {
          labels: categoryData.map((item) => capitalizeFirst(item.kategori)),
          datasets: [
            {
              data: categoryData.map((item) => item.count),
              backgroundColor: [
                "#3498db",
                "#e74c3c",
                "#f39c12",
                "#2ecc71",
                "#9b59b6",
              ],
            },
          ],
        },
        options: {
          responsive: true,
          plugins: {
            legend: {
              position: "bottom",
            },
          },
        },
      });
    })
    .catch((error) => {
      console.error("Error loading category chart:", error);
    });
}

// Create status chart (MODIFIED: Removed localStorage fallback)
function createStatusChart() {
  const ctx = document.getElementById("statusChart");
  if (!ctx) return;
  const chartInstance = Chart.getChart(ctx);
  if (chartInstance) {
    chartInstance.destroy();
  }

  fetch("php/admin_handler.php?action=get_statistics")
    .then((response) => response.json())
    .then((data) => {
      if (data.error) {
        console.error("Error fetching status statistics:", data.error);
        return;
      }

      const statusData = data.statusStats || [];

      new Chart(ctx, {
        type: "bar",
        data: {
          labels: statusData.map((item) => getStatusText(item.status)),
          datasets: [
            {
              label: "Jumlah Laporan",
              data: statusData.map((item) => item.count),
              backgroundColor: "#3498db",
            },
          ],
        },
        options: {
          responsive: true,
          plugins: {
            legend: {
              display: false,
            },
          },
          scales: {
            y: {
              beginAtZero: true,
            },
          },
        },
      });
    })
    .catch((error) => {
      console.error("Error loading status chart:", error);
    });
}

// Update charts
function updateCharts() {
  createCategoryChart();
  createStatusChart();
}

// Utility functions (Removed getReports and initializeDemoData as they are handled by shared/js/script.js or not needed here)

function getStatusText(status) {
  const statusMap = {
    pending: "Menunggu",
    in_progress: "Diproses",
    completed: "Selesai",
    rejected: "Ditolak",
  };
  return statusMap[status] || status;
}

function capitalizeFirst(str) {
  return str.charAt(0).toUpperCase() + str.slice(1);
}

function formatDate(dateString) {
  const options = { year: "numeric", month: "short", day: "numeric" };
  return new Date(dateString).toLocaleDateString("id-ID", options);
}

// showNotification function is assumed to be available from shared/js/script.js
// If not, ensure shared/js/script.js is loaded first, or redefine it here.
