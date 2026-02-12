# GitHub Copilot Instructions for Pressbooks

## Project Overview

**Pressbooks** is an open source book publishing tool built on a WordPress multisite platform. It outputs books in multiple formats: PDF, EPUB, web, and XML using a theming/templating system driven by CSS.

- **WordPress Plugin**: Requires WordPress 6.8.3+ multisite (network-activated)
- **PHP**: 8.3+ with `Pressbooks\` namespace
- **License**: GPL v3.0 or later

## Code Style & Standards

### General Guidelines
- Follow **Pressbooks coding standards** (`pressbooks/coding-standards`)
- Use **tabs** for indentation (`.editorconfig`)
- Use **PHPDoc** for all function documentation
- Write descriptive variable and function names
- Keep functions focused and single-purpose

### Commit Messages
Use **Conventional Commits** format:
- `feat: Add new feature`
- `fix: Fix bug description`
- `docs: Update documentation`
- `test: Add or update tests`
- `chore: Routine task`

## PHP Development

### Namespace & Structure
- All code uses `Pressbooks\` namespace
- Classes in `inc/` follow `class-*.php` naming convention
- Use PSR-4 autoloading via `hm-autoloader.php`

### WordPress Context
- **Always** consider WordPress multisite context
- This plugin only works on multisite installations
- Use WordPress functions and APIs appropriately
- Custom post types: `chapter`, `front-matter`, `back-matter`, `part`, `glossary`

### WordPress Patterns
```php
// Actions and filters
add_action( 'init', [ $this, 'methodName' ] );
add_filter( 'the_content', [ $this, 'filterContent' ] );

// Escaping output (always escape!)
echo esc_html( $text );
echo esc_attr( $attribute );
echo esc_url( $url );

// Sanitizing input
$clean = sanitize_text_field( $_POST['field'] );
$clean = sanitize_email( $_POST['email'] );

// Database queries
global $wpdb;
$results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->posts} WHERE ID = %d", $id ) );
```

### Service Container
Always use `\Pressbooks\Container` for service resolution:

```php
use Pressbooks\Container;

// Get service from container
$service = Container::get( 'ServiceName' );

// Example
$blade = Container::get( 'Blade' );
```

### Laravel Components
Pressbooks uses several Illuminate (Laravel) components:
- **Container**: Dependency injection
- **Database**: Query builder and Eloquent
- **View**: Blade templating
- **Events**: Event system
- **Filesystem**: File operations
- **HTTP**: HTTP client

Example using Illuminate components:
```php
use Illuminate\Database\Capsule\Manager as DB;

// Query builder
$results = DB::table( 'table_name' )
    ->where( 'column', '=', 'value' )
    ->get();
```

## Testing

### PHPUnit Tests
- Extend `\WP_UnitTestCase` for all tests
- Place tests in `tests/` directory
- Prefix test files with `test-*`
- Follow WordPress testing conventions

Example test structure:
```php
<?php

class MyTest extends \WP_UnitTestCase {
    
    public function setUp(): void {
        parent::setUp();
        // Setup code
    }
    
    public function test_my_feature() {
        // Arrange
        $input = 'test';
        
        // Act
        $result = my_function( $input );
        
        // Assert
        $this->assertEquals( 'expected', $result );
    }
}
```

## JavaScript Development

### Syntax & Style
- Use **ES6+** syntax
- jQuery is available globally as `$` and `jQuery`
- Use modern JavaScript features (arrow functions, const/let, template literals)

### Available Libraries
- jQuery (global)
- AlpineJS (for reactive components)
- TinyMCE (editor)
- Select2/SelectWoo
- Isotope Layout
- CountUp.js

Example modern JavaScript:
```javascript
// ES6 syntax
const processData = ( data ) => {
    return data.map( item => item.value );
};

// jQuery usage
jQuery( document ).ready( ( $ ) => {
    $( '.selector' ).on( 'click', function() {
        // Handle click
    } );
} );
```

## SCSS Development

### Style Guidelines
- **SCSS** is the preferred stylesheet language
- Use nesting appropriately
- Use variables for colors, spacing, etc.
- Follow BEM or similar naming conventions when appropriate

Example SCSS:
```scss
$primary-color: #1a1a1a;
$spacing-unit: 1rem;

.component {
    color: $primary-color;
    padding: $spacing-unit;
    
    &__element {
        margin-bottom: $spacing-unit / 2;
    }
    
    &--modifier {
        background-color: lighten( $primary-color, 10% );
    }
}
```

## Export Formats

When suggesting code for export modules, be aware of different output formats:

### PDF Export
- Generated via **PrinceXML** or **DocRaptor**
- CSS must be print-friendly
- Consider page breaks, margins, headers/footers
- Use `@page` rules appropriately

### EPUB Export
- EPUB 3 compliant
- Limited CSS support
- Must validate against EPUB standards
- Consider e-reader compatibility

### Web Output
- Standard HTML/CSS
- Full modern CSS support
- Responsive design considerations
- Accessibility important

### XML Formats
- Various XML flavours (XHTML, HTMLBook, etc.)
- No styling applied
- Structure and semantics critical

## Security Best Practices

### Input Validation
```php
// Sanitize all input
$user_input = sanitize_text_field( $_POST['input'] );
$email = sanitize_email( $_POST['email'] );
$url = esc_url_raw( $_POST['url'] );

// Validate before processing
if ( ! email_is_valid( $email ) ) {
    return new WP_Error( 'invalid_email', __( 'Invalid email address', 'pressbooks' ) );
}
```

### Output Escaping
```php
// Always escape output
<h1><?php echo esc_html( $title ); ?></h1>
<a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $text ); ?></a>
<input type="text" value="<?php echo esc_attr( $value ); ?>" />
```

### Nonces
```php
// Generate nonce
wp_nonce_field( 'action_name', 'nonce_field_name' );

// Verify nonce
if ( ! wp_verify_nonce( $_POST['nonce_field_name'], 'action_name' ) ) {
    wp_die( 'Security check failed' );
}
```

### Capabilities
```php
// Check user capabilities
if ( ! current_user_can( 'edit_posts' ) ) {
    wp_die( 'Insufficient permissions' );
}
```

## HTML Sanitization

Pressbooks uses the **HtmLawed** library for HTML sanitization:

```php
use Pressbooks\Sanitize\Sanitize;

$sanitized_html = Sanitize::sanitize_xml_attribute( $input );
$clean_html = Sanitize::clean_html( $html );
```

## Common Patterns

### Custom Post Types
```php
// Register custom post type
register_post_type( 'custom_type', [
    'public' => true,
    'label'  => __( 'Custom Type', 'pressbooks' ),
    'supports' => [ 'title', 'editor', 'author' ],
] );
```

### REST API Endpoints
```php
// Register REST route
register_rest_route( 'pressbooks/v2', '/endpoint', [
    'methods' => 'GET',
    'callback' => [ $this, 'handle_request' ],
    'permission_callback' => [ $this, 'check_permissions' ],
] );
```

### Metadata
```php
// Get/update post meta
$value = get_post_meta( $post_id, 'meta_key', true );
update_post_meta( $post_id, 'meta_key', $value );

// Get/update options
$option = get_option( 'option_name' );
update_option( 'option_name', $value );
```

## File Structure

### Where to Add Code

- **Core classes**: `inc/` with appropriate subdirectory
- **Admin code**: `inc/admin/`
- **API endpoints**: `inc/api/`
- **Templates**: `templates/`
- **JavaScript**: `assets/src/scripts/`
- **SCSS**: `assets/src/styles/`
- **Tests**: `tests/` (prefix with `test-`)

### Files NOT to Edit

- ❌ `assets/dist/` (compiled files)
- ❌ `vendor/` (Composer dependencies)
- ❌ `node_modules/` (npm dependencies)
- ❌ `symbionts/` (bundled dependencies)

## Development Resources

- **Developer Docs**: https://pressbooks.org/dev-docs/
- **Coding Standards**: https://pressbooks.org/dev-guides/coding-standards/
- **User Docs**: https://pressbooks.org/user-docs/
- **Community**: https://pressbooks.community
- **GitHub**: https://github.com/pressbooks/pressbooks

## Quick Commands

```bash
# PHP
composer install        # Install dependencies
composer test           # Run PHPUnit tests
composer standards      # Check coding standards
composer fix            # Auto-fix coding standards

# JavaScript/CSS
npm install             # Install dependencies
npm run build           # Build for production
npm run watch           # Watch for changes
npm run lint:scripts    # Lint JavaScript
npm run lint:fix-scripts # Fix JavaScript
npm run lint:styles     # Lint SCSS
npm run lint:fix-styles # Fix SCSS
```

## Tips for Copilot

### When Writing PHP
1. Always use the `Pressbooks\` namespace
2. Consider multisite context
3. Use WordPress functions appropriately
4. Escape output, sanitize input
5. Add PHPDoc comments
6. Follow WordPress coding standards

### When Writing Tests
1. Extend `\WP_UnitTestCase`
2. Place in `tests/` directory
3. Prefix filename with `test-`
4. Use WordPress test helper functions
5. Test in multisite context

### When Writing JavaScript
1. Use ES6+ syntax
2. jQuery is available globally
3. Follow ESLint rules
4. Consider browser compatibility
5. Use const/let instead of var

### When Writing SCSS
1. Use SCSS features (variables, nesting, mixins)
2. Follow Stylelint rules
3. Be aware of export format limitations
4. Use BEM-like naming when appropriate

---

**Remember**: Pressbooks is a WordPress multisite plugin focused on book publishing. Always consider the book publishing workflow, multiple export formats, and WordPress multisite architecture when suggesting code.
