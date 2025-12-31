# Advanced UI Components - Quick Reference

Quick lookup guide for common tasks and code snippets.

## Component Reference

### Card Component

```twig
{% include 'components/advanced_card.html.twig' with {
    title: 'Card Title',
    subtitle: 'Optional subtitle',
    description: 'Card description',
    icon: '<svg>...</svg>',
    stats: [
        { label: 'Label', value: 'Value' }
    ],
    actions: [
        { id: 'action-id', label: 'Button', variant: 'primary' }
    ]
} %}
```

**Variants:** `primary` | `secondary`

**API:**
```javascript
controller.showLoading();           // Show skeleton
await controller.hideLoading(500);  // Hide skeleton after delay
```

---

### Modal Component

```twig
{% include 'components/advanced_modal.html.twig' with {
    title: 'Modal Title',
    subtitle: 'Optional subtitle',
    content: '<p>Content here</p>',
    actions: [
        { id: 'ok', label: 'OK', variant: 'primary' },
        { id: 'cancel', label: 'Cancel', dismiss: true }
    ]
} %}
```

**Variants:** `primary` | `secondary` | `danger`

**API:**
```javascript
controller.open();       // Open with animation
controller.close();      // Close with animation
```

**Events:**
```javascript
// Listen for action
document.addEventListener('advanced-modal:action', (e) => {
    console.log(e.detail.actionId);
});
```

---

### Form Component

```twig
{% include 'components/advanced_form.html.twig' with {
    title: 'Form Title',
    description: 'Form description',
    fields: [
        {
            name: 'field_name',
            label: 'Field Label',
            type: 'text|email|number|textarea|select|checkbox',
            validation: 'required|email|min:3|max:100|url|pattern:regex',
            placeholder: 'Placeholder text',
            hint: 'Helper text',
            required: true,
            rows: 4  // for textarea
        }
    ],
    submit_label: 'Submit',
    show_reset: true
} %}
```

**Field Types:**
- `text` - Text input
- `email` - Email input
- `number` - Number input
- `textarea` - Multiline text
- `select` - Dropdown (add `options: [{value, label}]`)
- `checkbox` - Checkbox

**Validation Rules:**
- `required` - Required field
- `email` - Valid email
- `min:3` - Minimum 3 characters
- `max:100` - Maximum 100 characters
- `url` - Valid URL
- `pattern:^[A-Z]+$` - Regex pattern

**Events:**
```javascript
document.addEventListener('advanced-form:submit', (e) => {
    const formData = e.detail.formData;
});
```

---

### Toast Notifications

```javascript
// Show
window.advancedToast.show({
    type: 'success|error|warning|info',
    title: 'Title',
    message: 'Message',
    duration: 5000,  // 0 = no auto-dismiss
    action: 'Button text',
    onAction: () => {},
    onDismiss: () => {}
});

// Shortcuts
window.advancedToast.success('Title', 'Message');
window.advancedToast.error('Title', 'Message');
window.advancedToast.warning('Title', 'Message');
window.advancedToast.info('Title', 'Message');

// Dismiss
window.advancedToast.dismiss(toastId);
window.advancedToast.dismissAll();
```

---

### Collapse Component

```twig
{% include 'components/advanced_collapse.html.twig' with {
    id: 'unique-id',
    title: 'Section Title',
    subtitle: 'Optional subtitle',
    text: 'Content goes here',
    action: { label: 'Learn More' }
} %}
```

**API:**
```javascript
controller.toggle();  // Toggle open/close
controller.expand();  // Open
controller.collapse(); // Close
```

**Events:**
```javascript
document.addEventListener('advanced-collapse:action', (e) => {
    console.log(e.detail.collapsed);
});
```

---

## Animation CSS Classes

### Quick Animations

```html
<!-- Slide in from right -->
<div class="animate-slideInRight">Content</div>

<!-- Slide down -->
<div class="animate-slideDown">Content</div>

<!-- Fade in -->
<div class="animate-fadeIn">Content</div>

<!-- Scale in -->
<div class="animate-scaleIn">Content</div>

<!-- Bounce -->
<div class="animate-bounce-subtle">Content</div>

<!-- Loading shimmer -->
<div class="animate-shimmer">Content</div>
```

### Hover Effects

```html
<!-- Lift on hover -->
<div class="hover-lift">Content</div>

<!-- Scale on hover -->
<div class="hover-scale">Content</div>

<!-- Press on click -->
<button class="active-press">Button</button>
```

### Transitions

```html
<!-- Smooth transitions -->
<div class="transition-smooth">Content</div>

<!-- Fast transitions -->
<div class="transition-fast">Content</div>

<!-- Slow transitions -->
<div class="transition-slow">Content</div>
```

---

## Common Patterns

### Confirmation Modal

```javascript
function confirm(message, onConfirm) {
    window.advancedToast.show({
        type: 'warning',
        title: message,
        action: 'Confirm',
        onAction: onConfirm,
        duration: 0  // Don't auto-dismiss
    });
}

// Usage
confirm('Delete item?', () => {
    fetch('/api/delete', { method: 'DELETE' });
});
```

### Form with Toast Feedback

```javascript
document.addEventListener('advanced-form:submit', async (e) => {
    const formData = e.detail.formData;
    try {
        const response = await fetch('/api/submit', {
            method: 'POST',
            body: formData
        });
        if (response.ok) {
            window.advancedToast.success('Success!');
        }
    } catch (error) {
        window.advancedToast.error('Error', error.message);
    }
});
```

### Card List with Actions

```twig
<div class="space-y-4">
    {% for item in items %}
        {% include 'components/advanced_card.html.twig' with {
            title: item.name,
            actions: [
                { id: 'edit', label: 'Edit', variant: 'primary' },
                { id: 'delete', label: 'Delete' }
            ]
        } %}
    {% endfor %}
</div>

<script>
document.addEventListener('advanced-card:action', async (e) => {
    const { actionId } = e.detail;
    const card = e.target.closest('[data-controller="advanced-card"]');
    
    if (actionId === 'delete') {
        const itemId = card.dataset.itemId;
        await fetch(`/api/items/${itemId}`, { method: 'DELETE' });
        card.remove();
    }
});
</script>
```

### FAQ Accordion

```twig
<div class="space-y-0 border rounded-lg divide-y">
    {% for faq in faqs %}
        {% include 'components/advanced_collapse.html.twig' with {
            id: 'faq-' ~ faq.id,
            title: faq.question,
            text: faq.answer
        } %}
    {% endfor %}
</div>
```

---

## CSS Variables

### Timing
```css
--duration-fast: 150ms
--duration-base: 200ms
--duration-slow: 300ms
--duration-slower: 400ms
```

### Easing
```css
--ease-linear
--ease-in
--ease-out
--ease-in-out
--ease-smooth
--ease-elastic
--ease-bounce-out
--animation-timing: cubic-bezier(0.4, 0, 0.2, 1)
```

### Custom Override
```html
<style>
:root {
    --duration-slow: 200ms;
    --animation-timing: cubic-bezier(0.34, 1.56, 0.64, 1);
}
</style>
```

---

## Tailwind Classes Quick Reference

### Spacing
```html
p-4      <!-- Padding -->
m-4      <!-- Margin -->
gap-4    <!-- Gap -->
space-y-4 <!-- Vertical gap -->
```

### Colors
```html
bg-blue-600     <!-- Blue background -->
text-gray-900   <!-- Dark text -->
border-gray-200 <!-- Light border -->
hover:bg-blue-700
focus:ring-2 focus:ring-blue-500
```

### Sizing
```html
w-full      <!-- Full width -->
h-10        <!-- Height -->
max-w-md    <!-- Max width -->
rounded-lg  <!-- Border radius -->
```

### Grid
```html
grid
grid-cols-1 md:grid-cols-2 lg:grid-cols-3
gap-6
```

---

## Stimulus Targets & Values

### Card
```javascript
// Targets
@targets ['skeleton', 'content', 'actionButton']

// Values
animationDurationValue
```

### Modal
```javascript
// Targets
@targets ['backdrop', 'modal', 'content']

// Values
openValue
```

### Form
```javascript
// Targets
@targets ['field', 'submitButton', 'submitText', 'loadingSpinner', 
          'errorMessage', 'formError', 'validationIcon']
```

### Toast
```javascript
// Targets
@targets ['container']
```

### Collapse
```javascript
// Targets
@targets ['trigger', 'content', 'indicator']

// Values
openValue
```

---

## Accessibility Checklist

- [ ] All interactive elements have `aria-label`
- [ ] Modals have `aria-modal="true"`
- [ ] Collapsible sections have `aria-expanded`
- [ ] Form fields have `aria-describedby`
- [ ] Focus indicators are visible
- [ ] Color is not the only indicator of state
- [ ] Keyboard navigation works
- [ ] Screen reader friendly

---

## Performance Tips

### Do ✓
```javascript
// Use CSS transforms
element.style.transform = 'translateY(-2px)';

// Debounce events
let timeout;
element.addEventListener('input', () => {
    clearTimeout(timeout);
    timeout = setTimeout(() => validate(), 300);
});

// Use requestAnimationFrame
requestAnimationFrame(() => animate());
```

### Don't ✗
```javascript
// Avoid animating dimensions
element.style.width = '100px';  // Bad

// Don't spam events
element.addEventListener('mousemove', updatePosition);  // Bad

// Don't force synchronous reflows
element.offsetHeight;  // Force reflow
```

---

## Debugging

### Check Stimulus Controller
```javascript
// Get controller for element
const controller = Stimulus.getControllerForElementAndIdentifier(element, 'advanced-card');

// Access targets
console.log(controller.contentTarget);

// Access values
console.log(controller.animationDurationValue);
```

### Check Toast
```javascript
// Verify toast is available
console.log(window.advancedToast);

// Test toast
window.advancedToast.success('Test', 'Toast is working');
```

### Inspect Animations
```javascript
// Get computed style
const style = window.getComputedStyle(element);
console.log(style.animation);
console.log(style.transition);
```

---

## File Locations

```
templates/components/
├── advanced_card.html.twig
├── advanced_modal.html.twig
├── advanced_form.html.twig
├── advanced_toast.html.twig
└── advanced_collapse.html.twig

assets/controllers/
├── advanced_card_controller.js
├── advanced_modal_controller.js
├── advanced_form_controller.js
├── advanced_toast_controller.js
└── advanced_collapse_controller.js

assets/styles/
└── advanced_components.css
```

---

## Related Documentation

- [Full Documentation](ADVANCED_COMPONENTS_DOCS.md)
- [Quick Start](ADVANCED_COMPONENTS_QUICKSTART.md)
- [Integration Guide](ADVANCED_COMPONENTS_INTEGRATION.md)
- [Summary](ADVANCED_COMPONENTS_SUMMARY.md)

---

**Version 1.0** | Last updated: December 2024
