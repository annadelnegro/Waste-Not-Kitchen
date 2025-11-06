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

        const formData = new FormData(form);

        try {
            const response = await fetch(form.action, {
                method: "POST",
                body: formData,
            });
            const result = await response.json();

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