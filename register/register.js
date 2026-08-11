document.addEventListener("DOMContentLoaded", function () {
  // ========================================
  // 1. PASSWORD VISIBILITY TOGGLE
  // ========================================
  const togglePassword = document.getElementById("togglePassword");
  const toggleConfirm = document.getElementById("toggleConfirmPassword");
  const passwordInput = document.getElementById("password");
  const confirmInput = document.getElementById("confirm_password");

  if (togglePassword && passwordInput) {
    togglePassword.addEventListener("click", function () {
      const type =
        passwordInput.getAttribute("type") === "password" ? "text" : "password";
      passwordInput.setAttribute("type", type);
      this.querySelector("i").classList.toggle("fa-eye");
      this.querySelector("i").classList.toggle("fa-eye-slash");
    });
  }

  if (toggleConfirm && confirmInput) {
    toggleConfirm.addEventListener("click", function () {
      const type =
        confirmInput.getAttribute("type") === "password" ? "text" : "password";
      confirmInput.setAttribute("type", type);
      this.querySelector("i").classList.toggle("fa-eye");
      this.querySelector("i").classList.toggle("fa-eye-slash");
    });
  }

  // ========================================
  // 2. PASSWORD STRENGTH INDICATOR
  // ========================================
  const strengthBars = document.querySelectorAll(".strength-bar");
  const strengthText = document.querySelector(".strength-text");

  if (passwordInput && strengthBars.length) {
    passwordInput.addEventListener("input", function () {
      const password = this.value;
      const strength = checkPasswordStrength(password);
      updateStrengthIndicator(strength);
    });
  }

  function checkPasswordStrength(password) {
    let score = 0;

    if (password.length >= 8) score++;
    if (password.length >= 12) score++;
    if (/[a-z]/.test(password)) score++;
    if (/[A-Z]/.test(password)) score++;
    if (/[0-9]/.test(password)) score++;
    if (/[^a-zA-Z0-9]/.test(password)) score++;

    if (password.length === 0)
      return { level: "none", text: "Weak", color: "" };
    if (score <= 2) return { level: "weak", text: "Weak", color: "weak" };
    if (score <= 4) return { level: "medium", text: "Medium", color: "medium" };
    if (score <= 5) return { level: "strong", text: "Strong", color: "strong" };
    return { level: "very-strong", text: "Very Strong", color: "very-strong" };
  }

  function updateStrengthIndicator(strength) {
    strengthBars.forEach((bar) => {
      bar.className = "strength-bar";
    });

    if (strength.level === "none") {
      strengthText.textContent = "Weak";
      strengthText.className = "strength-text";
      return;
    }

    let barsToFill = 0;
    switch (strength.level) {
      case "weak":
        barsToFill = 1;
        break;
      case "medium":
        barsToFill = 2;
        break;
      case "strong":
        barsToFill = 3;
        break;
      case "very-strong":
        barsToFill = 4;
        break;
    }

    for (let i = 0; i < barsToFill && i < strengthBars.length; i++) {
      strengthBars[i].classList.add("active", strength.level);
    }

    strengthText.textContent = strength.text;
    strengthText.className = "strength-text " + strength.color;
  }

  // ========================================
  // 3. REAL-TIME VALIDATION
  // ========================================
  const nameInput = document.getElementById("full_name");
  const emailInput = document.getElementById("email");
  const phoneInput = document.getElementById("phone_number");
  const idInput = document.getElementById("id_number");
  const confirmInputField = document.getElementById("confirm_password");

  const nameError = document.getElementById("nameError");
  const emailError = document.getElementById("emailError");
  const phoneError = document.getElementById("phoneError");
  const idError = document.getElementById("idError");
  const confirmError = document.getElementById("confirmError");

  // Validate name
  if (nameInput && nameError) {
    nameInput.addEventListener("input", function () {
      if (this.value.trim().length < 3 && this.value.length > 0) {
        nameError.textContent = "Name must be at least 3 characters";
        this.classList.add("error");
        this.classList.remove("success");
      } else if (this.value.trim().length >= 3) {
        nameError.textContent = "✓ Valid name";
        this.classList.remove("error");
        this.classList.add("success");
      } else {
        nameError.textContent = "";
        this.classList.remove("error", "success");
      }
    });
  }

  // Validate email
  if (emailInput && emailError) {
    emailInput.addEventListener("input", function () {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
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

  // Validate phone
  if (phoneInput && phoneError) {
    phoneInput.addEventListener("input", function () {
      const phoneRegex = /^[0-9+\-\s()]{7,}$/;
      if (this.value.trim() === "") {
        phoneError.textContent = "";
        this.classList.remove("error", "success");
      } else if (!phoneRegex.test(this.value)) {
        phoneError.textContent = "Please enter a valid phone number";
        this.classList.add("error");
        this.classList.remove("success");
      } else {
        phoneError.textContent = "✓ Valid phone";
        this.classList.remove("error");
        this.classList.add("success");
      }
    });
  }

  // Validate ID
  if (idInput && idError) {
    idInput.addEventListener("input", function () {
      if (this.value.trim() === "") {
        idError.textContent = "";
        this.classList.remove("error", "success");
      } else if (this.value.trim().length < 5) {
        idError.textContent = "ID must be at least 5 characters";
        this.classList.add("error");
        this.classList.remove("success");
      } else {
        idError.textContent = "✓ Valid ID";
        this.classList.remove("error");
        this.classList.add("success");
      }
    });
  }

  // Validate confirm password
  if (confirmInputField && confirmError) {
    confirmInputField.addEventListener("input", function () {
      const password = passwordInput ? passwordInput.value : "";
      if (this.value.trim() === "") {
        confirmError.textContent = "";
        this.classList.remove("error", "success");
      } else if (this.value !== password) {
        confirmError.textContent = "Passwords do not match";
        this.classList.add("error");
        this.classList.remove("success");
      } else {
        confirmError.textContent = "✓ Passwords match";
        this.classList.remove("error");
        this.classList.add("success");
      }
    });
  }

  // ========================================
  // 4. FORM SUBMISSION
  // ========================================
  const registerForm = document.getElementById("registerForm");
  const registerBtn = document.getElementById("registerBtn");
  const btnText = registerBtn?.querySelector(".btn-text");
  const btnLoader = registerBtn?.querySelector(".btn-loader");

  if (registerForm) {
    registerForm.addEventListener("submit", function (e) {
      clearErrors();

      const isValid = validateForm();

      if (!isValid) {
        const firstError = document.querySelector(".form-group input.error");
        if (firstError) {
          firstError.focus();
        }
        e.preventDefault();
      }
    });
  }

  // ========================================
  // 5. VALIDATION FUNCTIONS
  // ========================================
  function validateForm() {
    let isValid = true;

    const name = nameInput ? nameInput.value.trim() : "";
    if (name.length < 3) {
      if (nameError)
        nameError.textContent = "Name must be at least 3 characters";
      if (nameInput) nameInput.classList.add("error");
      isValid = false;
    }

    const email = emailInput ? emailInput.value.trim() : "";
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
      if (emailError)
        emailError.textContent = "Please enter a valid email address";
      if (emailInput) emailInput.classList.add("error");
      isValid = false;
    }

    const phone = phoneInput ? phoneInput.value.trim() : "";
    const phoneRegex = /^[0-9+\-\s()]{7,}$/;
    if (!phoneRegex.test(phone)) {
      if (phoneError)
        phoneError.textContent = "Please enter a valid phone number";
      if (phoneInput) phoneInput.classList.add("error");
      isValid = false;
    }

    const id = idInput ? idInput.value.trim() : "";
    if (id.length < 5) {
      if (idError) idError.textContent = "ID must be at least 5 characters";
      if (idInput) idInput.classList.add("error");
      isValid = false;
    }

    const password = passwordInput ? passwordInput.value : "";
    if (password.length < 8) {
      if (document.getElementById("passwordError")) {
        document.getElementById("passwordError").textContent =
          "Password must be at least 8 characters";
      }
      if (passwordInput) passwordInput.classList.add("error");
      isValid = false;
    }

    const confirm = confirmInputField ? confirmInputField.value : "";
    if (confirm !== password) {
      if (confirmError) confirmError.textContent = "Passwords do not match";
      if (confirmInputField) confirmInputField.classList.add("error");
      isValid = false;
    }

    const terms = document.getElementById("terms");
    if (terms && !terms.checked) {
      if (document.getElementById("termsError")) {
        document.getElementById("termsError").textContent =
          "You must agree to the Terms & Conditions";
      }
      isValid = false;
    }

    return isValid;
  }

  function clearErrors() {
    document.querySelectorAll(".form-group input").forEach((input) => {
      input.classList.remove("error", "success");
    });
    document.querySelectorAll(".error-message").forEach((el) => {
      el.textContent = "";
    });
    hideMessage();

    const termsError = document.getElementById("termsError");
    if (termsError) termsError.textContent = "";
  }

  function showLoading() {
    if (registerBtn) {
      registerBtn.disabled = true;
      if (btnText) btnText.style.display = "none";
      if (btnLoader) btnLoader.style.display = "inline";
    }
  }

  function hideLoading() {
    if (registerBtn) {
      registerBtn.disabled = false;
      if (btnText) btnText.style.display = "inline";
      if (btnLoader) btnLoader.style.display = "none";
    }
  }

  function showMessage(message, type) {
    const messageDiv = document.getElementById("registerMessage");
    if (messageDiv) {
      messageDiv.textContent = message;
      messageDiv.className = "register-message " + type;
      messageDiv.style.display = "block";

      setTimeout(() => {
        hideMessage();
      }, 5000);
    }
  }

  function hideMessage() {
    const messageDiv = document.getElementById("registerMessage");
    if (messageDiv) {
      messageDiv.style.display = "none";
      messageDiv.className = "register-message";
    }
  }

  console.log("👑 CamExpress Royal Register loaded");
  console.log("📌 Password: 8+ chars, uppercase, lowercase, number");
});
