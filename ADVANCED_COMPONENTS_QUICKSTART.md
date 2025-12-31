# Advanced UI Components - Quick Start Guide

## 5-Minute Setup

### Step 1: Add Components to Your Layout

In your base Twig template (`templates/base.html.twig`), include the toast container:

```twig
<!DOCTYPE html>
<html>
<head>
    {# ... other head content ... #}
    <link rel="stylesheet" href="{{ asset('styles/advanced_components.css') }}">
</head>
<body>
    {# Your page content #}
    {% block content %}{% endblock %}
    
    {# Add toast container at the end #}
    {% include 'components/advanced_toast.html.twig' %}
    
    {# Scripts #}
    {{ stimulus_controller_attributes(this) }}
</body>
</html>
```

### Step 2: Use Components in Your Templates

#### Example 1: Display a Card

```twig
{% include 'components/advanced_card.html.twig' with {
    title: 'Welcome',
    description: 'This is a modern card component',
    actions: [
        { label: 'Click Me', variant: 'primary' }
    ]
} %}
```

#### Example 2: Show a Modal

```twig
{# Trigger button #}
<button onclick="document.querySelector('[data-controller=advanced-modal]').querySelector('[data-action*=open]')?.click()">
    Open Modal
</button>

{# Modal component #}
{% include 'components/advanced_modal.html.twig' with {
    title: 'Confirm Action',
    content: '<p>Are you sure you want to proceed?</p>',
    actions: [
        { label: 'Confirm', variant: 'primary', id: 'confirm' },
        { label: 'Cancel', variant: 'secondary', dismiss: true }
    ]
} %}
```

#### Example 3: Create a Form

```twig
{% include 'components/advanced_form.html.twig' with {
    title: 'Contact Us',
    fields: [
        {
            name: 'name',
            label: 'Your Name',
            type: 'text',
            validation: 'required|min:3',
            placeholder: 'John Doe',
            required: true
        },
        {
            name: 'email',
            label: 'Email Address',
            type: 'email',
            validation: 'required|email',
            placeholder: 'john@example.com',
            required: true
        },
        {
            name: 'message',
            label: 'Message',
            type: 'textarea',
            rows: 5,
            validation: 'required|max:1000',
            placeholder: 'Your message...'
        }
    ],
    submit_label: 'Send Message',
    show_reset: true
} %}
```

#### Example 4: Collapsible Sections

```twig
{% include 'components/advanced_collapse.html.twig' with {
    id: 'faq-1',
    title: 'How do I get started?',
    subtitle: 'Setup and installation',
    text: 'Follow the quick start guide above to get up and running in minutes.'
} %}

{% include 'components/advanced_collapse.html.twig' with {
    id: 'faq-2',
    title: 'Is it mobile-friendly?',
    subtitle: 'Responsive design',
    text: 'Yes! All components are fully responsive and work great on mobile, tablet, and desktop.'
} %}
```

### Step 3: Use JavaScript API

Show notifications with a single line:

```javascript
// Success notification
window.advancedToast.success('Success!', 'Your changes have been saved');

// Error notification
window.advancedToast.error('Error!', 'Something went wrong');

// Warning notification
window.advancedToast.warning('Warning', 'Please review before proceeding');

// Info notification
window.advancedToast.info('Information', 'Here is some helpful info');
```

## Common Use Cases

### 1. Save with Confirmation

```html
<button onclick="confirmAction('Are you sure?', () => {
    fetch('/api/save', { method: 'POST' })
        .then(() => window.advancedToast.success('Saved!'))
        .catch(() => window.advancedToast.error('Error!'));
})">
    Save
</button>

<script>
function confirmAction(message, callback) {
    window.advancedToast.show({
        type: 'warning',
        title: message,
        action: 'Confirm',
        onAction: callback,
        duration: 0 // Don't auto-dismiss
    });
}
</script>
```

### 2. Form Submission with Validation

```twig
{% include 'components/advanced_form.html.twig' with {
    fields: [
        { name: 'email', type: 'email', validation: 'required|email' }
    ]
} %}

<script>
document.addEventListener('advanced-form:submit', async (e) => {
    const formData = e.detail.formData;
    try {
        const response = await fetch('/api/subscribe', {
            method: 'POST',
            body: formData
        });
        if (response.ok) {
            window.advancedToast.success('Subscribed!', 'Check your email');
        }
    } catch (error) {
        window.advancedToast.error('Error', error.message);
    }
});
</script>
```

### 3. Dynamic List with Actions

```twig
<div class="space-y-4">
    {% for item in items %}
        {% include 'components/advanced_card.html.twig' with {
            title: item.name,
            description: item.description,
            actions: [
                { id: 'edit', label: 'Edit', variant: 'primary' },
                { id: 'delete', label: 'Delete', variant: 'secondary' }
            ]
        } %}
    {% endfor %}
</div>

<script>
document.addEventListener('advanced-card:action', (e) => {
    const { actionId } = e.detail;
    if (actionId === 'delete') {
        window.advancedToast.warning('Item deleted', 'This action cannot be undone');
    }
});
</script>
```

### 4. FAQ Accordion

```twig
<div class="space-y-0 border rounded-lg">
    {% for faq in faqs %}
        {% include 'components/advanced_collapse.html.twig' with {
            id: 'faq-' ~ loop.index,
            title: faq.question,
            text: faq.answer
        } %}
    {% endfor %}
</div>
```

## Animation Customization

### Change Animation Speed

All animations use CSS variables. Override them globally:

```html
<style>
:root {
    --duration-fast: 100ms;      /* Faster animations */
    --duration-base: 150ms;
    --duration-slow: 250ms;
    --duration-slower: 350ms;
}
</style>
```

### Add Custom Easing

```html
<style>
:root {
    --animation-timing: cubic-bezier(0.34, 1.56, 0.64, 1); /* Bouncy */
}
</style>
```

### Disable Animations

For testing or accessibility:

```html
<style>
@media (prefers-reduced-motion: reduce) {
    * {
        animation: none !important;
        transition: none !important;
    }
}
</style>
```

## Responsive Behavior

All components automatically adapt to screen size:

```
Mobile (<640px)    - Compact layout, smaller padding
Tablet (640-1024px) - Balanced layout
Desktop (1024px+)   - Full layout with spacious design
```

No additional code needed - just use the components as-is!

## Troubleshooting

### Toast not showing?
1. Ensure `advanced_toast.html.twig` is included in your layout
2. Check that `window.advancedToast` is available in browser console
3. Verify z-index CSS isn't hidden

### Form validation not working?
1. Add `data-validation` attribute to input fields
2. Ensure `advanced_form_controller.js` is loaded
3. Check browser console for errors

### Modal not animating?
1. Verify `advanced_components.css` is imported
2. Check that CSS animations are enabled (not in prefers-reduced-motion)
3. Ensure z-index isn't conflicting with other elements

## Next Steps

1. **Customize Colors**: Update Tailwind colors in your config
2. **Create Variants**: Modify templates for different styles
3. **Add More Components**: Create custom components based on these patterns
4. **Integrate with Backend**: Hook up forms to your API endpoints

## Real-World Example

Complete contact form with validation and submission:

```twig
{% include 'components/advanced_form.html.twig' with {
    title: 'Get In Touch',
    description: 'We\'ll get back to you as soon as possible',
    fields: [
        {
            name: 'name',
            label: 'Name',
            type: 'text',
            validation: 'required|min:2',
            required: true,
            placeholder: 'Your name'
        },
        {
            name: 'email',
            label: 'Email',
            type: 'email',
            validation: 'required|email',
            required: true,
            placeholder: 'your@email.com'
        },
        {
            name: 'phone',
            label: 'Phone (optional)',
            type: 'text',
            placeholder: '+1 (555) 123-4567'
        },
        {
            name: 'subject',
            label: 'Subject',
            type: 'text',
            validation: 'required|min:3',
            required: true,
            placeholder: 'How can we help?'
        },
        {
            name: 'message',
            label: 'Message',
            type: 'textarea',
            validation: 'required|min:10|max:5000',
            required: true,
            rows: 6,
            placeholder: 'Tell us more...'
        }
    ],
    submit_label: 'Send Message',
    show_reset: true
} %}

<script>
document.addEventListener('advanced-form:submit', async (e) => {
    const form = e.target;
    const formData = e.detail.formData;
    
    try {
        const response = await fetch('{{ path("contact.submit") }}', {
            method: 'POST',
            body: formData
        });
        
        if (response.ok) {
            form.reset();
            window.advancedToast.success(
                'Message Sent!',
                'Thank you for contacting us. We\'ll be in touch soon.'
            );
        } else {
            window.advancedToast.error(
                'Error',
                'Failed to send message. Please try again.'
            );
        }
    } catch (error) {
        window.advancedToast.error(
            'Error',
            error.message || 'An unexpected error occurred'
        );
    }
});
</script>
```

---

**That's it!** You now have a complete, production-ready UI component system. Start using them in your templates right away! 🚀
