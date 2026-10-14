/**
 * wire:confirm opens the site dialog (resources/views/partials/confirm-dialog.blade.php)
 * instead of the browser confirm() and prompt(). Livewire asks for confirmation
 * through el.__livewire_confirm(action): the action only runs once confirmed.
 * Pages without the dialog (sign in, print) keep the browser one.
 */
document.addEventListener('livewire:init', () => {
    window.Livewire.hook('directive.init', ({ el, directive }) => {
        if (directive.value !== 'confirm') {
            return;
        }

        const browserConfirm = el.__livewire_confirm;
        let message = directive.expression.replaceAll('\\n', '\n');
        let expected = null;

        if (directive.modifiers.includes('prompt')) {
            [message, expected] = message.split('|');
        }

        el.__livewire_confirm = (action, instead) => {
            if (! document.querySelector('[data-confirm-dialog]')) {
                return browserConfirm?.(action, instead);
            }

            window.dispatchEvent(new CustomEvent('marche-confirm', {
                detail: { message, expected: expected?.trim() ?? null, action },
            }));
        };
    });
});
