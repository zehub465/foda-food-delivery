
document.addEventListener("DOMContentLoaded", function () {

    // MOBILE NAVIGATION
    const toggle = document.querySelector(".menu-toggle");
    const nav = document.querySelector(".nav-links");

    if (toggle && nav) {

        toggle.setAttribute("aria-expanded", "false");

        toggle.addEventListener("click", function () {
            const isOpen = nav.classList.toggle("open");

            toggle.setAttribute("aria-expanded", String(isOpen));
            toggle.setAttribute(
                "aria-label",
                isOpen ? "Close navigation menu" : "Open navigation menu"
            );
        });

        // Close menu after selecting a navigation link
        nav.querySelectorAll("a").forEach(function (link) {
            link.addEventListener("click", function () {
                nav.classList.remove("open");
                toggle.setAttribute("aria-expanded", "false");
            });
        });

        // Close menu when clicking outside
        document.addEventListener("click", function (event) {
            if (
                nav.classList.contains("open") &&
                !nav.contains(event.target) &&
                !toggle.contains(event.target)
            ) {
                nav.classList.remove("open");
                toggle.setAttribute("aria-expanded", "false");
            }
        });

        // Close menu with Escape key
        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape") {
                nav.classList.remove("open");
                toggle.setAttribute("aria-expanded", "false");
            }
        });
    }

    // PASSWORD CONFIRMATION
    document.querySelectorAll("form").forEach(function (form) {

        form.addEventListener("submit", function (event) {

            const password = form.querySelector(
                'input[name="password"]'
            );

            const confirm = form.querySelector(
                'input[name="confirm_password"]'
            );

            if (
                password &&
                confirm &&
                password.value !== confirm.value
            ) {
                event.preventDefault();
                alert("Passwords do not match.");
                confirm.focus();
            }
        });
    });

    // PREVENT DOUBLE SUBMISSION
    document.querySelectorAll("form[data-prevent-double-submit]")
        .forEach(function (form) {

            form.addEventListener("submit", function () {

                const submitButton = form.querySelector(
                    'button[type="submit"], input[type="submit"]'
                );

                if (submitButton && form.checkValidity()) {
                    submitButton.disabled = true;
                    submitButton.textContent = "Please wait...";
                }
            });
        });

});

// SHOW / HIDE PASSWORD
// Available globally for HTML onclick handlers.
window.togglePassword = function (id, button) {

    const input = document.getElementById(id);

    if (!input || !button) {
        return;
    }

    const showPassword = input.type === "password";

    input.type = showPassword ? "text" : "password";

    button.textContent = showPassword ? "Hide" : "Show";

    button.setAttribute(
        "aria-label",
        showPassword ? "Hide password" : "Show password"
    );
};
