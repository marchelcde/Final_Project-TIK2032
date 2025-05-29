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

// Load dashboard data
function loadDashboardData() {
  fetch("php/admin_handler.php?action=get_dashboard_stats")
    .then((response) => response.json())
    .then((data) => {
      if (data.error) {
        console.error("Error:", data.error);
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
      // Fallback to localStorage for demo
      loadDashboardDataFromStorage();
    });

  // Load recent reports
  loadRecentReports();
}

// Fallback function using localStorage
function loadDashboardDataFromStorage() {
  const reports = getReports();

  // Update statistics
  document.getElementById("totalReports").textContent = reports.length;
  document.getElementById("pendingReports").textContent = reports.filter(
    (r) => r.status === "pending"
  ).length;
  document.getElementById("completedReports").textContent = reports.filter(
    (r) => r.status === "completed"
  ).length;
  document.getElementById("rejectedReports").textContent = reports.filter(
    (r) => r.status === "rejected"
  ).length;
}

// Load recent reports
function loadRecentReports() {
  fetch("php/admin_handler.php?action=get_recent_reports&limit=5")
    .then((response) => response.json())
    .then((data) => {
      if (data.error) {
        console.error("Error:", data.error);
        return;
      }

      displayRecentReports(data.reports || []);
    })
    .catch((error) => {
      console.error("Error loading recent reports:", error);
      // Fallback to localStorage
      const reports = getReports();
      const recentReports = reports.slice(-5).reverse();
      displayRecentReports(recentReports);
    });
}

// Display recent reports in table
function displayRecentReports(reports) {
  const tbody = document.querySelector("#recentReportsTable tbody");
  tbody.innerHTML = "";

  reports.forEach((report) => {
    const row = document.createElement("tr");
    row.innerHTML = `
      <td>${report.id}</td>
      <td>${report.judul}</td>
      <td>${capitalizeFirst(report.kategori)}</td>
      <td><span class="status-badge status-${report.status}">${getStatusText(
      report.status
    )}</span></td>
      <td>${formatDate(report.tanggal)}</td>
    `;
    tbody.appendChild(row);
  });
}

// Load reports table
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
        console.error("Error:", data.error);
        return;
      }

      displayReportsTable(data.reports || []);
    })
    .catch((error) => {
      console.error("Error loading reports:", error);
      // Fallback to localStorage
      const reports = getReports();
      displayReportsTable(reports);
    });
}

// Display reports in table
function displayReportsTable(reports) {
  const tbody = document.querySelector("#reportsTable tbody");
  tbody.innerHTML = "";

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
      <td>${formatDate(report.tanggal)}</td>
      <td>
        <button onclick="viewReport('${
          report.id
        }')" class="btn btn-primary btn-sm">
          Detail
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

// View report details
function viewReport(reportId) {
  const reports = getReports();
  const report = reports.find((r) => r.id === reportId);

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
        <div><strong>Tanggal:</strong> ${formatDate(report.tanggal)}</div>
      </div>
      <div style="margin: 1rem 0;">
        <strong>Deskripsi:</strong>
        <p style="margin-top: 0.5rem; padding: 1rem; background: #f8f9fa; border-radius: 5px;">${
          report.deskripsi
        }</p>
      </div>
    </div>
  `;

  // Set current status in dropdown
  document.getElementById("statusUpdate").value = report.status;

  // Show modal
  document.getElementById("reportModal").style.display = "block";
}

// Update report status
function updateReportStatus() {
  if (!currentReportId) {
    alert("Tidak ada laporan yang dipilih");
    return;
  }

  const newStatus = document.getElementById("statusUpdate").value;

  // First try API
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
        throw new Error(data.error || "Failed to update status");
      }
    })
    .catch((error) => {
      console.error("Error updating status:", error);
      // Fallback to localStorage
      updateReportStatusLocalStorage(newStatus);
    });
}

// Fallback function using localStorage
function updateReportStatusLocalStorage(newStatus) {
  const reports = getReports();
  const reportIndex = reports.findIndex((r) => r.id === currentReportId);

  if (reportIndex !== -1) {
    reports[reportIndex].status = newStatus;
    localStorage.setItem("reports", JSON.stringify(reports));

    showNotification("Status laporan berhasil diperbarui", "success");
    document.getElementById("reportModal").style.display = "none";
    loadDashboardData();
    loadReportsTable();
  } else {
    showNotification("Laporan tidak ditemukan", "error");
  }
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

// Initialize charts
function initializeCharts() {
  createCategoryChart();
  createStatusChart();
}

// Create category chart
function createCategoryChart() {
  const ctx = document.getElementById("categoryChart").getContext("2d");

  fetch("php/admin_handler.php?action=get_statistics")
    .then((response) => response.json())
    .then((data) => {
      if (data.error) {
        console.error("Error:", data.error);
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
      // Fallback chart
      createFallbackCategoryChart(ctx);
    });
}

// Create status chart
function createStatusChart() {
  const ctx = document.getElementById("statusChart").getContext("2d");

  fetch("php/admin_handler.php?action=get_statistics")
    .then((response) => response.json())
    .then((data) => {
      if (data.error) {
        console.error("Error:", data.error);
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
      // Fallback chart
      createFallbackStatusChart(ctx);
    });
}

// Fallback chart functions
function createFallbackCategoryChart(ctx) {
  const reports = getReports();
  const categories = {};

  reports.forEach((report) => {
    categories[report.kategori] = (categories[report.kategori] || 0) + 1;
  });

  new Chart(ctx, {
    type: "doughnut",
    data: {
      labels: Object.keys(categories).map((cat) => capitalizeFirst(cat)),
      datasets: [
        {
          data: Object.values(categories),
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
}

function createFallbackStatusChart(ctx) {
  const reports = getReports();
  const statuses = {};

  reports.forEach((report) => {
    statuses[report.status] = (statuses[report.status] || 0) + 1;
  });

  new Chart(ctx, {
    type: "bar",
    data: {
      labels: Object.keys(statuses).map((status) => getStatusText(status)),
      datasets: [
        {
          label: "Jumlah Laporan",
          data: Object.values(statuses),
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
}

// Update charts
function updateCharts() {
  // Recreate charts with updated data
  const categoryCanvas = document.getElementById("categoryChart");
  const statusCanvas = document.getElementById("statusChart");

  // Clear existing charts
  if (categoryCanvas) {
    const categoryCtx = categoryCanvas.getContext("2d");
    categoryCtx.clearRect(0, 0, categoryCanvas.width, categoryCanvas.height);
  }

  if (statusCanvas) {
    const statusCtx = statusCanvas.getContext("2d");
    statusCtx.clearRect(0, 0, statusCanvas.width, statusCanvas.height);
  }

  // Recreate charts
  createCategoryChart();
  createStatusChart();
}

// Utility functions
function getReports() {
  const reports = localStorage.getItem("reports");
  return reports ? JSON.parse(reports) : [];
}

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
      document.body.removeChild(notification);
    }, 300);
  }, 3000);
}
