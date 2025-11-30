document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("forgotForm");
    const step1 = document.getElementById("step1");
    const step2 = document.getElementById("step2");
    const step3 = document.getElementById("step3");
    const brainImg = document.getElementById("brainImage");
    const securityQuestionEl = document.getElementById("securityQuestion");
    const requirementsBox = document.getElementById("passwordRequirements");

    let currentStep = 1;
    let currentUsername = "";

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

    form.addEventListener("submit", async (e) => {
        e.preventDefault();

        // Build FormData only with fields relevant to the current step.
        const formData = new FormData();
        if (currentStep === 1) {
            formData.append('username', form.username.value.trim());
        } else if (currentStep === 2) {
            formData.append('username', form.username.value.trim());
            formData.append('security_answer', form.security_answer.value.trim());
        } else if (currentStep === 3) {
            formData.append('username', currentUsername || form.username.value.trim());
            formData.append('new_password', form.new_password.value);
            formData.append('confirm_password', form.confirm_password.value);
        }

        try {
            const response = await fetch(form.action, {
                method: "POST",
                body: formData,
            });

            // Safely parse JSON and fall back to logging raw text on error
            let result;
            const text = await response.text();
            try {
                result = text ? JSON.parse(text) : null;
            } catch (parseErr) {
                console.error("Network error: JSON parse failed", parseErr, "HTTP status:", response.status, "response text:", text);
                showError(form.username, "Server returned invalid JSON. See console for details.");
                return; // stop further processing so developer can inspect the raw response
            }

            if (!result) {
                console.error("Network error: empty JSON result", "HTTP status:", response.status, "response text:", text);
                showError(form.username, "Empty server response. See console for details.");
                return;
            }

            if (result.status === "error") {
                // Show appropriate inline error
                if (currentStep === 1) showError(form.username, result.message);
                if (currentStep === 2) showError(form.security_answer, result.message);
                if (currentStep === 3) showError(form.new_password, result.message);
            } else if (result.status === "success") {
                if (currentStep === 1) {
                    // Move to step 2
                    currentStep = 2;
                    currentUsername = form.username.value.trim();
                    securityQuestionEl.textContent = result.security_question;
                    step1.classList.add("hidden");
                    step2.classList.remove("hidden");
                    brainImg.src = "../../../assets/images/brain2.png";
                } else if (currentStep === 2) {
                    // Move to step 3
                    currentStep = 3;
                    step2.classList.add("hidden");
                    step3.classList.remove("hidden");
                    brainImg.src = "../../../assets/images/brain3.png";
                    requirementsBox.classList.remove("hidden");
                } else if (currentStep === 3) {
                    window.location.href = "login.php";
                }
            }
        } catch (err) {
            console.error("Network error:", err);
        }
    });
});