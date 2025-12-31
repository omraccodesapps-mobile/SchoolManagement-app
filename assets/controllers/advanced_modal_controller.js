// Advanced Modal Component Controller
// Handles modal animations, backdrop interactions, and accessibility

import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['backdrop', 'modal', 'content'];
    static values = { open: Boolean };

    connect() {
        this.openValue = false;
        // Prevent body scroll when modal is open
        document.addEventListener('keydown', this.handleKeyDown.bind(this));
    }

    disconnect() {
        document.removeEventListener('keydown', this.handleKeyDown.bind(this));
        this.close();
    }

    /**
     * Open modal with smooth animations
     * Backdrop fades in, modal slides and fades in
     */
    open() {
        if (this.openValue) return;

        this.openValue = true;
        this.element.style.display = 'flex';
        this.element.offsetHeight; // Trigger reflow

        // Animate backdrop
        this.backdropTarget.style.opacity = '0';
        this.backdropTarget.offsetHeight;
        this.backdropTarget.style.opacity = '1';
        this.backdropTarget.style.transition = 'opacity 300ms ease-out';

        // Animate modal - scale and fade
        this.modalTarget.style.opacity = '0';
        this.modalTarget.style.transform = 'scale(0.95) translateY(20px)';
        this.modalTarget.offsetHeight;
        this.modalTarget.style.opacity = '1';
        this.modalTarget.style.transform = 'scale(1) translateY(0)';
        this.modalTarget.style.transition = 'all 300ms cubic-bezier(0.4, 0, 0.2, 1)';

        // Prevent scroll
        document.body.style.overflow = 'hidden';

        // Focus management
        setTimeout(() => {
            const closeButton = this.modalTarget.querySelector('[data-action*="close"]');
            if (closeButton) closeButton.focus();
        }, 300);
    }

    /**
     * Close modal with reverse animations
     * Modal scales down and fades out, backdrop fades out
     */
    close() {
        if (!this.openValue) return;

        this.openValue = false;

        // Animate modal out
        this.modalTarget.style.opacity = '0';
        this.modalTarget.style.transform = 'scale(0.95) translateY(20px)';
        this.modalTarget.style.transition = 'all 300ms cubic-bezier(0.4, 0, 0.2, 1)';

        // Animate backdrop out
        this.backdropTarget.style.opacity = '0';
        this.backdropTarget.style.transition = 'opacity 300ms ease-out';

        setTimeout(() => {
            this.element.style.display = 'none';
            document.body.style.overflow = '';

            // Reset transforms for next open
            this.modalTarget.style.transition = '';
            this.backdropTarget.style.transition = '';
        }, 300);
    }

    /**
     * Handle backdrop click to close modal
     * Only close if clicking directly on backdrop, not modal content
     */
    handleBackdropClick(event) {
        // Only close if clicking on the backdrop itself
        if (event.target === this.backdropTarget) {
            this.close();
        }
    }

    /**
     * Handle modal action buttons
     * Supports different action types with animations
     */
    handleAction(event) {
        const button = event.target.closest('[data-action="advanced-modal#handleAction"]');
        if (!button) return;

        const actionId = button.dataset.actionId;
        const isDismiss = button.dataset.dismiss === 'true';

        // Add click animation
        button.style.transform = 'scale(0.98)';
        button.style.transition = 'transform 100ms ease-out';

        setTimeout(() => {
            button.style.transform = 'scale(1)';
        }, 100);

        // Dispatch event for parent component
        this.dispatch('action', {
            detail: { actionId, isDismiss }
        });

        // Auto-close if dismiss button
        if (isDismiss) {
            setTimeout(() => this.close(), 300);
        }
    }

    /**
     * Keyboard navigation
     * ESC key closes the modal
     */
    handleKeyDown(event) {
        if (event.key === 'Escape' && this.openValue) {
            event.preventDefault();
            this.close();
        }
    }
}
