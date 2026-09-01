function togglePassword(inputSelector, iconSelector) {
    const input = document.querySelector(inputSelector);
    const icon = document.querySelector(iconSelector);

    if (!input || !icon) {
        console.warn("togglePassword: Input or icon not found.");
        return;
    }

    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';

    icon.className = isPassword
        ? 'fa-solid fa-eye-slash'
        : 'fa-solid fa-eye';
}
