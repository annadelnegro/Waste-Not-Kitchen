document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("registerForm");
    const roleSelect = document.getElementById("role");
    const paymentSection = document.getElementById("payment-section");

    // --- Show/hide payment section based on role ---
    roleSelect.addEventListener("change", () => {
        if (["customer", "donor"].includes(roleSelect.value)) {
            paymentSection.classList.remove("hidden");
        } else {
            paymentSection.classList.add("hidden");
        }
    });

    // --- Error handling helpers ---
    const showError = (input, message) => {
        const error = input.parentElement.querySelector(".error-message");
        if (error) {
            error.textContent = message;
            error.style.visibility = "visible";
        }
    };

    const clearError = (input) => {
        const error = input.parentElement.querySelector(".error-message");
        if (error) {
            error.textContent = "";
            error.style.visibility = "hidden";
        }
    };

    // --- Validation patterns ---
    const usernamePattern = /^[A-Za-z0-9_]{4,20}$/;
    const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*]).{8,}$/;
    const phonePattern = /^\d{3}-\d{3}-\d{4}$/;
    const cardPattern = /^\d{13,19}$/;
    const cvcPattern = /^\d{3,4}$/;
    const expPattern = /^(0[1-9]|1[0-2])\/\d{2}$/;

    // --- Submit handler ---
    form.addEventListener("submit", async (e) => {
        e.preventDefault();
        let valid = true;

        const username = form.username;
        const password = form.password;
        const confirmPassword = form.confirm_password;
        const phone = form.phone;
        const cardNumber = form.card_number;
        const cvc = form.cvc;
        const exp = form.expiration_date;

        // --- Username check ---
        if (!usernamePattern.test(username.value)) {
            showError(username, "Username does not meet requirements");
            valid = false;
        } else clearError(username);

        // --- Password check ---
        if (!passwordPattern.test(password.value)) {
            showError(password, "Password does not meet requirements");
            valid = false;
        } else clearError(password);

        // --- Confirm password check ---
        if (password.value !== confirmPassword.value) {
            showError(confirmPassword, "Passwords do not match");
            valid = false;
        } else clearError(confirmPassword);

        // --- Phone check ---
        if (phone.value && !phonePattern.test(phone.value)) {
            showError(phone, "Phone format: 123-456-7890");
            valid = false;
        } else clearError(phone);

        // --- Payment section validation ---
        if (!paymentSection.classList.contains("hidden")) {
            if (!cardPattern.test(cardNumber.value)) {
                showError(cardNumber, "Card number must be 13–19 digits");
                valid = false;
            } else clearError(cardNumber);

            if (!cvcPattern.test(cvc.value)) {
                showError(cvc, "CVC must be 3–4 digits");
                valid = false;
            } else clearError(cvc);

            if (!expPattern.test(exp.value)) {
                showError(exp, "Expiration format MM/YY");
                valid = false;
            } else clearError(exp);
        }

        if (!valid) return;

        const formData = new FormData(form);

        try {
            const response = await fetch(form.action, {
                method: "POST",
                body: formData,
            });

            const result = await response.json();

            if (result.status === "success") {
                // Redirect to login page on success
                window.location.href = "login.php";
            } else {
                // Inline error handling for username uniqueness and generic server errors
                if (result.field === "username") {
                    showError(username, result.message || "username already exists");
                } else {
                    console.error("Registration error:", result.message || "Unknown error");
                }
            }
        } catch (err) {
            console.error("Network error:", err);
        }
    });
});