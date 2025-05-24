document.addEventListener("DOMContentLoaded", function () {
  const openBtn = document.getElementById("open-login-btn");
  const popupContainer = document.getElementById("popup-container");
  const popupOverlay = document.getElementById("popup-overlay");
  const verificationLink = document.querySelectorAll(".require-login");

  const bodyElement = document.body;
  const isLoggedIn = bodyElement.getAttribute("data-logged-in") === "true";

  function handleRequireLoginClick(e) {
    if (!isLoggedIn) {
      e.preventDefault();
      alert("Silakan login terlebih dahulu.");
    }
  }

  verificationLink.forEach((link) => {
    link.addEventListener("click", handleRequireLoginClick);
  });

  function showPopup() {
    if (popupOverlay && popupContainer) {
      popupOverlay.style.display = "block";
      popupContainer.style.display = "block";
    }
  }

  function hidePopup() {
    if (popupOverlay && popupContainer) {
      popupOverlay.style.display = "none";
      popupContainer.style.display = "none";
      popupContainer.innerHTML = "";
    }
  }

  async function loadAndShowPopup() {
    showPopup();
    popupContainer.innerHTML = "Memuat...";

    try {
      const response = await fetch("login.php");
      if (!response.ok) {
        throw new Error("Gagal memuat.");
      }
      const content = await response.text();
      popupContainer.innerHTML = content;

      const closeBtn = document.getElementById("close-popup");
      if (closeBtn) {
        closeBtn.addEventListener("click", hidePopup);
      }
    } catch (error) {
      popupContainer.innerHTML = `Error: ${error.message} <button id="close-popup-error">&times;</button>`;
      const closeBtnError = document.getElementById("close-popup-error");
      if (closeBtnError) {
        closeBtnError.addEventListener("click", hidePopup);
      }
    }
  }

  // Pastikan elemen ada sebelum menambah listener
  if (openBtn) {
    openBtn.addEventListener("click", function (e) {
      e.preventDefault();
      loadAndShowPopup();
    });
  }

  if (popupOverlay) {
    popupOverlay.addEventListener("click", hidePopup);
  }
});
