// While a form is being sent, disable its submit button and show a spinner
// (styled in app.css via [data-loading]). This blocks double submissions and
// gives feedback on slow connections.
// Opt out on a form with the data-no-loading attribute, e.g. when the response
// is a file download and the page never reloads.
document.addEventListener('submit', (event) => {
    const form = event.target;

    if (! (form instanceof HTMLFormElement) || form.hasAttribute('data-no-loading')) {
        return;
    }

    const button = event.submitter
        ?? form.querySelector('button[type="submit"], button:not([type])');

    if (! button || button.disabled) {
        return;
    }

    button.setAttribute('data-loading', '');
    button.setAttribute('aria-busy', 'true');
    button.disabled = true;
});

// Coming back with the browser's back button can restore the page with the
// button still disabled, so reset it.
window.addEventListener('pageshow', (event) => {
    if (! event.persisted) {
        return;
    }

    document.querySelectorAll('button[data-loading]').forEach((button) => {
        button.removeAttribute('data-loading');
        button.removeAttribute('aria-busy');
        button.disabled = false;
    });
});

// Brazilian phone mask for inputs marked with data-mask="phone".
// Landline: (99) 9999-9999. Mobile: (99) 99999-9999.
document.addEventListener('input', (event) => {
    const input = event.target;

    if (! (input instanceof HTMLInputElement) || input.dataset.mask !== 'phone') {
        return;
    }

    const digits = input.value.replace(/\D/g, '').slice(0, 11);

    if (digits.length > 10) {
        input.value = digits.replace(/^(\d{2})(\d{5})(\d{0,4}).*/, '($1) $2-$3');
    } else if (digits.length > 6) {
        input.value = digits.replace(/^(\d{2})(\d{4})(\d{0,4}).*/, '($1) $2-$3');
    } else if (digits.length > 2) {
        input.value = digits.replace(/^(\d{2})(\d{0,5}).*/, '($1) $2');
    } else if (digits.length > 0) {
        // No closing parenthesis yet, so backspace keeps working at this point.
        input.value = '(' + digits;
    } else {
        input.value = '';
    }
});

// Image preview for inputs with data-image-preview="<img id>".
// Optional attributes:
//   data-max-kb       refuses a bigger file right away, before any upload.
//   data-error-target id of an element where the refusal message is shown.
document.addEventListener('change', (event) => {
    const input = event.target;

    if (! (input instanceof HTMLInputElement) || ! input.dataset.imagePreview) {
        return;
    }

    const errorBox = input.dataset.errorTarget
        ? document.getElementById(input.dataset.errorTarget)
        : null;

    const showError = (message) => {
        if (! errorBox) {
            return;
        }

        errorBox.textContent = message ?? '';
        errorBox.classList.toggle('hidden', ! message);
    };

    // A new selection always starts without the previous message.
    showError(null);

    const file = input.files?.[0];

    if (! file) {
        return;
    }

    if (! file.type.startsWith('image/')) {
        input.value = '';
        showError('O arquivo precisa ser uma imagem JPG, PNG ou WebP.');

        return;
    }

    const maxKb = Number(input.dataset.maxKb || 0);

    if (maxKb && file.size > maxKb * 1024) {
        const limit = maxKb >= 1024 ? (maxKb / 1024) + ' MB' : maxKb + ' KB';
        const size  = (file.size / 1024 / 1024).toFixed(1).replace('.', ',');

        input.value = '';
        showError('A imagem escolhida tem ' + size + ' MB. O máximo permitido é ' + limit + '.');

        return;
    }

    const preview = document.getElementById(input.dataset.imagePreview);

    if (! preview) {
        return;
    }

    const url = URL.createObjectURL(file);

    preview.onload = () => URL.revokeObjectURL(url);
    preview.src = url;
    // The <img> starts hidden when the user has no photo yet.
    preview.classList.remove('hidden');
});