// Advanced Form Component Controller
// Handles real-time validation, animated feedback, and form submission

import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        'field',
        'submitButton',
        'submitText',
        'loadingSpinner',
        'errorMessage',
        'formError',
        'validationIcon'
    ];

    connect() {
        this.validationRules = this.buildValidationRules();
        this.setupFieldListeners();
    }

    /**
     * Setup real-time validation listeners on all form fields
     * Provides instant feedback with animated error messages
     */
    setupFieldListeners() {
        this.fieldTargets.forEach(field => {
            // Validate on input with debouncing
            let timeout;
            field.addEventListener('input', () => {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    this.validateField(field);
                }, 300);
            });

            // Validate on blur immediately
            field.addEventListener('blur', () => {
                this.validateField(field);
            });

            // Show valid state on focus if previously valid
            field.addEventListener('focus', () => {
                if (field.dataset.validated === 'valid') {
                    field.classList.add('border-green-500');
                }
            });
        });
    }

    /**
     * Build validation rules from field attributes
     * Supports email, required, min, max, pattern, etc.
     */
    buildValidationRules() {
        const rules = {};

        this.fieldTargets.forEach(field => {
            const validation = field.dataset.validation;
            if (!validation) return;

            rules[field.name] = validation.split('|').map(rule => {
                const [name, ...params] = rule.split(':');
                return { name: name.trim(), params };
            });
        });

        return rules;
    }

    /**
     * Validate individual field with animated feedback
     * Returns true if valid, false otherwise
     */
    validateField(field) {
        const fieldName = field.name;
        const validation = field.dataset.validation;
        let errors = [];

        if (!validation) {
            this.clearFieldErrors(field);
            return true;
        }

        const rules = validation.split('|');

        for (const rule of rules) {
            const [ruleName, ...params] = rule.split(':');

            switch (ruleName.trim()) {
                case 'required':
                    if (!field.value.trim()) {
                        errors.push('This field is required');
                    }
                    break;

                case 'email':
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (field.value && !emailRegex.test(field.value)) {
                        errors.push('Please enter a valid email address');
                    }
                    break;

                case 'min':
                    const minLength = params[0];
                    if (field.value && field.value.length < minLength) {
                        errors.push(`Minimum ${minLength} characters required`);
                    }
                    break;

                case 'max':
                    const maxLength = params[0];
                    if (field.value && field.value.length > maxLength) {
                        errors.push(`Maximum ${maxLength} characters allowed`);
                    }
                    break;

                case 'pattern':
                    const pattern = new RegExp(params.join(':'));
                    if (field.value && !pattern.test(field.value)) {
                        errors.push('Invalid format');
                    }
                    break;

                case 'url':
                    try {
                        new URL(field.value);
                    } catch {
                        if (field.value) errors.push('Please enter a valid URL');
                    }
                    break;
            }
        }

        // Update field visual state
        if (errors.length === 0) {
            this.setFieldValid(field);
            return true;
        } else {
            this.setFieldInvalid(field, errors);
            return false;
        }
    }

    /**
     * Set field to valid state with animated checkmark
     * Demonstrates validation feedback animation
     */
    setFieldValid(field) {
        field.dataset.validated = 'valid';
        field.classList.remove('border-red-500', 'focus:ring-red-200');
        field.classList.add('border-green-500', 'focus:ring-green-200');

        // Show checkmark icon with fade animation
        const iconIndex = this.fieldTargets.indexOf(field);
        if (this.validationIconTargets[iconIndex]) {
            const icon = this.validationIconTargets[iconIndex];
            icon.style.opacity = '0';
            icon.style.display = 'flex';
            icon.offsetHeight; // Trigger reflow
            icon.style.opacity = '1';
            icon.style.transition = 'opacity 200ms ease-out';
        }

        this.clearFieldErrors(field);
    }

    /**
     * Set field to invalid state with error messages
     * Animated error message reveal
     */
    setFieldInvalid(field, errors) {
        field.dataset.validated = 'invalid';
        field.classList.remove('border-green-500', 'focus:ring-green-200');
        field.classList.add('border-red-500', 'focus:ring-red-200');

        // Hide checkmark icon
        const iconIndex = this.fieldTargets.indexOf(field);
        if (this.validationIconTargets[iconIndex]) {
            const icon = this.validationIconTargets[iconIndex];
            icon.style.opacity = '0';
            setTimeout(() => {
                icon.style.display = 'none';
            }, 200);
        }

        // Show error messages with animation
        const errorContainer = field.parentElement.querySelector(
            `#error_${field.name}`
        );

        if (errorContainer) {
            errorContainer.innerHTML = errors
                .map(
                    error => `
                    <div class="animate-[slideDown_0.2s_ease-out] text-red-600 text-xs font-medium">
                        ${this.escapeHtml(error)}
                    </div>
                `
                )
                .join('');

            errorContainer.style.maxHeight = errorContainer.scrollHeight + 'px';
        }
    }

    /**
     * Clear field errors with fade animation
     */
    clearFieldErrors(field) {
        const errorContainer = field.parentElement.querySelector(
            `#error_${field.name}`
        );

        if (errorContainer && errorContainer.innerHTML.trim()) {
            errorContainer.innerHTML = '';
            errorContainer.style.maxHeight = '0';
            errorContainer.style.transition = 'max-height 200ms ease-out';
        }
    }

    /**
     * Validate entire form before submission
     */
    validateForm() {
        let isValid = true;

        this.fieldTargets.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });

        return isValid;
    }

    /**
     * Handle form submission with loading animation
     * Demonstrates button loading state with spinner
     */
    async handleSubmit(event) {
        event.preventDefault();

        // Validate form
        if (!this.validateForm()) {
            this.showFormError('Please fix all errors before submitting');
            return;
        }

        // Start loading state
        this.setLoadingState(true);

        try {
            // Simulate form submission
            const formData = new FormData(this.element);
            
            // Dispatch custom event for actual submission handling
            const response = await this.dispatch('submit', {
                detail: { formData },
                cancelable: true
            });

            // Show success message if needed
            this.showFormError('Form submitted successfully!', 'success');
            
            // Reset form after short delay
            setTimeout(() => {
                this.element.reset();
                this.fieldTargets.forEach(field => {
                    field.classList.remove('border-green-500', 'border-red-500');
                    field.dataset.validated = '';
                });
                this.clearFormError();
            }, 500);

        } catch (error) {
            this.showFormError(error.message || 'An error occurred while submitting the form');
        } finally {
            this.setLoadingState(false);
        }
    }

    /**
     * Set button to loading state with spinner animation
     */
    setLoadingState(isLoading) {
        const button = this.submitButtonTarget;
        button.disabled = isLoading;

        if (isLoading) {
            this.submitTextTarget.style.opacity = '0';
            this.submitTextTarget.style.transition = 'opacity 200ms ease-out';
            setTimeout(() => {
                this.submitTextTarget.style.display = 'none';
                this.loadingSpinnerTarget.style.display = 'inline-block';
            }, 200);
        } else {
            this.loadingSpinnerTarget.style.display = 'none';
            this.submitTextTarget.style.display = 'inline';
            this.submitTextTarget.style.opacity = '0';
            this.submitTextTarget.offsetHeight;
            this.submitTextTarget.style.opacity = '1';
        }
    }

    /**
     * Show form-level error message with animation
     */
    showFormError(message, type = 'error') {
        const errorElement = this.formErrorTarget;
        errorElement.textContent = message;
        errorElement.className = `rounded-lg border p-4 text-sm transition-all duration-200 
            ${type === 'success' 
                ? 'border-green-200 bg-green-50 text-green-700' 
                : 'border-red-200 bg-red-50 text-red-700'
            }`;

        errorElement.style.display = 'block';
        errorElement.style.opacity = '0';
        errorElement.offsetHeight;
        errorElement.style.opacity = '1';
        errorElement.style.transition = 'opacity 200ms ease-out';
    }

    /**
     * Clear form error message
     */
    clearFormError() {
        const errorElement = this.formErrorTarget;
        if (errorElement.style.display !== 'none') {
            errorElement.style.opacity = '0';
            setTimeout(() => {
                errorElement.style.display = 'none';
            }, 200);
        }
    }

    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }
}
