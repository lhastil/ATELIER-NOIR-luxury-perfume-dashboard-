/**
 * Show or hide a password while typing it.
 *
 * Markup contract: a button[data-password-toggle] whose aria-controls names the
 * password input, holding a [data-password-toggle-show] (eye) and a
 * [data-password-toggle-hide] (eye with a line) icon. The button starts hidden
 * and is revealed here, so without JavaScript the field stays a plain password
 * field. Only the input's type changes; the typed value is never touched. Being a
 * real button, it works with Enter and Space as well as a click.
 */
export function initPasswordToggles(root = document) {
    root.querySelectorAll('[data-password-toggle]').forEach((button) => {
        const input = document.getElementById(button.getAttribute('aria-controls'));
        const showIcon = button.querySelector('[data-password-toggle-show]');
        const hideIcon = button.querySelector('[data-password-toggle-hide]');

        if (!input) {
            return;
        }

        button.hidden = false;

        button.addEventListener('click', () => {
            const reveal = input.type === 'password';

            input.type = reveal ? 'text' : 'password';
            button.setAttribute('aria-label', reveal ? 'Hide password' : 'Show password');
            showIcon?.toggleAttribute('hidden', reveal);
            hideIcon?.toggleAttribute('hidden', !reveal);
        });
    });
}
