{{-- Replaces the browser confirm() of wire:confirm (see resources/js/app.js). --}}
<div data-confirm-dialog
    x-data="{
        message: '',
        expected: null,
        typed: '',
        action: null,
        open(detail) {
            this.message = detail.message || @js(__('actions.confirm_default'));
            this.expected = detail.expected;
            this.typed = '';
            this.action = detail.action;
            $flux.modal('confirm-dialog').show();
            this.$nextTick(() => (this.$refs.typed ?? this.$refs.confirm)?.focus());
        },
        get canConfirm() {
            return this.expected === null || this.typed.trim() === this.expected;
        },
        confirm() {
            if (! this.canConfirm) {
                return;
            }

            const action = this.action;
            this.action = null;
            $flux.modal('confirm-dialog').close();
            action?.();
        },
    }"
    x-on:marche-confirm.window="open($event.detail)">
    <flux:modal name="confirm-dialog" class="w-full max-w-md" x-on:close="action = null">
        <form class="space-y-5" x-on:submit.prevent="confirm()">
            <div class="flex items-start gap-3">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                    <flux:icon name="exclamation-triangle" variant="mini" />
                </div>
                <div class="min-w-0 space-y-2">
                    <flux:heading size="lg">{{ __('actions.confirm_title') }}</flux:heading>
                    <p class="whitespace-pre-line text-sm text-zinc-700" x-text="message"></p>
                </div>
            </div>

            <div x-show="expected !== null" x-cloak data-confirm-prompt class="space-y-2">
                <p class="text-sm font-semibold" x-text="@js(__('actions.confirm_prompt', ['word' => '__WORD__'])).replace('__WORD__', expected ?? '')"></p>
                <input x-ref="typed" x-model="typed" type="text" autocomplete="off" autocapitalize="off"
                    class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-base shadow-xs focus:border-brand-green focus:outline-none focus:ring-2 focus:ring-brand-green/30"
                    x-bind:aria-label="@js(__('actions.confirm_prompt', ['word' => '__WORD__'])).replace('__WORD__', expected ?? '')">
            </div>

            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <flux:modal.close>
                    <flux:button type="button" variant="ghost" class="w-full sm:w-auto">{{ __('actions.cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" :loading="false" class="w-full sm:w-auto" x-ref="confirm" x-bind:disabled="! canConfirm">{{ __('actions.confirm') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
