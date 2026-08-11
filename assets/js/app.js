// ========================================
// NAVBAR SCROLL EFFECT
// ========================================
window.addEventListener("scroll", function () {
  const navbar = document.querySelector(".navbar");
  if (window.scrollY > 30) {
    navbar.classList.add("scrolled");
  } else {
    navbar.classList.remove("scrolled");
  }
});

// ========================================
// SEARCH FORM HANDLING
// ========================================
document.addEventListener("DOMContentLoaded", function () {
  const searchForm = document.querySelector(".search-form");
  const dateInput = document.querySelector("#travel-date");

  // Set minimum date to tomorrow
  if (dateInput) {
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    dateInput.min = tomorrow.toISOString().split("T")[0];
    dateInput.value = tomorrow.toISOString().split("T")[0];
  }

  // Handle form submission
  if (searchForm) {
    searchForm.addEventListener("submit", function (e) {
      e.preventDefault();

      const origin = document.querySelector("#origin").value.trim();
      const destination = document.querySelector("#destination").value.trim();
      const date = document.querySelector("#travel-date").value;

      if (!origin || !destination || !date) {
        alert("Please fill in all fields to search for available buses.");
        return;
      }

      // Redirect to search results
      const params = new URLSearchParams({
        origin: origin,
        destination: destination,
        travel_date: date,
      });

      window.location.href = "search-results.php?" + params.toString();
    });
  }
});

// ========================================
// FEATURE CARDS ANIMATION
// ========================================
document.querySelectorAll(".feature-card").forEach((card, index) => {
  card.style.opacity = "0";
  card.style.transform = "translateY(30px)";

  setTimeout(
    () => {
      card.style.transition = "all 0.6s ease";
      card.style.opacity = "1";
      card.style.transform = "translateY(0)";
    },
    200 + index * 150,
  );
});

// ========================================
// PASSWORD TOGGLE (robust)
// ========================================
document.addEventListener("click", function (e) {
  const btn = e.target.closest && e.target.closest(".toggle-password");
  if (!btn) return;
  e.preventDefault();

  const wrapper = btn.closest(".password-wrapper");
  if (!wrapper) return;

  const input = wrapper.querySelector(
    'input[type="password"], input[type="text"]',
  );
  const icon = btn.querySelector("i");
  if (!input) return;

  if (input.type === "password") {
    input.type = "text";
    if (icon) {
      icon.classList.remove("fa-eye");
      icon.classList.add("fa-eye-slash");
    }
    btn.setAttribute("aria-pressed", "true");
  } else {
    input.type = "password";
    if (icon) {
      icon.classList.remove("fa-eye-slash");
      icon.classList.add("fa-eye");
    }
    btn.setAttribute("aria-pressed", "false");
  }
});

// ========================================
// SMOOTH SCROLL
// ========================================
document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
  anchor.addEventListener("click", function (e) {
    const href = this.getAttribute("href");
    if (href !== "#") {
      e.preventDefault();
      const target = document.querySelector(href);
      if (target) {
        target.scrollIntoView({
          behavior: "smooth",
          block: "start",
        });
      }
    }
  });
});
