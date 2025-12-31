// Advanced Collapse Component Controller
// Handles smooth expand/collapse animations with accessibility

import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['trigger', 'content', 'indicator'];
    static values = { open: Boolean };

    connect() {
        this.openValue = false;
        this.contentHeight = 0;
        this.animationDuration = 300;

        // Set initial accessibility attributes
        this.updateAriaExpanded();
    }

    /**
     * Toggle collapse state with smooth animation
     * Demonstrates expand/collapse with height animation
     */
    toggle() {
        if (this.openValue) {
            this.collapse();
        } else {
            this.expand();
        }
    }

    /**
     * Expand collapsible section with smooth animation
     * Height animates from 0 to full height
     * Indicator icon rotates 180 degrees
     */
    expand() {
        if (this.openValue) return;

        this.openValue = true;
        const content = this.contentTarget;

        // Get the full height of content
        content.style.maxHeight = 'none'; // Temporarily disable to measure
        const fullHeight = content.offsetHeight;
        content.style.maxHeight = '0';
        content.style.opacity = '0';

        // Force reflow
        content.offsetHeight;

        // Animate to full height
        content.style.maxHeight = fullHeight + 'px';
        content.style.opacity = '1';
        content.style.transition = `
            max-height ${this.animationDuration}ms cubic-bezier(0.4, 0, 0.2, 1),
            opacity ${this.animationDuration}ms ease-out
        `;

        // Rotate indicator icon
        this.indicatorTarget.style.transform = 'rotate(180deg)';
        this.indicatorTarget.style.transition = `transform ${this.animationDuration}ms cubic-bezier(0.4, 0, 0.2, 1)`;

        // Update accessibility
        this.updateAriaExpanded();

        // Clean up transition after animation completes
        setTimeout(() => {
            content.style.transition = '';
            content.style.maxHeight = 'none';
        }, this.animationDuration);
    }

    /**
     * Collapse collapsible section with smooth animation
     * Height animates from full height to 0
     * Indicator icon rotates back to 0 degrees
     */
    collapse() {
        if (!this.openValue) return;

        this.openValue = false;
        const content = this.contentTarget;

        // Get current height
        const fullHeight = content.offsetHeight;

        // Force reflow to apply transition
        content.style.maxHeight = 'none';
        content.offsetHeight;

        // Animate to zero height
        content.style.maxHeight = fullHeight + 'px';
        content.style.transition = `
            max-height ${this.animationDuration}ms cubic-bezier(0.4, 0, 0.2, 1),
            opacity ${this.animationDuration}ms ease-out
        `;
        content.offsetHeight; // Force reflow
        content.style.maxHeight = '0';
        content.style.opacity = '0';

        // Rotate indicator icon back
        this.indicatorTarget.style.transform = 'rotate(0deg)';
        this.indicatorTarget.style.transition = `transform ${this.animationDuration}ms cubic-bezier(0.4, 0, 0.2, 1)`;

        // Update accessibility
        this.updateAriaExpanded();

        // Clean up transition after animation
        setTimeout(() => {
            content.style.transition = '';
        }, this.animationDuration);
    }

    /**
     * Handle action button clicks within the content
     */
    handleAction(event) {
        const button = event.target.closest('[data-action="advanced-collapse#handleAction"]');
        if (!button) return;

        // Add click animation
        button.style.transform = 'scale(0.98)';
        button.style.transition = 'transform 100ms ease-out';

        setTimeout(() => {
            button.style.transform = 'scale(1)';
        }, 100);

        // Dispatch custom event for parent handling
        this.dispatch('action', { detail: { collapsed: !this.openValue } });
    }

    /**
     * Update ARIA attributes for accessibility
     * Screen readers will announce expanded/collapsed state
     */
    updateAriaExpanded() {
        this.triggerTarget.setAttribute(
            'aria-expanded',
            this.openValue.toString()
        );
    }
}
