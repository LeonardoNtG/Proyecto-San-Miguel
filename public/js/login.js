const password = document.getElementById("password");
const toggle = document.getElementById("togglePassword");

toggle.addEventListener("click", () => {

    if (password.type === "password") {

        password.type = "text";
        toggle.innerHTML = '<i class="fa-regular fa-eye-slash"></i>';

    } else {

        password.type = "password";
        toggle.innerHTML = '<i class="fa-regular fa-eye"></i>';

    }
    if (loginForm && btnSubmit) {
        loginForm.addEventListener('submit', function () {
            btnSubmit.disabled = true;
            btnText.textContent = 'Autenticando...';
            btnSpinner.classList.remove('d-none');
        });
    }
});
