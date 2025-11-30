document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("loginForm");
    const username = form.username;
    const password = form.password;

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

        let valid = true;
        if (!username.value.trim()) {
            showError(username, "Username is required");
            valid = false;
        } else clearError(username);

        if (!password.value.trim()) {
            showError(password, "Password is required");
            valid = false;
        } else clearError(password);

        if (!valid) return;

        const formData = new FormData(form);

        try {
            const response = await fetch(form.action, {
                method: "POST",
                body: formData,
            });

            const result = await response.json();

            if (result.status === "success") {
                // If the server indicates this user is an admin, send them to admin dashboard
                const role = (result.role || '').toString().toLowerCase();
                if (role === 'admin') {
                    // Use absolute path so redirect works from any page
                    window.location.href = '/Waste-Not-Kitchen/admin-dashboard.php';
                } else {
                    window.location.href = 'profile.php';
                }
            } else if (result.field === "username" || result.field === "password") {
                showError(password, "Incorrect Username or Password");
            } else {
                console.error("Login error:", result.message);
            }
        } catch (err) {
            console.error("Network error:", err);
        }
    });
});