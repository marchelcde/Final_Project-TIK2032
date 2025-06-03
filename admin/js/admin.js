let currentReportId = null;

document.addEventListener("DOMContentLoaded", function () {
  if (
    !sessionStorage.getItem("userLoggedIn") ||
    !sessionStorage.getItem("userRole")
  ) {
    console.log("Setting up temporary admin session for testing...");
    sessionStorage.setItem("userLoggedIn", "true");
    sessionStorage.setItem("userRole", "admin");
    sessionStorage.setItem("userName", "Admin Test");
    sessionStorage.setItem("userEmail", "admin@test.com");
    sessionStorage.setItem(
      "currentUser",
      JSON.stringify({
        id: 1,
        username: "admin",
        role: "admin",
        fullName: "Admin Test",
        email: "admin@test.com",
      })
    );
  }
  checkAdminAccess();

  showSection("dashboard");

  loadDashboardData();
  loadReportsTable();
  initializeCharts();
  initializeModal();

  initializeFilters();

  initializeMobileMenu();

  setupModalEventListeners();
  // Store login time for logout tracking
  if (!sessionStorage.getItem("loginTime")) {
    sessionStorage.setItem("loginTime", new Date().toISOString());
  }

  // Load system settings from localStorage if they exist
  const emailNotifications = localStorage.getItem("emailNotifications");
  const autoAssign = localStorage.getItem("autoAssign");

  if (emailNotifications !== null) {
    const emailCheckbox = document.getElementById("emailNotifications");
    if (emailCheckbox) emailCheckbox.checked = emailNotifications === "true";
  }

  if (autoAssign !== null) {
    const autoAssignCheckbox = document.getElementById("autoAssign");
    if (autoAssignCheckbox) autoAssignCheckbox.checked = autoAssign === "true";
  }
});

function checkAdminAccess() {
  const userLoggedIn = sessionStorage.getItem("userLoggedIn");
  const userRole = sessionStorage.getItem("userRole");

  if (!userLoggedIn || userRole !== "admin") {
    window.location.href = "../login.html";
    return;
  }
}

/**
 * Enhanced admin logout function with comprehensive session cleanup
 * Ensures both client-side and server-side session destruction
 * Updated to work with new logout.php implementation
 */
function logout() {
  // Show loading notification
  if (typeof showNotification === "function") {
    showNotification("Sedang logout...", "info");
  }

  console.log("Starting admin logout process...");

  // Store login time for potential logging
  const loginTime =
    sessionStorage.getItem("loginTime") || new Date().toISOString();

  // Clear client-side storage immediately
  const itemsToRemove = [
    "userLoggedIn",
    "userRole",
    "currentUser",
    "userEmail",
    "userId",
    "userName",
    "adminLoggedIn",
    "adminRole",
    "currentAdmin",
    "loginTime",
    "lastActivity",
  ];

  itemsToRemove.forEach((item) => {
    sessionStorage.removeItem(item);
    localStorage.removeItem(item);
  });

  // Clear entire storage to be safe
  sessionStorage.clear();

  // Also clear relevant localStorage items
  localStorage.removeItem("rememberMe");
  localStorage.removeItem("adminPreferences");

  console.log("Client-side storage cleared");

  // Call server-side logout for proper session cleanup
  // Updated to use simplified approach without requiring action parameter
  fetch("php/logout.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
    body: JSON.stringify({
      userType: "admin",
      loginTime: loginTime,
    }),
  })
    .then((response) => {
      console.log("Server logout response received", response.status);
      return response.json();
    })
    .then((data) => {
      console.log("Server logout response:", data);
      if (data.success) {
        console.log("Admin session destroyed successfully on server");
        if (typeof showNotification === "function") {
          showNotification("Logout berhasil.", "success");
        }
        // Redirect after success with slight delay
        setTimeout(() => {
          window.location.href = "../index.php?logout=success&from=admin";
        }, 1000);
      } else {
        console.warn("Failed to destroy admin session on server:", data.error);
        // Still redirect even if server logout failed
        if (typeof showNotification === "function") {
          showNotification("Logout berhasil (client-side).", "info");
        }
        setTimeout(() => {
          window.location.href = "../index.php?logout=partial&from=admin";
        }, 1000);
      }
    })
    .catch((error) => {
      console.error("Error during admin logout:", error);
      // Still redirect even if there's an error - client-side cleanup was successful
      if (typeof showNotification === "function") {
        showNotification("Logout berhasil (offline).", "info");
      }
      setTimeout(() => {
        window.location.href = "../index.php?logout=offline&from=admin";
      }, 1000);
    });
}

function showSection(sectionId) {
  // Hide all sections
  const sections = document.querySelectorAll(".content-section");
  sections.forEach((section) => section.classList.remove("active"));

  // Show selected section
  const targetSection = document.getElementById(sectionId);
  if (targetSection) {
    targetSection.classList.add("active");
  }

  // Update sidebar navigation
  const sidebarLinks = document.querySelectorAll(".sidebar-link");
  sidebarLinks.forEach((link) => link.classList.remove("active"));

  const activeLink = document.querySelector(
    `[onclick="showSection('${sectionId}')"]`
  );
  if (activeLink) {
    activeLink.classList.add("active");
  }

  // Apply special styling for settings section
  const body = document.body;
  if (sectionId === "settings") {
    body.classList.add("settings-active");
  } else {
    body.classList.remove("settings-active");
  }

  // Load section-specific data
  if (sectionId === "dashboard") {
    loadDashboardData();
  } else if (sectionId === "reports") {
    loadReportsTable();
  } else if (sectionId === "statistics") {
    updateCharts();
  } else if (sectionId === "settings") {
    loadSettingsData();
  } else if (sectionId === "users") {
    loadUsersTable();
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
        document.getElementById("inProgressReports").textContent = "Error";
        document.getElementById("completedReports").textContent = "Error";
        return;
      } // Update statistics
      document.getElementById("totalReports").textContent = data.total || 0;
      document.getElementById("pendingReports").textContent = data.pending || 0;
      document.getElementById("inProgressReports").textContent =
        data.in_progress || 0;
      document.getElementById("completedReports").textContent =
        data.completed || 0;
    })
    .catch((error) => {
      console.error("Error loading dashboard data:", error);
      document.getElementById("totalReports").textContent = "Error";
      document.getElementById("pendingReports").textContent = "Error";
      document.getElementById("inProgressReports").textContent = "Error";
      document.getElementById("completedReports").textContent = "Error";
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
          '<tr><td colspan="9">Gagal memuat laporan terbaru: ' +
          data.error +
          "</td></tr>";
        return;
      }

      displayRecentReports(data.reports || []);
    })
    .catch((error) => {
      console.error("Error loading recent reports:", error);
      document.querySelector("#recentReportsTable tbody").innerHTML =
        '<tr><td colspan="9">Terjadi kesalahan jaringan saat memuat laporan terbaru.</td></tr>';
    });
}

// Display recent reports in table
function displayRecentReports(reports) {
  const tbody = document.querySelector("#recentReportsTable tbody");
  tbody.innerHTML = "";

  if (reports.length === 0) {
    tbody.innerHTML =
      '<tr><td colspan="9">Tidak ada laporan terbaru.</td></tr>';
    return;
  }

  reports.forEach((report) => {
    const row = document.createElement("tr");
    row.innerHTML = `
      <td>${report.id}</td>
      <td>${report.nama}</td>
      <td>${report.email || "N/A"}</td>
      <td>${report.judul}</td>
      <td>${capitalizeFirst(report.kategori)}</td>
      <td>${report.lokasi || "N/A"}</td>
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

// Load reports table (MODIFIED: Removed localStorage fallback)
function loadReportsTable() {
  fetch(`php/admin_handler.php?action=get_all_reports`)
    .then((response) => response.json())
    .then((data) => {
      if (data.error) {
        console.error("Error fetching all reports:", data.error);
        document.querySelector("#reportsTable tbody").innerHTML =
          '<tr><td colspan="9">Gagal memuat daftar laporan: ' +
          data.error +
          "</td></tr>";
        return;
      }

      // Apply filters on the client side
      const filteredReports = applyFilters(data.reports || []);
      displayReportsTable(filteredReports);
    })
    .catch((error) => {
      console.error("Error loading reports:", error);
      document.querySelector("#reportsTable tbody").innerHTML =
        '<tr><td colspan="9">Terjadi kesalahan jaringan saat memuat daftar laporan.</td></tr>';
    });
}

// Display reports in table (MODIFIED: Added Delete button and email column)
function displayReportsTable(reports) {
  const tbody = document.querySelector("#reportsTable tbody");
  tbody.innerHTML = "";

  if (reports.length === 0) {
    tbody.innerHTML =
      '<tr><td colspan="9">Tidak ada laporan yang ditemukan.</td></tr>';
    return;
  }

  reports.forEach((report) => {
    const row = document.createElement("tr");
    row.innerHTML = `
      <td>${report.id}</td>
      <td>${report.nama}</td>
      <td>${report.email || "N/A"}</td>
      <td>${report.judul}</td>
      <td>${capitalizeFirst(report.kategori)}</td>
      <td>${report.lokasi || "N/A"}</td>
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
  const statusFilter = document.getElementById("statusFilter");
  const categoryFilter = document.getElementById("categoryFilter");
  const searchInput = document.getElementById("searchInput");

  if (statusFilter) {
    statusFilter.addEventListener("change", loadReportsTable);
  }
  if (categoryFilter) {
    categoryFilter.addEventListener("change", loadReportsTable);
  }
  if (searchInput) {
    searchInput.addEventListener("keyup", searchReports);
  }
}

// Search reports function
function searchReports() {
  loadReportsTable();
}

// Apply filters to reports table
function applyFilters(reports) {
  const statusFilter = document.getElementById("statusFilter");
  const categoryFilter = document.getElementById("categoryFilter");
  const searchInput = document.getElementById("searchInput");

  let filteredReports = reports;

  // Filter by status
  if (statusFilter && statusFilter.value) {
    filteredReports = filteredReports.filter(
      (report) => report.status === statusFilter.value
    );
  }

  // Filter by category
  if (categoryFilter && categoryFilter.value) {
    filteredReports = filteredReports.filter(
      (report) => report.kategori === categoryFilter.value
    );
  }

  // Search filter
  if (searchInput && searchInput.value.trim()) {
    const searchTerm = searchInput.value.toLowerCase();
    filteredReports = filteredReports.filter(
      (report) =>
        (report.nama && report.nama.toLowerCase().includes(searchTerm)) ||
        (report.email && report.email.toLowerCase().includes(searchTerm)) ||
        (report.judul && report.judul.toLowerCase().includes(searchTerm)) ||
        (report.lokasi && report.lokasi.toLowerCase().includes(searchTerm)) ||
        (report.kategori && report.kategori.toLowerCase().includes(searchTerm))
    );
  }

  return filteredReports;
}

// View report details (MODIFIED: Use API fetch, expects base64 for foto_bukti)
// Also handles viewReportDetails for consistency with HTML onclick calls
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

// Update report status and feedback (MODIFIED: Combined status and feedback update)
function updateReportStatus() {
  if (!currentReportId) {
    alert("Tidak ada laporan yang dipilih");
    return;
  }

  const newStatus = document.getElementById("statusUpdate").value;
  const feedbackText = document.getElementById("adminFeedbackText").value;

  fetch("php/admin_handler.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      action: "update_report_status_and_feedback",
      reportId: currentReportId,
      status: newStatus,
      feedback: feedbackText,
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showNotification(
          "Status dan feedback laporan berhasil diperbarui",
          "success"
        );
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
  if (modal) {
    const closeBtn = modal.querySelector(".close");
    if (closeBtn) {
      closeBtn.onclick = function () {
        modal.style.display = "none";
      };
    }
  }

  // Don't override window.onclick here as it's handled in HTML inline script
}

// Initialize charts (MODIFIED: Removed localStorage fallback in create functions)
function initializeCharts() {
  createCategoryChart();
  createStatusChart();
  createTrendChart();
  createDashboardTrendChart();
}

// Update charts
function updateCharts() {
  createCategoryChart();
  createStatusChart();
  // Only create trend chart if statistics section is active
  const statisticsSection = document.getElementById("statistics");
  if (statisticsSection && statisticsSection.classList.contains("active")) {
    createTrendChart();
  }
  createDashboardTrendChart();
}

// Create category chart (FIXED: Proper canvas handling and error checking)
function createCategoryChart() {
  const container = document.querySelector("#categoryChart")?.parentElement;
  if (!container) {
    console.warn("Category chart container not found");
    return;
  }

  // Destroy existing chart
  const existingCtx = document.getElementById("categoryChart");
  if (existingCtx) {
    const chartInstance = Chart.getChart(existingCtx);
    if (chartInstance) {
      chartInstance.destroy();
    }
  }

  // Show loading state
  container.innerHTML =
    '<div class="chart-loading">Memuat data kategori...</div>';

  fetch("php/admin_handler.php?action=get_statistics")
    .then((response) => response.json())
    .then((data) => {
      if (data.error) {
        console.error("Error fetching category statistics:", data.error);
        container.innerHTML = `<div class="chart-error">Error: ${data.error}</div>`;
        return;
      }

      // Restore canvas element
      container.innerHTML = '<canvas id="categoryChart"></canvas>';
      const ctx = document.getElementById("categoryChart");

      const categoryData = data.categoryStats || [];
      if (categoryData.length === 0) {
        container.innerHTML =
          '<div class="chart-error">Belum ada data kategori</div>';
        return;
      }

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
                "#1abc9c",
                "#e67e22",
              ],
              borderWidth: 2,
              borderColor: "#fff",
              hoverBorderWidth: 3,
              hoverOffset: 4,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: "bottom",
              labels: {
                padding: 15,
                usePointStyle: true,
                font: {
                  size: 12,
                },
              },
            },
            tooltip: {
              backgroundColor: "rgba(0,0,0,0.8)",
              titleColor: "#fff",
              bodyColor: "#fff",
              borderColor: "#3498db",
              borderWidth: 1,
            },
          },
        },
      });
    })
    .catch((error) => {
      console.error("Error loading category chart:", error);
      container.innerHTML = `<div class="chart-error">Gagal memuat grafik kategori: ${error.message}</div>`;
    });
}

// Create status chart (FIXED: Proper canvas handling and error checking)
function createStatusChart() {
  const container = document.querySelector("#statusChart")?.parentElement;
  if (!container) {
    console.warn("Status chart container not found");
    return;
  }

  // Destroy existing chart
  const existingCtx = document.getElementById("statusChart");
  if (existingCtx) {
    const chartInstance = Chart.getChart(existingCtx);
    if (chartInstance) {
      chartInstance.destroy();
    }
  }

  // Show loading state
  container.innerHTML =
    '<div class="chart-loading">Memuat data status...</div>';

  fetch("php/admin_handler.php?action=get_statistics")
    .then((response) => response.json())
    .then((data) => {
      if (data.error) {
        console.error("Error fetching status statistics:", data.error);
        container.innerHTML = `<div class="chart-error">Error: ${data.error}</div>`;
        return;
      }

      // Restore canvas element
      container.innerHTML = '<canvas id="statusChart"></canvas>';
      const ctx = document.getElementById("statusChart");

      const statusData = data.statusStats || [];

      if (statusData.length === 0) {
        container.innerHTML =
          '<div class="chart-error">Belum ada data status</div>';
        return;
      } // Define status colors
      const statusColors = {
        pending: "#f39c12",
        in_progress: "#3498db",
        completed: "#2ecc71",
        rejected: "#e74c3c",
      };

      new Chart(ctx, {
        type: "bar",
        data: {
          labels: statusData.map((item) => getStatusText(item.status)),
          datasets: [
            {
              label: "Jumlah Laporan",
              data: statusData.map((item) => item.count),
              backgroundColor: statusData.map(
                (item) => statusColors[item.status] || "#95a5a6"
              ),
              borderColor: statusData.map(
                (item) => statusColors[item.status] || "#7f8c8d"
              ),
              borderWidth: 1,
              borderRadius: 4,
              borderSkipped: false,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false,
            },
            tooltip: {
              backgroundColor: "rgba(0,0,0,0.8)",
              titleColor: "#fff",
              bodyColor: "#fff",
              borderColor: "#3498db",
              borderWidth: 1,
            },
          },
          scales: {
            x: {
              grid: {
                display: false,
              },
              ticks: {
                color: "#666",
                font: {
                  size: 11,
                },
              },
            },
            y: {
              beginAtZero: true,
              ticks: {
                stepSize: 1,
                color: "#666",
              },
              grid: {
                color: "rgba(0,0,0,0.1)",
              },
            },
          },
        },
      });
    })
    .catch((error) => {
      console.error("Error loading status chart:", error);
      if (ctx && ctx.parentElement) {
        ctx.parentElement.innerHTML = `<div class="chart-error">Gagal memuat grafik status: ${error.message}</div>`;
      }
    });
}

// Create trend chart (Statistik Bulanan) - IMPROVED
function createTrendChart() {
  const ctx = document.getElementById("trendChart");
  if (!ctx) {
    console.warn("Trend chart canvas not found");
    return;
  }

  const container = ctx.parentElement;
  if (!container) {
    console.warn("Trend chart parent container not found");
    return;
  }

  // Destroy chart instance if exists
  const chartInstance = Chart.getChart(ctx);
  if (chartInstance) {
    chartInstance.destroy();
  }

  // Show loading state
  container.innerHTML =
    '<div class="chart-loading">Memuat data tren bulanan...</div>';

  fetch("php/admin_handler.php?action=get_statistics")
    .then((response) => response.json())
    .then((data) => {
      console.log("[TrendChart] Data statistik diterima:", data);
      if (data.error) {
        console.error("Error fetching monthly statistics:", data.error);
        container.innerHTML = `<div class="chart-error">Error: ${data.error}</div>`;
        return;
      }

      // Restore canvas element
      container.innerHTML = '<canvas id="trendChart"></canvas>';
      const newCtx = document.getElementById("trendChart");

      if (!newCtx) {
        console.error("Failed to recreate trendChart canvas");
        return;
      }

      const monthlyData = data.monthlyStats || [];
      const currentYear = new Date().getFullYear();
      const monthLabels = [];
      const monthCounts = [];

      for (let i = 1; i <= 12; i++) {
        const monthKey = `${currentYear}-${i.toString().padStart(2, "0")}`;
        const monthName = new Date(currentYear, i - 1, 1).toLocaleDateString(
          "id-ID",
          { month: "long" }
        );
        monthLabels.push(monthName);
        const monthData = monthlyData.find((item) => item.month === monthKey);
        monthCounts.push(monthData ? parseInt(monthData.count) : 0);
      }

      // If all months are 0, show no data message
      if (monthCounts.every((c) => c === 0)) {
        container.innerHTML =
          '<div class="chart-error">Belum ada data laporan tahun ini.</div>';
        console.warn("[TrendChart] Tidak ada data laporan untuk tahun ini.");
        return;
      }

      new Chart(newCtx, {
        type: "line",
        data: {
          labels: monthLabels,
          datasets: [
            {
              label: "Jumlah Laporan",
              data: monthCounts,
              borderColor: "#3498db",
              backgroundColor: "rgba(52,152,219,0.1)",
              borderWidth: 3,
              fill: true,
              tension: 0.4,
              pointBackgroundColor: "#3498db",
              pointBorderColor: "#fff",
              pointBorderWidth: 2,
              pointRadius: 5,
              pointHoverRadius: 8,
              pointHoverBackgroundColor: "#2980b9",
              pointHoverBorderColor: "#fff",
              pointHoverBorderWidth: 3,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: true,
              position: "top",
              labels: {
                usePointStyle: true,
                padding: 20,
                font: {
                  size: 12,
                },
              },
            },
            tooltip: {
              mode: "index",
              intersect: false,
              backgroundColor: "rgba(0,0,0,0.8)",
              titleColor: "#fff",
              bodyColor: "#fff",
              borderColor: "#3498db",
              borderWidth: 1,
              cornerRadius: 6,
              displayColors: false,
            },
          },
          scales: {
            x: {
              display: true,
              title: {
                display: true,
                text: "Bulan",
                color: "#666",
                font: {
                  size: 12,
                  weight: "bold",
                },
              },
              grid: {
                display: false,
              },
              ticks: {
                color: "#666",
                maxRotation: 45,
              },
            },
            y: {
              display: true,
              title: {
                display: true,
                text: "Jumlah Laporan",
                color: "#666",
                font: {
                  size: 12,
                  weight: "bold",
                },
              },
              beginAtZero: true,
              ticks: {
                stepSize: 1,
                color: "#666",
              },
              grid: {
                color: "rgba(0,0,0,0.1)",
              },
            },
          },
          interaction: {
            mode: "nearest",
            axis: "x",
            intersect: false,
          },
        },
      });
    })
    .catch((error) => {
      console.error("Error loading trend chart:", error);
      if (container) {
        container.innerHTML = `<div class="chart-error">Gagal memuat grafik tren bulanan: ${error.message}</div>`;
      }
    });
}

// Create dashboard trend chart (simplified version for dashboard)
function createDashboardTrendChart() {
  const ctx = document.getElementById("dashboardTrendChart");
  if (!ctx || !ctx.parentElement) {
    console.warn(
      "Dashboard trend chart canvas or its parent element not found"
    );
    return;
  }

  // Store reference to parent container before modifying DOM
  const container = ctx.parentElement;

  // Destroy existing chart instance if exists
  const chartInstance = Chart.getChart(ctx);
  if (chartInstance) {
    chartInstance.destroy();
  }

  // Show loading state
  container.innerHTML =
    '<div class="chart-loading">Memuat data statistik...</div>';

  fetch("php/admin_handler.php?action=get_statistics")
    .then((response) => response.json())
    .then((data) => {
      if (data.error) {
        console.error("Error fetching dashboard statistics:", data.error);
        container.innerHTML = `<div class="chart-error">Error: ${data.error}</div>`;
        return;
      }

      // Restore canvas element
      container.innerHTML = '<canvas id="dashboardTrendChart"></canvas>';
      const newCtx = document.getElementById("dashboardTrendChart");

      const monthlyData = data.monthlyStats || [];
      const currentYear = new Date().getFullYear();

      // Get last 6 months for dashboard view
      const monthLabels = [];
      const monthCounts = [];
      const currentMonth = new Date().getMonth() + 1;

      for (let i = 5; i >= 0; i--) {
        let month = currentMonth - i;
        let year = currentYear;

        if (month <= 0) {
          month += 12;
          year -= 1;
        }

        const monthKey = `${year}-${month.toString().padStart(2, "0")}`;
        const monthName = new Date(year, month - 1, 1).toLocaleDateString(
          "id-ID",
          { month: "short" }
        );
        monthLabels.push(monthName);

        const monthData = monthlyData.find((item) => item.month === monthKey);
        monthCounts.push(monthData ? parseInt(monthData.count) : 0);
      }

      new Chart(newCtx, {
        type: "line",
        data: {
          labels: monthLabels,
          datasets: [
            {
              label: "Laporan",
              data: monthCounts,
              borderColor: "#3498db",
              backgroundColor: "rgba(52,152,219,0.1)",
              borderWidth: 3,
              fill: true,
              tension: 0.4,
              pointBackgroundColor: "#3498db",
              pointBorderColor: "#fff",
              pointBorderWidth: 2,
              pointRadius: 5,
              pointHoverRadius: 7,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false,
            },
            tooltip: {
              mode: "index",
              intersect: false,
              backgroundColor: "rgba(0,0,0,0.8)",
              titleColor: "#fff",
              bodyColor: "#fff",
              borderColor: "#3498db",
              borderWidth: 1,
            },
          },
          scales: {
            x: {
              display: true,
              grid: {
                display: false,
              },
            },
            y: {
              display: true,
              beginAtZero: true,
              ticks: {
                stepSize: 1,
                color: "#666",
              },
              grid: {
                color: "rgba(0,0,0,0.1)",
              },
            },
          },
          interaction: {
            mode: "nearest",
            axis: "x",
            intersect: false,
          },
        },
      });
    })
    .catch((error) => {
      console.error("Error loading dashboard trend chart:", error);
      if (container) {
        container.innerHTML = `<div class="chart-error">Gagal memuat grafik: ${error.message}</div>`;
      }
    });
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

// Function to show/toggle the user profile modal (adapted for admin context)
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

// Initialize mobile menu toggle
function initializeMobileMenu() {
  const mobileMenuToggle = document.getElementById("mobileMenuToggle");
  const sidebar = document.getElementById("sidebar");
  const overlay = document.getElementById("mobileMenuOverlay");

  if (mobileMenuToggle && sidebar && overlay) {
    mobileMenuToggle.addEventListener("click", function () {
      const isOpen = sidebar.classList.contains("mobile-open");

      if (isOpen) {
        closeMobileMenu();
      } else {
        openMobileMenu();
      }
    });

    // Close sidebar when clicking on overlay
    overlay.addEventListener("click", function () {
      closeMobileMenu();
    });

    // Close sidebar when clicking on a menu item (mobile)
    const sidebarLinks = sidebar.querySelectorAll(".sidebar-link");
    sidebarLinks.forEach((link) => {
      link.addEventListener("click", function () {
        if (window.innerWidth <= 768) {
          closeMobileMenu();
        }
      });
    });

    // Handle window resize
    window.addEventListener("resize", function () {
      if (
        window.innerWidth > 768 &&
        sidebar.classList.contains("mobile-open")
      ) {
        closeMobileMenu();
      }
    });
  }

  function openMobileMenu() {
    sidebar.classList.add("mobile-open");
    overlay.classList.add("active");
    const icon = mobileMenuToggle.querySelector("i");
    icon.className = "fas fa-times";
    document.body.style.overflow = "hidden"; // Prevent background scrolling
  }

  function closeMobileMenu() {
    sidebar.classList.remove("mobile-open");
    overlay.classList.remove("active");
    const icon = mobileMenuToggle.querySelector("i");
    icon.className = "fas fa-bars";
    document.body.style.overflow = ""; // Restore scrolling
  }
}

// showNotification function is assumed to be available from shared/js/script.js
// If not, ensure shared/js/script.js is loaded first, or redefine it here.

/**
 * Settings Functions
 */

// Load settings data
function loadSettingsData() {
  console.log("Loading admin settings data...");

  // Load admin profile information
  loadAdminProfileInfo();

  // Load system settings
  loadSystemSettings();

  // Set login time
  setLoginTimeDisplay();
}

// Load admin profile information
function loadAdminProfileInfo() {
  // Get admin info from session storage or set defaults
  const adminName = sessionStorage.getItem("userName") || "Administrator";
  const adminEmail = sessionStorage.getItem("userEmail") || "admin@elapor.com";

  let adminUsername = "admin";
  const currentUserString = sessionStorage.getItem("currentUser");
  if (currentUserString) {
    try {
      const currentUser = JSON.parse(currentUserString);
      adminUsername = currentUser.username || "admin";
    } catch (e) {
      console.error("Error parsing currentUser from sessionStorage:", e);
      adminUsername = "admin";
    }
  }

  // Update profile fields
  const adminFullNameField = document.getElementById("adminFullName");
  const adminEmailField = document.getElementById("adminEmail");
  const adminUsernameField = document.getElementById("adminUsername");
  const settingsAdminName = document.getElementById("settingsAdminName");

  if (adminFullNameField) adminFullNameField.value = adminName;
  if (adminEmailField) adminEmailField.value = adminEmail;
  if (adminUsernameField) adminUsernameField.value = adminUsername;
  if (settingsAdminName) settingsAdminName.textContent = adminName;
}

// Load system settings
function loadSystemSettings() {
  // You can load system settings from server here
  // For now, we'll use default values
  const emailNotifications = document.getElementById("emailNotifications");
  const autoAssign = document.getElementById("autoAssign");

  if (emailNotifications) emailNotifications.checked = true;
  if (autoAssign) autoAssign.checked = false;
}

// Set login time display (FIXED: Properly handle date formatting)
function setLoginTimeDisplay() {
  const loginTimeElement = document.getElementById("loginTime");
  if (loginTimeElement) {
    const loginTime = sessionStorage.getItem("loginTime");
    if (loginTime) {
      try {
        // Try to parse as ISO date first
        let date = new Date(loginTime);

        // If the date is invalid, it might be already in localized format
        if (isNaN(date.getTime())) {
          // Try to parse localized Indonesian format (dd/mm/yyyy, hh:mm:ss)
          const localizedPattern =
            /(\d{1,2})\/(\d{1,2})\/(\d{4}),?\s*(\d{1,2}):(\d{2}):(\d{2})/;
          const match = loginTime.match(localizedPattern);

          if (match) {
            const [, day, month, year, hour, minute, second] = match;
            date = new Date(year, month - 1, day, hour, minute, second);
          } else {
            // Fallback: use current time
            date = new Date();
            console.warn(
              "Could not parse login time, using current time:",
              loginTime
            );
          }
        }

        // Display in Indonesian locale
        loginTimeElement.textContent = date.toLocaleString("id-ID", {
          year: "numeric",
          month: "2-digit",
          day: "2-digit",
          hour: "2-digit",
          minute: "2-digit",
          second: "2-digit",
        });
      } catch (error) {
        console.error("Error parsing login time:", error);
        // Fallback: use current time
        const now = new Date();
        loginTimeElement.textContent = now.toLocaleString("id-ID", {
          year: "numeric",
          month: "2-digit",
          day: "2-digit",
          hour: "2-digit",
          minute: "2-digit",
          second: "2-digit",
        });
      }
    } else {
      // Set current time as login time if not available
      const now = new Date();
      sessionStorage.setItem("loginTime", now.toISOString());
      loginTimeElement.textContent = now.toLocaleString("id-ID", {
        year: "numeric",
        month: "2-digit",
        day: "2-digit",
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit",
      });
    }
  }
}

// Update admin profile
function updateAdminProfile() {
  const adminFullName = document.getElementById("adminFullName").value;
  const adminEmail = document.getElementById("adminEmail").value;

  if (!adminFullName.trim() || !adminEmail.trim()) {
    if (typeof showNotification === "function") {
      showNotification("Nama lengkap dan email harus diisi.", "error");
    }
    return;
  }

  // Show loading notification
  if (typeof showNotification === "function") {
    showNotification("Mengupdate profil admin...", "info");
  }

  // Send update request to server
  fetch("php/admin_handler.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      action: "update_admin_profile",
      fullName: adminFullName,
      email: adminEmail,
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        // Update session storage only after successful server update
        sessionStorage.setItem("userName", adminFullName);
        sessionStorage.setItem("userEmail", adminEmail);

        // Update currentUser object in session storage
        const currentUserString = sessionStorage.getItem("currentUser");
        if (currentUserString) {
          try {
            const currentUser = JSON.parse(currentUserString);
            currentUser.fullName = adminFullName;
            currentUser.email = adminEmail;
            sessionStorage.setItem("currentUser", JSON.stringify(currentUser));
          } catch (e) {
            console.error("Error updating currentUser in sessionStorage:", e);
          }
        }

        // Update UI elements
        const settingsAdminName = document.getElementById("settingsAdminName");
        if (settingsAdminName) settingsAdminName.textContent = adminFullName;

        if (typeof showNotification === "function") {
          showNotification(
            data.message || "Profil admin berhasil diperbarui.",
            "success"
          );
        }

        console.log("Admin profile updated successfully:", {
          adminFullName,
          adminEmail,
        });
      } else {
        if (typeof showNotification === "function") {
          showNotification(
            data.error || "Gagal memperbarui profil admin.",
            "error"
          );
        }
      }
    })
    .catch((error) => {
      console.error("Error updating admin profile:", error);
      if (typeof showNotification === "function") {
        showNotification(
          "Terjadi kesalahan saat memperbarui profil admin.",
          "error"
        );
      }
    });
}

// Change password function
function changePassword() {
  const currentPassword = document.getElementById("currentPassword").value;
  const newPassword = document.getElementById("newPassword").value;
  const confirmPassword = document.getElementById("confirmPassword").value;

  // Validation
  if (!currentPassword || !newPassword || !confirmPassword) {
    if (typeof showNotification === "function") {
      showNotification("Semua field kata sandi harus diisi.", "error");
    }
    return;
  }

  if (newPassword !== confirmPassword) {
    if (typeof showNotification === "function") {
      showNotification("Konfirmasi kata sandi tidak cocok.", "error");
    }
    return;
  }

  if (newPassword.length < 6) {
    if (typeof showNotification === "function") {
      showNotification("Kata sandi baru minimal 6 karakter.", "error");
    }
    return;
  }

  // Show loading
  if (typeof showNotification === "function") {
    showNotification("Mengubah kata sandi...", "info");
  }

  // Send password change request
  fetch("../shared/php/update_password.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
    body: JSON.stringify({
      old_password: currentPassword,
      new_password: newPassword,
      userType: "admin",
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        if (typeof showNotification === "function") {
          showNotification("Kata sandi berhasil diubah.", "success");
        }
        // Clear password fields
        document.getElementById("currentPassword").value = "";
        document.getElementById("newPassword").value = "";
        document.getElementById("confirmPassword").value = "";
      } else {
        if (typeof showNotification === "function") {
          showNotification(data.error || "Gagal mengubah kata sandi.", "error");
        }
      }
    })
    .catch((error) => {
      console.error("Error changing admin password:", error);
      if (typeof showNotification === "function") {
        showNotification(
          "Terjadi kesalahan saat mengubah kata sandi.",
          "error"
        );
      }
    });
}

// Update system settings
function updateSystemSettings() {
  const emailNotifications =
    document.getElementById("emailNotifications").checked;
  const autoAssign = document.getElementById("autoAssign").checked;

  // Save to localStorage for now (in real app, send to server)
  localStorage.setItem("adminEmailNotifications", emailNotifications);
  localStorage.setItem("adminAutoAssign", autoAssign);

  if (typeof showNotification === "function") {
    showNotification("Pengaturan sistem berhasil disimpan.", "success");
  }

  console.log("System settings updated:", { emailNotifications, autoAssign });
}

// User Management Functions
function loadUsersTable() {
  fetch("php/admin_handler.php?action=get_all_users")
    .then((response) => response.json())
    .then((data) => {
      if (data.error) {
        console.error("Error fetching users:", data.error);
        document.querySelector(
          "#usersTable tbody"
        ).innerHTML = `<tr><td colspan="9">Gagal memuat daftar pengguna: ${data.error}</td></tr>`;
        return;
      }

      displayUsersTable(data.users || []);
    })
    .catch((error) => {
      console.error("Error loading users:", error);
      document.querySelector("#usersTable tbody").innerHTML =
        '<tr><td colspan="9">Terjadi kesalahan jaringan saat memuat daftar pengguna.</td></tr>';
    });
}

function displayUsersTable(users) {
  const tbody = document.querySelector("#usersTable tbody");
  tbody.innerHTML = "";

  if (!users || users.length === 0) {
    tbody.innerHTML = '<tr><td colspan="9">Tidak ada data pengguna.</td></tr>';
    return;
  }

  users.forEach((user) => {
    const row = document.createElement("tr");
    row.innerHTML = `
      <td>${user.id || "-"}</td>
      <td>${user.fullName || "-"}</td>
      <td>${user.username || "-"}</td>
      <td>${user.email || "-"}</td>
      <td>${user.phone || "-"}</td>
      <td>${user.role || "user"}</td>
      <td><span class="status-${user.status || "active"}">${
      user.status || "Active"
    }</span></td>
      <td>${user.created_at ? formatDate(user.created_at) : "-"}</td>      <td>
        <button class="btn btn-sm btn-primary" onclick="editUser('${
          user.id
        }')" title="Edit">
          <i class="fas fa-edit"></i>
        </button>
        <button class="btn btn-sm btn-danger" onclick="deleteUser('${
          user.id
        }')" title="Hapus">
          <i class="fas fa-trash"></i>
        </button>
      </td>
    `;
    tbody.appendChild(row);
  });
}

function openAddUserModal() {
  // Clear form
  document.getElementById("newUserFullName").value = "";
  document.getElementById("newUserUsername").value = "";
  document.getElementById("newUserEmail").value = "";
  document.getElementById("newUserPhone").value = "";
  document.getElementById("newUserPassword").value = "";
  document.getElementById("newUserRole").value = "user";

  openModal("addUserModal");
}

function addNewUser() {
  const userData = {
    action: "add_new_user",
    fullName: document.getElementById("newUserFullName").value,
    username: document.getElementById("newUserUsername").value,
    email: document.getElementById("newUserEmail").value,
    phone: document.getElementById("newUserPhone").value,
    password: document.getElementById("newUserPassword").value,
    role: document.getElementById("newUserRole").value,
  };

  // Basic validation
  if (
    !userData.fullName ||
    !userData.username ||
    !userData.email ||
    !userData.password
  ) {
    alert("Mohon lengkapi semua field yang wajib diisi.");
    return;
  }

  fetch("php/admin_handler.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(userData),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.error) {
        alert("Error: " + data.error);
        return;
      }

      alert("Pengguna berhasil ditambahkan!");
      closeModal("addUserModal");
      loadUsersTable(); // Refresh table
    })
    .catch((error) => {
      console.error("Error adding user:", error);
      alert("Terjadi kesalahan saat menambahkan pengguna.");
    });
}

function editUser(userId) {
  // Fetch user data first
  fetch(`php/admin_handler.php?action=get_user_detail&user_id=${userId}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.error) {
        alert("Error: " + data.error);
        return;
      }

      const user = data.user;

      // Populate edit form with user data
      document.getElementById("editUserFullName").value = user.fullName || "";
      document.getElementById("editUserUsername").value = user.username || "";
      document.getElementById("editUserEmail").value = user.email || "";
      document.getElementById("editUserPhone").value = user.phone || "";
      document.getElementById("editUserRole").value = user.role || "user";

      // Store user ID for saving
      window.currentEditUserId = userId;

      // Open modal
      openModal("editUserModal");
    })
    .catch((error) => {
      console.error("Error fetching user details:", error);
      alert("Terjadi kesalahan saat mengambil data pengguna.");
    });
}

function saveUserEdit() {
  if (!window.currentEditUserId) {
    alert("Tidak ada pengguna yang sedang diedit.");
    return;
  }

  const userData = {
    action: "update_user",
    user_id: window.currentEditUserId,
    fullName: document.getElementById("editUserFullName").value,
    email: document.getElementById("editUserEmail").value,
    phone: document.getElementById("editUserPhone").value,
    role: document.getElementById("editUserRole").value,
  };

  // Basic validation
  if (!userData.fullName || !userData.email) {
    alert("Nama lengkap dan email harus diisi.");
    return;
  }

  fetch("php/admin_handler.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(userData),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.error) {
        alert("Error: " + data.error);
        return;
      }

      alert("Data pengguna berhasil diperbarui!");
      closeModal("editUserModal");
      loadUsersTable(); // Refresh table
      window.currentEditUserId = null; // Clear stored ID
    })
    .catch((error) => {
      console.error("Error updating user:", error);
      alert("Terjadi kesalahan saat memperbarui data pengguna.");
    });
}

function deleteUser(userId) {
  if (confirm("Apakah Anda yakin ingin menghapus pengguna ini?")) {
    fetch("php/admin_handler.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        action: "delete_user",
        user_id: userId,
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.error) {
          alert("Error: " + data.error);
          return;
        }

        alert("Pengguna berhasil dihapus!");
        loadUsersTable(); // Refresh table
      })
      .catch((error) => {
        console.error("Error deleting user:", error);
        alert("Terjadi kesalahan saat menghapus pengguna.");
      });
  }
}

// Modal Management Functions
function openModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.style.display = "block";
    document.body.style.overflow = "hidden";
  }
}

function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.style.display = "none";
    document.body.style.overflow = "auto";
  }
}

// Close modal when clicking outside
window.onclick = function (event) {
  if (event.target.classList.contains("modal")) {
    event.target.style.display = "none";
    document.body.style.overflow = "auto";
  }
};

// Setup modal event listeners
function setupModalEventListeners() {
  // Close modal when clicking the X button
  document.querySelectorAll(".modal .close").forEach((closeBtn) => {
    closeBtn.addEventListener("click", function () {
      const modal = this.closest(".modal");
      if (modal) {
        closeModal(modal.id);
      }
    });
  });

  // Close modals with Escape key
  document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") {
      const openModals = document.querySelectorAll('.modal[style*="block"]');
      openModals.forEach((modal) => {
        closeModal(modal.id);
      });
    }
  });
}

// Generate PDF Report function
function generateReport() {
  // Show loading notification
  if (typeof showNotification === "function") {
    showNotification("Generating PDF report...", "info");
  }

  // For now, show a placeholder message
  // In a real implementation, this would call a server endpoint to generate a PDF
  alert(
    "PDF Report generation feature coming soon!\n\nThis would generate a comprehensive report including:\n- Dashboard statistics\n- Chart visualizations\n- Recent reports summary\n- Monthly trends"
  );

  console.log("PDF Report generation requested");

  // Example of what this might look like in a real implementation:
  /*
  fetch('php/admin_handler.php?action=generate_pdf_report')
    .then(response => response.blob())
    .then(blob => {
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.style.display = 'none';
      a.href = url;
      a.download = `laporan-${new Date().toISOString().split('T')[0]}.pdf`;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      
      if (typeof showNotification === 'function') {
        showNotification('PDF report downloaded successfully!', 'success');
      }
    })
    .catch(error => {
      console.error('Error generating PDF:', error);
      if (typeof showNotification === 'function') {
        showNotification('Failed to generate PDF report', 'error');
      }
    });
  */
}

// Update charts
