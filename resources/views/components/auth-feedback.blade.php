@php
    $rawError = null;
    foreach ($errors->getBags() as $bag) {
        if ($bag->any()) {
            $rawError = (string) $bag->first();
            break;
        }
    }

    $status = session('status');
    $feedback = null;

    if ($rawError !== null && $rawError !== '') {
        $lower = strtolower($rawError);

        if (str_contains($lower, 'suspended')) {
            $feedback = [
                'variant' => 'danger',
                'icon' => 'no-symbol',
                'heading' => __('Print shop suspended'),
                'body' => $rawError,
            ];
        } elseif (str_contains($lower, 'too many')) {
            $feedback = [
                'variant' => 'warning',
                'icon' => 'clock',
                'heading' => __('Too many attempts'),
                'body' => $rawError,
            ];
        } elseif ($rawError === __('auth.failed') || str_contains($lower, 'do not match our records')) {
            $feedback = [
                'variant' => 'danger',
                'icon' => 'exclamation-circle',
                'heading' => __('Account not found or wrong password'),
                'body' => __('We couldn’t find an account matching that email and password. Double-check your details, or create an account below.'),
            ];
        } elseif (str_contains($lower, 'already been taken')) {
            $feedback = [
                'variant' => 'warning',
                'icon' => 'exclamation-triangle',
                'heading' => __('Email already registered'),
                'body' => __('An account with this email already exists. Try signing in instead, or reset your password.'),
            ];
        } elseif (str_contains($lower, 'expired') || str_contains($lower, 'token') || str_contains($lower, 'invitation')) {
            $feedback = [
                'variant' => 'danger',
                'icon' => 'exclamation-circle',
                'heading' => __('Link invalid or expired'),
                'body' => $rawError,
            ];
        } elseif ($errors->has('code') || $errors->has('recovery_code')) {
            $feedback = [
                'variant' => 'danger',
                'icon' => 'shield-exclamation',
                'heading' => __('Verification failed'),
                'body' => $rawError,
            ];
        } elseif ($errors->has('password')) {
            $feedback = [
                'variant' => 'danger',
                'icon' => 'exclamation-circle',
                'heading' => __('Check your password'),
                'body' => $rawError,
            ];
        } else {
            $feedback = [
                'variant' => 'danger',
                'icon' => 'exclamation-circle',
                'heading' => __('Please check your details'),
                'body' => $rawError,
            ];
        }
    } elseif (is_string($status) && $status !== '') {
        if ($status === 'verification-link-sent') {
            $feedback = [
                'variant' => 'success',
                'icon' => 'check-circle',
                'heading' => __('Verification email sent'),
                'body' => __('A new verification link has been sent to the email address you provided during registration.'),
            ];
        } else {
            $feedback = [
                'variant' => 'success',
                'icon' => 'check-circle',
                'heading' => __('Success'),
                'body' => $status,
            ];
        }
    }
@endphp

@if ($feedback)
    @php
        $toastPayload = [
            'duration' => 6000,
            'slots' => [
                'heading' => $feedback['heading'],
                'text' => $feedback['body'],
            ],
            'dataset' => [
                'variant' => $feedback['variant'],
            ],
        ];
    @endphp
    <div
        role="{{ $feedback['variant'] === 'success' ? 'status' : 'alert' }}"
        aria-live="polite"
        data-test="auth-feedback"
        x-data="{ toast: @js($toastPayload) }"
        x-init="$nextTick(() => window.requestAnimationFrame(() => document.dispatchEvent(new CustomEvent('toast-show', { detail: toast }))))"
    >
        <flux:callout
            :variant="$feedback['variant']"
            :icon="$feedback['icon']"
            :heading="$feedback['heading']"
            :text="$feedback['body']"
        />
    </div>
@endif
