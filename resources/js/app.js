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

    // A disabled button is left out of the submitted data, so a named submit
    // button (e.g. name="action" value="approve") would lose its value.
    // Carry it in a hidden input before disabling the button.
    if (button.name) {
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = button.name;
        hidden.value = button.value;
        hidden.setAttribute('data-submitter-value', '');
        form.appendChild(hidden);
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

    // Drop the hidden copies of the submit button values added above.
    document.querySelectorAll('input[data-submitter-value]').forEach((input) => input.remove());
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

// Heart (favorite) buttons: toggles without reloading the page. The form also
// works with JavaScript off. If anything goes wrong here (expired session,
// unverified e-mail, network), the form is submitted normally, so the usual
// redirects (login, e-mail verification) take over.
document.addEventListener('submit', (event) => {
    const form = event.target;

    if (! (form instanceof HTMLFormElement) || ! form.hasAttribute('data-favorite-form')) {
        return;
    }

    event.preventDefault();

    const button = form.querySelector('button');
    const method = form.querySelector('input[name="_method"]');

    if (! button || button.disabled) {
        return;
    }

    button.disabled = true;

    fetch(form.action, {
        method: 'POST',
        body: new FormData(form), // carries _token and, when removing, _method=DELETE
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })
        .then(async (response) => {
            if (! response.ok) {
                throw new Error('Request failed: ' + response.status);
            }

            const { favorited } = await response.json();

            button.setAttribute('aria-pressed', favorited ? 'true' : 'false');

            if (button.dataset.labelOn) {
                button.setAttribute('aria-label', favorited ? button.dataset.labelOn : button.dataset.labelOff);
            }

            // A disabled input is not sent: next time the form adds or removes accordingly.
            if (method) {
                method.disabled = ! favorited;
            }

            button.disabled = false;
        })
        .catch(() => {
            // form.submit() does not fire the submit event again, so there is no loop.
            form.submit();
        });
});

// CPF mask for inputs marked with data-mask="cpf": 999.999.999-99.
document.addEventListener('input', (event) => {
    const input = event.target;

    if (! (input instanceof HTMLInputElement) || input.dataset.mask !== 'cpf') {
        return;
    }

    const digits = input.value.replace(/\D/g, '').slice(0, 11);

    input.value = digits
        .replace(/^(\d{3})(\d)/, '$1.$2')
        .replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3')
        .replace(/^(\d{3})\.(\d{3})\.(\d{3})(\d)/, '$1.$2.$3-$4');
});
// Lazy Cloudflare Turnstile for forms marked with <div data-turnstile-lazy>
// (the footer "Fale conosco" form, present on every page). The challenge
// script is downloaded only when the visitor first focuses the form, so
// ordinary page views stay light. Forms with a regular .cf-turnstile widget
// are not affected.
const renderLazyTurnstile = (container) => {
    if (container.dataset.rendered) {
        return;
    }

    container.dataset.rendered = '1';

    window.turnstile.render(container, {
        sitekey: container.dataset.sitekey,
        language: 'pt-br',
        size: 'flexible',
    });
};

let turnstileLoading = null;

const loadTurnstile = () => {
    if (window.turnstile) {
        return Promise.resolve();
    }

    turnstileLoading ??= new Promise((resolve, reject) => {
        const script = document.createElement('script');

        script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
        script.async = true;
        script.onload = resolve;
        script.onerror = reject;
        document.head.appendChild(script);
    });

    return turnstileLoading;
};

document.addEventListener('focusin', (event) => {
    const form = event.target instanceof Element ? event.target.closest('form') : null;
    const container = form?.querySelector('[data-turnstile-lazy]');

    if (! container || container.dataset.rendered) {
        return;
    }

    loadTurnstile().then(() => renderLazyTurnstile(container)).catch(() => {
        // Script blocked or offline: the server answers with a clear error on submit.
    });
});

// A form that came back with errors already has the visitor's attention.
document.querySelectorAll('[data-turnstile-lazy][data-autoload]').forEach((container) => {
    loadTurnstile().then(() => renderLazyTurnstile(container)).catch(() => {});
});
