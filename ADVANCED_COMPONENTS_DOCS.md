# Advanced UI Components Documentation

A complete guide to implementing advanced, modern UI components with Symfony, Stimulus, and Tailwind CSS.

## Table of Contents

1. [Overview](#overview)
2. [Components](#components)
3. [Installation](#installation)
4. [Usage](#usage)
5. [Customization](#customization)
6. [Animation Specifications](#animation-specifications)
7. [Accessibility](#accessibility)
8. [Performance Tips](#performance-tips)

## Overview

This component library provides production-ready, modern UI components with:

- ✨ Smooth animations and transitions (200-400ms)
- 🎯 Micro-interactions on all interactive elements
- ♿ Full WCAG accessibility support
- 📱 Responsive design for mobile, tablet, and desktop
- ⚡ 60fps animations using CSS transforms
- 🎨 Tailwind CSS styling
- 🔧 Stimulus controllers for interactivity
- 📦 Reusable Twig components

## Components

### 1. Advanced Card Component

**File:** `templates/components/advanced_card.html.twig`
**Controller:** `assets/controllers/advanced_card_controller.js`

A modern card component with hover effects, loading states, and action buttons.

#### Features:
- Hover animations with scale and color transitions
- Loading skeleton with animated pulse
- Icon rotation and scaling
- Action button interactions
- Stats/metrics display

#### Usage:

```twig
{% include 'components/advanced_card.html.twig' with {
    title: 'Card Title',
    subtitle: 'Subtitle text',
    description: 'Card description',
    icon: '<svg>...</svg>',
    stats: [
        { label: 'Status', value: 'Active' },
        { label: 'Score', value: '9.2' }
    ],
    actions: [
        { id: 'edit', label: 'Edit', variant: 'primary' },
        { id: 'delete', label: 'Delete', variant: 'secondary' }
    ]
} %}
```

#### Stimulus Controller Methods:

```javascript
// Show loading skeleton
await controller.showLoading();

// Hide loading and show content
await controller.hideLoading(duration);

// Listen for action events
document.addEventListener('advanced-card:action', (e) => {
    console.log(e.detail.actionId);
});
```

### 2. Advanced Modal Component

**File:** `templates/components/advanced_modal.html.twig`
**Controller:** `assets/controllers/advanced_modal_controller.js`

Accessible modal dialog with backdrop blur and smooth animations.

#### Features:
- Scale and fade animations
- Backdrop blur effect
- Keyboard navigation (ESC to close)
- Focus management
- Modal action buttons

#### Usage:

```twig
{% include 'components/advanced_modal.html.twig' with {
    title: 'Modal Title',
    subtitle: 'Modal subtitle',
    content: 'Modal content here',
    actions: [
        { id: 'confirm', label: 'Confirm', variant: 'primary' },
        { id: 'cancel', label: 'Cancel', variant: 'secondary', dismiss: true }
    ]
} %}
```

#### JavaScript Control:

```javascript
const modal = document.querySelector('[data-controller="advanced-modal"]');
const controller = modal.__stimulus__.application.getControllerForElementAndIdentifier(modal, 'advanced-modal');

// Open modal
controller.open();

// Close modal
controller.close();

// Listen for actions
document.addEventListener('advanced-modal:action', (e) => {
    console.log(e.detail.actionId);
    console.log(e.detail.isDismiss);
});
```

### 3. Advanced Form Component

**File:** `templates/components/advanced_form.html.twig`
**Controller:** `assets/controllers/advanced_form_controller.js`

Form with real-time validation, animated feedback, and loading states.

#### Features:
- Real-time field validation with debouncing
- Animated error messages
- Success state with checkmark icon
- Loading spinner on submit
- Multiple input types (text, email, textarea, select, checkbox)

#### Validation Rules:

```
required           Required field
email              Valid email format
min:3              Minimum 3 characters
max:500            Maximum 500 characters
pattern:regex      Custom regex pattern
url                Valid URL format
```

#### Usage:

```twig
{% include 'components/advanced_form.html.twig' with {
    title: 'Contact Form',
    description: 'Please fill in all fields',
    fields: [
        {
            name: 'name',
            label: 'Full Name',
            type: 'text',
            placeholder: 'John Doe',
            validation: 'required|min:3',
            hint: 'Minimum 3 characters',
            required: true
        },
        {
            name: 'email',
            label: 'Email Address',
            type: 'email',
            validation: 'required|email',
            required: true
        },
        {
            name: 'message',
            label: 'Message',
            type: 'textarea',
            rows: 4,
            validation: 'max:500'
        }
    ],
    submit_label: 'Send',
    show_reset: true
} %}
```

#### Stimulus Events:

```javascript
// Listen for form submission
document.addEventListener('advanced-form:submit', (e) => {
    const formData = e.detail.formData;
    // Handle submission
});
```

### 4. Toast Notifications

**File:** `templates/components/advanced_toast.html.twig`
**Controller:** `assets/controllers/advanced_toast_controller.js`

Slide-in toast notifications with auto-dismiss and progress bar.

#### Features:
- Multiple toast types (success, error, warning, info)
- Slide-in and slide-out animations
- Auto-dismiss with animated progress bar
- Custom action buttons
- Manual dismiss

#### Usage:

```html
<!-- Add toast container to your layout -->
{% include 'components/advanced_toast.html.twig' %}
```

#### JavaScript API:

```javascript
// Show generic toast
window.advancedToast.show({
    type: 'info',
    title: 'Notification Title',
    message: 'Notification message',
    duration: 5000
});

// Shorthand methods
window.advancedToast.success('Success!', 'Operation completed');
window.advancedToast.error('Error!', 'Something went wrong');
window.advancedToast.warning('Warning', 'Please be careful');
window.advancedToast.info('Info', 'Here is some information');

// With custom action
window.advancedToast.success('Item saved', 'View details', {
    action: 'View',
    onAction: () => {
        // Handle action
    },
    onDismiss: () => {
        // Handle dismiss
    }
});

// Dismiss specific toast
window.advancedToast.dismiss(toastId);

// Dismiss all toasts
window.advancedToast.dismissAll();
```

### 5. Collapsible Section

**File:** `templates/components/advanced_collapse.html.twig`
**Controller:** `assets/controllers/advanced_collapse_controller.js`

Smooth expand/collapse animations with accessibility features.

#### Features:
- Height-based animation
- Icon rotation
- ARIA attributes for accessibility
- Optional action button in footer

#### Usage:

```twig
{% include 'components/advanced_collapse.html.twig' with {
    id: 'faq-1',
    title: 'Frequently Asked Question',
    subtitle: 'Click to expand',
    text: 'Answer to the question goes here',
    action: {
        label: 'Learn More'
    }
} %}
```

#### Stimulus Controller Methods:

```javascript
const collapse = document.querySelector('[data-controller="advanced-collapse"]');
const controller = collapse.__stimulus__.application.getControllerForElementAndIdentifier(collapse, 'advanced-collapse');

// Toggle state
controller.toggle();

// Expand
controller.expand();

// Collapse
controller.collapse();

// Listen for actions
document.addEventListener('advanced-collapse:action', (e) => {
    console.log(e.detail.collapsed);
});
```

## Installation

### 1. Copy Files

Copy the provided files to your Symfony project:

```bash
# Components
cp -r templates/components/ templates/

# Controllers
cp -r assets/controllers/ assets/

# Styles
cp assets/styles/advanced_components.css assets/styles/
```

### 2. Update Asset Mapper

Ensure your Symfony project has Asset Mapper configured. In `config/packages/asset_mapper.yaml`:

```yaml
framework:
    asset_mapper:
        paths:
            assets/: /
```

### 3. Import Stimulus Controllers

In your `assets/controllers.json`:

```json
{
    "controllers": {
        "advanced-card": {
            "enabled": true,
            "webpackMode": "eager"
        },
        "advanced-modal": {
            "enabled": true,
            "webpackMode": "eager"
        },
        "advanced-form": {
            "enabled": true,
            "webpackMode": "eager"
        },
        "advanced-toast": {
            "enabled": true,
            "webpackMode": "eager"
        },
        "advanced-collapse": {
            "enabled": true,
            "webpackMode": "eager"
        }
    }
}
```

### 4. Import Styles

In your main stylesheet or Twig template:

```html
<link rel="stylesheet" href="{{ asset('styles/advanced_components.css') }}">
```

## Usage

### In Twig Templates

```twig
<!-- Basic card -->
{% include 'components/advanced_card.html.twig' with {
    title: 'My Card',
    description: 'Card description'
} %}

<!-- Modal with content -->
{% include 'components/advanced_modal.html.twig' with {
    title: 'Are you sure?',
    content: '<p>This action cannot be undone.</p>',
    actions: [
        { label: 'Confirm', variant: 'danger', id: 'confirm' },
        { label: 'Cancel', variant: 'secondary', dismiss: true }
    ]
} %}

<!-- Form with validation -->
{% include 'components/advanced_form.html.twig' with {
    fields: [...],
    title: 'Contact Us'
} %}
```

### In JavaScript

```javascript
// Show toast notification
window.advancedToast.success('Success!', 'Your changes have been saved');

// Control modals
const modal = document.querySelector('[data-controller="advanced-modal"]');
const controller = Stimulus.getControllerForElementAndIdentifier(modal, 'advanced-modal');
controller.open();

// Listen for form submission
document.addEventListener('advanced-form:submit', async (e) => {
    const formData = e.detail.formData;
    const response = await fetch('/api/submit', {
        method: 'POST',
        body: formData
    });
    // Handle response
});
```

## Customization

### Animation Duration

Change default animation durations in CSS:

```css
:root {
    --duration-fast: 150ms;
    --duration-base: 200ms;
    --duration-slow: 300ms;
    --duration-slower: 400ms;
}
```

### Easing Functions

Customize easing functions:

```css
:root {
    --ease-in-out: cubic-bezier(0.4, 0, 0.2, 1);
    --ease-smooth: cubic-bezier(0.25, 0.46, 0.45, 0.94);
}
```

### Colors

Override Tailwind colors in your configuration:

```js
// tailwind.config.js
module.exports = {
    theme: {
        extend: {
            colors: {
                primary: '#3b82f6',
                secondary: '#8b5cf6'
            }
        }
    }
}
```

### Component Variants

Create component variants by modifying the Twig templates:

```twig
{# Dark variant #}
<div class="bg-gray-900 text-white">
    {# Component content #}
</div>

{# Compact variant #}
<div class="p-3 gap-2">
    {# Component content with smaller padding #}
</div>
```

## Animation Specifications

### Timing

- **Fast interactions:** 150ms (hover states, icon changes)
- **Standard transitions:** 200-300ms (modal open/close, collapse)
- **Slower animations:** 400ms (page transitions, complex effects)

### Easing Functions

```css
ease-linear          Linear progression
ease-in              Slow start, fast end
ease-out             Fast start, slow end
ease-in-out          Slow at both ends (default)
cubic-bezier(...)    Custom curves
```

### Performance Tips

Use CSS transforms instead of positioning:

```css
/* Good - GPU accelerated */
transform: translateX(10px) scale(1.1);

/* Avoid - CPU intensive */
left: 10px;
width: 110%;
```

## Accessibility

### ARIA Labels

All components include proper ARIA labels:

```html
<button aria-label="Close modal" aria-expanded="false">
    ×
</button>
```

### Keyboard Navigation

- **Tab:** Navigate between focusable elements
- **Enter/Space:** Activate buttons and toggles
- **Escape:** Close modals

### Color Contrast

All text meets WCAG AA standards (4.5:1 minimum contrast ratio).

### Motion Preferences

Respect user's motion preferences:

```css
@media (prefers-reduced-motion: reduce) {
    * {
        animation-duration: 0.01ms !important;
        transition-duration: 0.01ms !important;
    }
}
```

## Performance Tips

### 1. Use CSS Transforms

```javascript
// Good - GPU accelerated
element.style.transform = 'translateX(10px)';

// Avoid
element.style.left = '10px';
```

### 2. Debounce Event Listeners

```javascript
let timeout;
element.addEventListener('input', () => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        validateField();
    }, 300);
});
```

### 3. Use requestAnimationFrame

```javascript
function animate() {
    // Update animation frame
    requestAnimationFrame(animate);
}
animate();
```

### 4. Lazy Load Heavy Components

```javascript
// Load modal content only when opened
controller.addEventListener('open', async () => {
    const content = await fetch('/modal-content');
    // Update DOM
});
```

### 5. Optimize Media Queries

Use Tailwind's responsive modifiers:

```html
<!-- Hide on mobile, show on tablet+ -->
<div class="hidden md:block"></div>
```

## Troubleshooting

### Animations Not Playing

1. Check if CSS file is imported
2. Verify `prefers-reduced-motion` is not enabled
3. Check browser dev tools for animation timing

### Form Validation Not Working

1. Ensure field `data-validation` attribute is set
2. Check console for JavaScript errors
3. Verify Stimulus controller is loaded

### Toast Not Appearing

1. Ensure toast container is rendered
2. Check `window.advancedToast` is available
3. Verify z-index is not being overridden

### Modal Focus Issues

1. Ensure focus management code runs after DOM update
2. Check for conflicting focus event listeners
3. Verify escape key handler is attached

## Browser Support

- Chrome/Edge 90+
- Firefox 88+
- Safari 14+
- Mobile browsers (iOS Safari 14+, Chrome Mobile)

## License

These components are provided as-is for use in your Symfony projects.

---

For questions or issues, refer to the component source code or create custom variants based on your needs.
