# Advanced UI Components - Integration Guide

Complete step-by-step instructions for integrating advanced components into your Symfony application.

## Prerequisites

- Symfony 6.0 or higher
- Stimulus 3.0 or higher
- Tailwind CSS 3.0 or higher
- Asset Mapper configured

## Installation Steps

### Step 1: File Organization

Create the required directory structure:

```bash
# Create component directories
mkdir -p templates/components
mkdir -p assets/controllers
mkdir -p assets/styles

# Files are already created, verify they exist:
ls templates/components/  # Should show .html.twig files
ls assets/controllers/    # Should show .js files
ls assets/styles/         # Should show .css files
```

### Step 2: Import CSS in Your Layout

**File:** `templates/base.html.twig`

```twig
<!DOCTYPE html>
<html lang="{{ app.request.locale }}">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{% block title %}My Application{% endblock %}</title>
        
        {# Import advanced components CSS #}
        <link rel="stylesheet" href="{{ asset('styles/advanced_components.css') }}">
        
        {# Your other stylesheets #}
        {% block stylesheets %}
            {{ encore_entry_link_tags('app') }}
        {% endblock %}
    </head>
    <body>
        {# Your page content #}
        {% block body %}
            {% block content %}{% endblock %}
        {% endblock %}
        
        {# Toast container - must be at bottom of body #}
        {% include 'components/advanced_toast.html.twig' %}
        
        {# Scripts #}
        {% block javascripts %}
            {{ stimulus_controller_attributes(this) }}
            {{ encore_entry_script_tags('app') }}
        {% endblock %}
    </body>
</html>
```

### Step 3: Configure Asset Mapper

**File:** `config/packages/asset_mapper.yaml`

Ensure your asset paths are configured:

```yaml
framework:
    asset_mapper:
        paths:
            assets/: /

when@dev:
    framework:
        asset_mapper:
            mismatched_version_strategy: ~
```

### Step 4: Verify Stimulus is Configured

**File:** `config/packages/ux_turbo.yaml` (or similar)

Ensure Stimulus is properly configured:

```yaml
symfony:
    ux:
        turbo:
            enabled: true

symfony:
    asset_mapper:
        enabled: true
```

### Step 5: Register Controllers

The controllers are auto-registered if using Stimulus 3.0+ with auto-discovery.

**Optional:** Manually register in your Stimulus bootstrap file:

**File:** `assets/bootstrap.js` (if you have a custom bootstrap)

```javascript
import { Application } from '@hotwired/stimulus';

// Import controllers
import AdvancedCardController from './controllers/advanced_card_controller.js';
import AdvancedModalController from './controllers/advanced_modal_controller.js';
import AdvancedFormController from './controllers/advanced_form_controller.js';
import AdvancedToastController from './controllers/advanced_toast_controller.js';
import AdvancedCollapseController from './controllers/advanced_collapse_controller.js';

const application = Application.start();

// Register controllers
application.register('advanced-card', AdvancedCardController);
application.register('advanced-modal', AdvancedModalController);
application.register('advanced-form', AdvancedFormController);
application.register('advanced-toast', AdvancedToastController);
application.register('advanced-collapse', AdvancedCollapseController);

// Export for use in other modules
export default application;
```

### Step 6: Create a Test Page

Create a test route to verify everything works:

**File:** `src/Controller/ComponentController.php`

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ComponentController extends AbstractController
{
    #[Route('/components/demo', name: 'app_components_demo')]
    public function demo(): Response
    {
        return $this->render('components/advanced_demo.html.twig');
    }

    #[Route('/components/test', name: 'app_components_test')]
    public function test(): Response
    {
        return $this->render('components/test.html.twig');
    }
}
```

### Step 7: Create Test Template

**File:** `templates/components/test.html.twig`

```twig
{% extends 'base.html.twig' %}

{% block title %}Component Test{% endblock %}

{% block content %}
    <div class="max-w-4xl mx-auto py-12 px-4">
        <h1 class="text-4xl font-bold mb-8">Advanced Components Test</h1>
        
        <!-- Test Card -->
        <section class="mb-12">
            <h2 class="text-2xl font-bold mb-6">Card Component</h2>
            {% include 'components/advanced_card.html.twig' with {
                title: 'Test Card',
                description: 'This is a test card component',
                stats: [
                    { label: 'Status', value: 'Active' }
                ],
                actions: [
                    { label: 'Click Me', variant: 'primary' }
                ]
            } %}
        </section>
        
        <!-- Test Form -->
        <section class="mb-12">
            <h2 class="text-2xl font-bold mb-6">Form Component</h2>
            {% include 'components/advanced_form.html.twig' with {
                title: 'Test Form',
                fields: [
                    {
                        name: 'test_name',
                        label: 'Name',
                        type: 'text',
                        validation: 'required|min:3',
                        required: true
                    }
                ],
                submit_label: 'Test Submit'
            } %}
        </section>
        
        <!-- Toast Test Button -->
        <section class="mb-12">
            <h2 class="text-2xl font-bold mb-6">Toast Notification</h2>
            <button 
                onclick="window.advancedToast.success('Success!', 'This is a test notification')"
                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
            >
                Show Toast
            </button>
        </section>
    </div>
{% endblock %}
```

## Verification Checklist

Run through these steps to verify installation:

```bash
# 1. Check files exist
test -f templates/components/advanced_card.html.twig && echo "✓ Card component exists"
test -f assets/controllers/advanced_card_controller.js && echo "✓ Card controller exists"
test -f assets/styles/advanced_components.css && echo "✓ Styles exist"

# 2. Check Symfony dependencies
grep -r "stimulus" composer.json && echo "✓ Stimulus installed"

# 3. Clear cache
php bin/console cache:clear

# 4. Test in browser
# Visit: http://localhost:8000/components/test
```

## Troubleshooting Installation

### Issue: Components not appearing

**Solution:**
1. Verify files are in correct directories
2. Clear browser cache (Ctrl+F5)
3. Check browser console for JS errors
4. Verify CSS is imported in base template

### Issue: Stimulus controllers not loading

**Solution:**
1. Verify `stimulus` package is installed
2. Check auto-discovery is enabled
3. Verify controller naming convention: `*_controller.js`
4. Check browser console for loading errors

### Issue: Animations not playing

**Solution:**
1. Check that `advanced_components.css` is imported
2. Verify browser supports CSS animations
3. Check for `prefers-reduced-motion` setting
4. Open DevTools and check animation timeline

### Issue: Toast not appearing

**Solution:**
1. Verify toast container is in base layout
2. Check that `advanced_toast_controller.js` is loaded
3. Verify `window.advancedToast` exists in console
4. Check z-index isn't hidden behind other elements

## Configuration Options

### Customize Animation Duration

Create a CSS override file:

**File:** `assets/styles/custom_animations.css`

```css
:root {
    --duration-fast: 100ms;      /* Faster animations */
    --duration-base: 150ms;
    --duration-slow: 250ms;
    --duration-slower: 350ms;
}
```

Then import it after the advanced components CSS:

```twig
<link rel="stylesheet" href="{{ asset('styles/advanced_components.css') }}">
<link rel="stylesheet" href="{{ asset('styles/custom_animations.css') }}">
```

### Customize Colors

Override Tailwind classes with your own CSS:

**File:** `assets/styles/custom_theme.css`

```css
:root {
    --color-primary: #3b82f6;
    --color-secondary: #8b5cf6;
    --color-success: #10b981;
    --color-error: #ef4444;
    --color-warning: #f59e0b;
}

.bg-primary {
    background-color: var(--color-primary);
}

.text-primary {
    color: var(--color-primary);
}

/* etc... */
```

### Disable Animations (for testing)

Create a test-specific CSS file:

**File:** `assets/styles/no_animations.css`

```css
@media (prefers-reduced-motion: no-preference) {
    * {
        animation: none !important;
        transition: none !important;
    }
}
```

Include only in development:

```twig
{% if app.debug %}
    <link rel="stylesheet" href="{{ asset('styles/no_animations.css') }}">
{% endif %}
```

## Usage in Routes

### Basic Example

**Controller:**
```php
#[Route('/contact', name: 'app_contact')]
public function contact(): Response
{
    return $this->render('contact/index.html.twig');
}
```

**Template:**
```twig
{% extends 'base.html.twig' %}

{% block content %}
    {% include 'components/advanced_form.html.twig' with {
        title: 'Contact Us',
        fields: [...],
        submit_label: 'Send'
    } %}
    
    <script>
    document.addEventListener('advanced-form:submit', async (e) => {
        const response = await fetch('{{ path("app_contact_submit") }}', {
            method: 'POST',
            body: e.detail.formData
        });
        
        if (response.ok) {
            window.advancedToast.success('Sent!');
        }
    });
    </script>
{% endblock %}
```

### With API Endpoints

**Controller:**
```php
#[Route('/api/items', name: 'api_items', methods: ['GET'])]
public function getItems(): JsonResponse
{
    // Your logic here
    return $this->json($items);
}

#[Route('/api/items/{id}', name: 'api_item_delete', methods: ['DELETE'])]
public function deleteItem(int $id): JsonResponse
{
    // Your logic here
    return $this->json(['success' => true]);
}
```

**Template:**
```twig
<div class="space-y-4">
    {% for item in items %}
        {% include 'components/advanced_card.html.twig' with {
            title: item.name,
            description: item.description,
            actions: [
                { id: 'delete', label: 'Delete', variant: 'secondary' }
            ]
        } %}
    {% endfor %}
</div>

<script>
document.addEventListener('advanced-card:action', async (e) => {
    if (e.detail.actionId === 'delete') {
        const itemId = e.target.dataset.itemId;
        const response = await fetch(`/api/items/${itemId}`, {
            method: 'DELETE'
        });
        
        if (response.ok) {
            window.advancedToast.success('Item deleted');
            e.target.remove();
        }
    }
});
</script>
```

## Security Considerations

### CSRF Protection

Ensure forms include CSRF tokens:

```twig
{% include 'components/advanced_form.html.twig' with {
    fields: [
        {
            name: '_csrf_token',
            type: 'hidden',
            value: csrf_token('form_name')
        },
        { name: 'email', type: 'email' }
    ]
} %}
```

### XSS Prevention

All form inputs are sanitized. Controllers escape HTML in error messages.

### Input Validation

Always validate on the server side:

```php
#[Route('/submit', name: 'app_submit', methods: ['POST'])]
public function submit(Request $request): Response
{
    $email = $request->request->get('email');
    
    // Server-side validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return $this->json(['error' => 'Invalid email'], 400);
    }
    
    // Process...
}
```

## Performance Optimization

### Lazy Load Heavy Components

```twig
{# Load modal content only when opened #}
<div data-controller="advanced-modal" data-lazy-load="true">
    {# Content loads on first open #}
</div>
```

### Minify CSS and JS

```bash
# Using Webpack Encore
npm run build

# Using esbuild
npm run build-prod
```

### Use Production Builds

```bash
APP_ENV=prod composer install
php bin/console assets:install
php bin/console cache:clear --env=prod
```

## Testing Components

### Unit Testing with PHPUnit

```php
public function testComponentRendering(): void
{
    $twig = $this->createTwig();
    
    $html = $twig->render('components/advanced_card.html.twig', [
        'title' => 'Test Card',
        'description' => 'Test description'
    ]);
    
    $this->assertStringContainsString('Test Card', $html);
    $this->assertStringContainsString('Test description', $html);
}
```

### E2E Testing with Dusk

```php
public function testCardAnimation(): void
{
    $this->browse(function (Browser $browser) {
        $browser->visit('/components/test')
                ->waitFor('[data-controller="advanced-card"]')
                ->assertVisible('[data-controller="advanced-card"]');
    });
}
```

## Deployment

### Production Checklist

- [ ] Set `APP_ENV=prod`
- [ ] Clear caches: `php bin/console cache:clear --env=prod`
- [ ] Install dependencies: `composer install --no-dev`
- [ ] Compile assets: `npm run build-prod`
- [ ] Verify animations work in production build
- [ ] Test all forms submit correctly
- [ ] Check toast notifications appear
- [ ] Verify responsive design on target devices

### Docker Integration

```dockerfile
FROM php:8.2-fpm

# Install dependencies
RUN apt-get update && apt-get install -y nodejs npm

# Copy application
COPY . /app

# Install Symfony dependencies
RUN composer install --no-dev --optimize-autoloader

# Install Node dependencies
RUN npm ci && npm run build-prod

# Compile assets
RUN php bin/console assets:install public

WORKDIR /app
```

---

## Next Steps

1. **Review** the [ADVANCED_COMPONENTS_QUICKSTART.md](ADVANCED_COMPONENTS_QUICKSTART.md) for usage examples
2. **Test** components by visiting `/components/test`
3. **Customize** colors and animations to match your brand
4. **Integrate** components into your existing pages
5. **Deploy** to production with confidence

---

**Need help?** See ADVANCED_COMPONENTS_DOCS.md for detailed troubleshooting and advanced features.
