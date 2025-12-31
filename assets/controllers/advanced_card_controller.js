// Advanced Card Component Controller
// Handles card animations, loading states, and interactions

import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['skeleton', 'content', 'actionButton'];
    static values = { animationDuration: Number };

    connect() {
        // Initialize card state
        this.isLoading = false;
        this.setupHoverEffects();
    }

    /**
     * Setup hover effects with subtle animations
     * Demonstrates micro-interactions on hover state
     */
    setupHoverEffects() {
        const element = this.element;

        element.addEventListener('mouseenter', () => {
            // Subtle scale effect on hover
            this.element.style.transform = 'translateY(-2px)';
            this.element.style.transition = `transform ${this.animationDurationValue}ms cubic-bezier(0.4, 0, 0.2, 1)`;
        });

        element.addEventListener('mouseleave', () => {
            this.element.style.transform = 'translateY(0)';
        });
    }

    /**
     * Show loading skeleton state with fade animation
     * Demonstrates loading state management
     */
    async showLoading() {
        this.isLoading = true;

        // Fade out content
        this.contentTarget.style.opacity = '0';
        this.contentTarget.style.transition = `opacity ${this.animationDurationValue}ms ease-out`;

        setTimeout(() => {
            this.contentTarget.style.display = 'none';
            this.skeletonTarget.style.display = 'block';

            // Fade in skeleton
            this.skeletonTarget.style.opacity = '0';
            this.skeletonTarget.offsetHeight; // Trigger reflow
            this.skeletonTarget.style.opacity = '1';
            this.skeletonTarget.style.transition = `opacity ${this.animationDurationValue}ms ease-out`;
        }, this.animationDurationValue);
    }

    /**
     * Hide loading skeleton and show content
     * Demonstrates content reveal with animation
     */
    async hideLoading(duration = 500) {
        // Simulate loading duration
        await new Promise(resolve => setTimeout(resolve, duration));

        this.skeletonTarget.style.opacity = '0';

        setTimeout(() => {
            this.skeletonTarget.style.display = 'none';
            this.contentTarget.style.display = 'block';
            this.contentTarget.style.opacity = '0';
            this.contentTarget.offsetHeight; // Trigger reflow
            this.contentTarget.style.opacity = '1';
            this.contentTarget.style.transition = `opacity ${this.animationDurationValue}ms ease-out`;

            this.isLoading = false;
        }, this.animationDurationValue);
    }

    /**
     * Handle action button clicks
     * Demonstrates button interaction feedback
     */
    handleActionButtonClick(event) {
        const button = event.target.closest('[data-advanced-card-target="actionButton"]');
        if (!button) return;

        const actionId = button.dataset.actionId;

        // Add click animation
        button.style.transform = 'scale(0.95)';
        button.style.transition = `transform 100ms cubic-bezier(0.4, 0, 0.2, 1)`;

        setTimeout(() => {
            button.style.transform = 'scale(1)';
        }, 100);

        // Dispatch custom event for parent component
        this.dispatch('action', { detail: { actionId } });
    }
}
