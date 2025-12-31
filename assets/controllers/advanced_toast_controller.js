import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static values = { timeout: Number }

    connect() {
        this.timeoutValue = this.timeoutValue || 5000
        this.element.setAttribute('role', 'status')
        this.element.setAttribute('aria-live', 'polite')
        this.element.setAttribute('aria-atomic', 'true')
        this.element.classList.add('advanced-toast-container')
    }

    show(message, { variant = 'info', timeout = this.timeoutValue } = {}) {
        const toast = document.createElement('div')
        toast.className = `advanced-toast advanced-toast--${variant}`
        toast.setAttribute('role', 'alert')
        toast.setAttribute('aria-live', 'assertive')
        toast.innerHTML = `
            <div class="advanced-toast__content">${message}</div>
            <button type="button" class="advanced-toast__close" aria-label="Dismiss">&times;</button>
        `

        const closeBtn = toast.querySelector('.advanced-toast__close')
        closeBtn.addEventListener('click', () => this.dismissToast(toast))

        // Insert and animate
        this.element.appendChild(toast)
        // Force reflow then add visible class for CSS transition
        void toast.offsetWidth
        toast.classList.add('advanced-toast--visible')

        // Auto dismiss
        const to = setTimeout(() => this.dismissToast(toast), timeout)
        toast.dataset.timeoutId = to

        return toast
    }

    dismissToast(toast) {
        if (!toast) return
        // clear pending timeout
        if (toast.dataset.timeoutId) {
            clearTimeout(parseInt(toast.dataset.timeoutId, 10))
        }

        toast.classList.remove('advanced-toast--visible')
        toast.classList.add('advanced-toast--hiding')

        // Remove after CSS animation (300ms fallback)
        const removeAfter = parseInt(getComputedStyle(toast).transitionDuration || '0', 10) * 1000 || 300
        setTimeout(() => {
            if (toast.parentNode) toast.parentNode.removeChild(toast)
        }, removeAfter)
    }

    // Convenience API
    success(message, opts = {}) { return this.show(message, { ...opts, variant: 'success' }) }
    error(message, opts = {}) { return this.show(message, { ...opts, variant: 'error' }) }
    warning(message, opts = {}) { return this.show(message, { ...opts, variant: 'warning' }) }
    info(message, opts = {}) { return this.show(message, { ...opts, variant: 'info' }) }
}
