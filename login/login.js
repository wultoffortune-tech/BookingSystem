// ========================================
// CAMEXPRESS LOGIN - JavaScript
// ========================================

document.addEventListener("DOMContentLoaded", function () {
  // ========================================
  // 1. PASSWORD VISIBILITY TOGGLE
  // ========================================
  var togglePassword = document.getElementById("togglePassword");
  var passwordInput = document.getElementById("password");

  if (togglePassword && passwordInput) {
    togglePassword.addEventListener("click", function () {
      var type =
        passwordInput.getAttribute("type") === "password" ? "text" : "password";
      passwordInput.setAttribute("type", type);

      // Toggle eye icon
      var icon = this.querySelector("i");
      if (icon) {
        icon.classList.toggle("fa-eye");
        icon.classList.toggle("fa-eye-slash");
      }
    });
  }

  // ========================================
  // 2. REAL-TIME VALIDATION
  // ========================================
  var emailInput = document.getElementById("email");
  var emailError = document.getElementById("emailError");
  var passwordError = document.getElementById("passwordError");
  var loginBtn = document.getElementById("loginBtn");
  var btnText = loginBtn ? loginBtn.querySelector(".btn-text") : null;
  var btnLoader = loginBtn ? loginBtn.querySelector(".btn-loader") : null;

  // Validate email on input
  if (emailInput && emailError) {
    emailInput.addEventListener("input", function () {
      var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

      if (this.value.trim() === "") {
        emailError.textContent = "";
        this.classList.remove("error", "success");
      } else if (!emailRegex.test(this.value)) {
        emailError.textContent = "Please enter a valid email address";
        this.classList.add("error");
        this.classList.remove("success");
      } else {
        emailError.textContent = "✓ Valid email";
        this.classList.remove("error");
        this.classList.add("success");
      }
    });
  }

  // Validate password on input
  if (passwordInput && passwordError) {
    passwordInput.addEventListener("input", function () {
      if (this.value.length > 0 && this.value.length < 6) {
        passwordError.textContent = "Password must be at least 6 characters";
        this.classList.add("error");
        this.classList.remove("success");
      } else if (this.value.length >= 6) {
        passwordError.textContent = "✓ Strong password";
        this.classList.remove("error");
        this.classList.add("success");
      } else {
        passwordError.textContent = "";
        this.classList.remove("error", "success");
      }
    });
  }

  // ========================================
  // 3. FORM SUBMISSION
  // ========================================
  var loginForm = document.getElementById("loginForm");

  if (loginForm) {
    loginForm.addEventListener("submit", function (e) {
      e.preventDefault();

      // Clear previous errors
      clearErrors();

      // Validate all fields
      var isEmailValid = validateEmail();
      var isPasswordValid = validatePassword();

      if (!isEmailValid || !isPasswordValid) {
        var firstError = document.querySelector(".form-group input.error");
        if (firstError) {
          firstError.focus();
        }
        return;
      }

      // Show loading state
      showLoading();

      // Get form data
      var formData = new FormData(this);

      // Send AJAX request to login-process.php
      fetch("login-process.php", {
        method: "POST",
        body: formData,
      })
        .then(function (response) {
          return response.json();
        })
        .then(function (data) {
          // Hide loading state
          hideLoading();

          if (data.success) {
            // Login successful
            showMessage("✅ Login successful! Redirecting...", "success");

            // Log the redirect URL for debugging
            console.log("Redirecting to:", data.redirect);

            // Redirect after 1.5 seconds
            setTimeout(function () {
              if (data.redirect) {
                window.location.href = data.redirect;
              } else {
                // Fallback redirect
                window.location.href = "../home.php";
              }
            }, 1500);
          } else {
            // Login failed
            showMessage(
              data.message || "Login failed. Please try again.",
              "error",
            );
          }
        })
        .catch(function (error) {
          // Network or server error
          hideLoading();
          showMessage("An error occurred. Please try again.", "error");
          console.error("Error:", error);
        });
    });
  }

  // ========================================
  // 4. VALIDATION FUNCTIONS
  // ========================================
  function validateEmail() {
    var email = emailInput ? emailInput.value.trim() : "";
    var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (!email) {
      if (emailError) emailError.textContent = "Email address is required";
      if (emailInput) emailInput.classList.add("error");
      if (emailInput) emailInput.classList.remove("success");
      return false;
    } else if (!emailRegex.test(email)) {
      if (emailError)
        emailError.textContent = "Please enter a valid email address";
      if (emailInput) emailInput.classList.add("error");
      if (emailInput) emailInput.classList.remove("success");
      return false;
    }

    return true;
  }

  function validatePassword() {
    var password = passwordInput ? passwordInput.value : "";

    if (!password) {
      if (passwordError) passwordError.textContent = "Password is required";
      if (passwordInput) passwordInput.classList.add("error");
      if (passwordInput) passwordInput.classList.remove("success");
      return false;
    } else if (password.length < 6) {
      if (passwordError)
        passwordError.textContent = "Password must be at least 6 characters";
      if (passwordInput) passwordInput.classList.add("error");
      if (passwordInput) passwordInput.classList.remove("success");
      return false;
    }

    return true;
  }

  // ========================================
  // 5. UI HELPERS
  // ========================================
  function clearErrors() {
    var inputs = document.querySelectorAll(".form-group input");
    for (var i = 0; i < inputs.length; i++) {
      inputs[i].classList.remove("error", "success");
    }
    var messages = document.querySelectorAll(".error-message");
    for (var j = 0; j < messages.length; j++) {
      messages[j].textContent = "";
    }
    hideMessage();
  }

  function showLoading() {
    if (loginBtn) {
      loginBtn.disabled = true;
      if (btnText) btnText.style.display = "none";
      if (btnLoader) btnLoader.style.display = "inline";
    }
  }

  function hideLoading() {
    if (loginBtn) {
      loginBtn.disabled = false;
      if (btnText) btnText.style.display = "inline";
      if (btnLoader) btnLoader.style.display = "none";
    }
  }

  function showMessage(message, type) {
    var messageDiv = document.getElementById("loginMessage");
    if (messageDiv) {
      messageDiv.textContent = message;
      messageDiv.className = "login-message " + type;
      messageDiv.style.display = "block";

      // Auto hide after 5 seconds
      setTimeout(function () {
        hideMessage();
      }, 5000);
    }
  }

  function hideMessage() {
    var messageDiv = document.getElementById("loginMessage");
    if (messageDiv) {
      messageDiv.style.display = "none";
      messageDiv.className = "login-message";
    }
  }

  // ========================================
  // 6. ENTER KEY SUPPORT
  // ========================================
  document.addEventListener("keydown", function (e) {
    if (e.key === "Enter") {
      var activeElement = document.activeElement;
      if (
        activeElement &&
        (activeElement.id === "email" || activeElement.id === "password")
      ) {
        e.preventDefault();
        if (loginForm) {
          loginForm.dispatchEvent(new Event("submit"));
        }
      }
    }
  });

  // ========================================
  // 7. CONSOLE INFO
  // ========================================
  console.log("🚌 CamExpress Login loaded successfully");
  console.log("📌 Press Enter to submit the form");
  console.log("🔑 Demo: admin@camexpress.cm / Admin123");
});
