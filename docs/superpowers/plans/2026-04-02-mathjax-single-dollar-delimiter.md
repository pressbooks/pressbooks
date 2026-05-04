# MathJax Single Dollar Sign Delimiter Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add an opt-in setting to the MathJax admin page that enables `$...$` as an inline LaTeX delimiter in webbooks, EPUB, and PDF exports.

**Architecture:** Extend the existing `pb_mathjax` WordPress option with a `use_single_dollar` boolean. Conditionally apply the delimiter in three places within `MathJax::addHeaders()` (client-side config), `MathJax::replaceLatexDelimitersOnExports()` (export pipeline), and `MathJax::sectionHasMath()` (content detection). Add a checkbox to the admin template.

**Tech Stack:** PHP 8.3+, WordPress Multisite, MathJax v3, Blade templates, PHPUnit/WP_UnitTestCase

---

## File Map

| File | Change Type | What Changes |
|------|-------------|--------------|
| `inc/class-mathjax.php` | Modify | `$defaultOptions`, `getOptions()`, `saveOptions()`, `addHeaders()`, `replaceLatexDelimitersOnExports()`, `sectionHasMath()` |
| `templates/admin/mathjax.blade.php` | Modify | Add checkbox row; conditionally show `$...$` in syntax reference |
| `tests/test-mathjax.php` | Modify | Add 13 new test methods covering all changed behaviour |

---

## Task 1: Extend options — default, get, save

**Files:**
- Modify: `inc/class-mathjax.php` (lines ~35-37, ~192-214, ~350-359)
- Test: `tests/test-mathjax.php`

### Step 1: Write the failing tests

Add these three test methods to `tests/test-mathjax.php` inside `MathjaxTest`:

```php
public function testSingleDollarOptionDefault() {
    $options = $this->mathjax->getOptions();
    $this->assertFalse( $options['use_single_dollar'] );
}

public function testSingleDollarOptionSave() {
    $_POST = [
        'pb-mathjax-nonce' => wp_create_nonce( 'save' ),
        'fg'               => '000000',
        'use_single_dollar' => '1',
    ];
    $this->mathjax->saveOptions();
    $options = $this->mathjax->getOptions();
    $this->assertTrue( $options['use_single_dollar'] );
}

public function testSingleDollarOptionSaveUnchecked() {
    // First enable it
    $_POST = [
        'pb-mathjax-nonce'  => wp_create_nonce( 'save' ),
        'fg'                => '000000',
        'use_single_dollar' => '1',
    ];
    $this->mathjax->saveOptions();

    // Then save without the checkbox (unchecked = absent from POST)
    $_POST = [
        'pb-mathjax-nonce' => wp_create_nonce( 'save' ),
        'fg'               => '000000',
    ];
    $this->mathjax->saveOptions();
    $options = $this->mathjax->getOptions();
    $this->assertFalse( $options['use_single_dollar'] );
}
```

- [ ] Paste the three test methods into `tests/test-mathjax.php`, inside the `MathjaxTest` class, after the existing `testOptions()` method.

### Step 2: Run tests to confirm they fail

```bash
vendor/bin/phpunit --configuration phpunit.xml --filter testSingleDollarOption tests/test-mathjax.php
```

Expected: 3 FAILs — `use_single_dollar` key does not exist in options yet.

- [ ] Run the command above and confirm failures.

### Step 3: Implement the option changes in `inc/class-mathjax.php`

**3a. Extend `$defaultOptions`** (around line 35):

```php
// Before:
private $defaultOptions = [
    'fg' => '000000',
];

// After:
private $defaultOptions = [
    'fg'               => '000000',
    'use_single_dollar' => false,
];
```

**3b. Extend `getOptions()`** (around line 353):

```php
// Before:
public function getOptions() {
    $options = get_option( self::OPTION, [] );
    $fg = trim( $options['fg'] ?? $this->defaultOptions['fg'] );
    return [
        'fg' => $fg,
    ];
}

// After:
public function getOptions() {
    $options = get_option( self::OPTION, [] );
    $fg = trim( $options['fg'] ?? $this->defaultOptions['fg'] );
    $use_single_dollar = (bool) ( $options['use_single_dollar'] ?? $this->defaultOptions['use_single_dollar'] );

    /**
     * Override whether single dollar sign delimiters are enabled.
     *
     * @since  [next version]
     * @param  bool $use_single_dollar
     * @return bool
     */
    $use_single_dollar = (bool) apply_filters( 'pb_mathjax_use_single_dollar', $use_single_dollar );

    return [
        'fg'               => $fg,
        'use_single_dollar' => $use_single_dollar,
    ];
}
```

**3c. Extend `saveOptions()`** (around line 192). Replace the `$options` array construction:

```php
// Before:
$options = [
    'fg' => $fg,
];

// After:
$use_single_dollar = isset( $_POST['use_single_dollar'] ) && '1' === $_POST['use_single_dollar'];

$options = [
    'fg'               => $fg,
    'use_single_dollar' => $use_single_dollar,
];
```

- [ ] Apply all three changes to `inc/class-mathjax.php`.

### Step 4: Run tests to confirm they pass

```bash
vendor/bin/phpunit --configuration phpunit.xml --filter testSingleDollarOption tests/test-mathjax.php
```

Expected: 3 PASSes.

- [ ] Run and confirm.

### Step 5: Run full MathJax test suite to confirm no regressions

```bash
vendor/bin/phpunit --configuration phpunit.xml tests/test-mathjax.php
```

Expected: All existing tests still pass.

- [ ] Run and confirm.

### Step 6: Commit

```bash
git add inc/class-mathjax.php tests/test-mathjax.php
git commit -m "feat: add use_single_dollar option to pb_mathjax settings"
```

- [ ] Commit.

---

## Task 2: Content detection — `sectionHasMath()`

**Files:**
- Modify: `inc/class-mathjax.php` (lines ~222-258)
- Test: `tests/test-mathjax.php`

### Step 1: Write the failing tests

Add these methods to `tests/test-mathjax.php`:

```php
public function testSectionHasMathSingleDollarEnabled() {
    // Enable the option
    update_option( MathJax::OPTION, [ 'fg' => '000000', 'use_single_dollar' => true ] );

    $new_post = [
        'post_title'   => 'Test Chapter: ' . wp_rand(),
        'post_type'    => 'chapter',
        'post_status'  => 'published',
        'post_content' => 'Euler\'s formula $e^{i\pi}+1=0$ is beautiful.',
    ];
    $pid             = $this->factory()->post->create_object( $new_post );
    $GLOBALS['post'] = get_post( $pid );
    $this->assertTrue( $this->mathjax->sectionHasMath() );
}

public function testSectionHasMathSingleDollarDisabled() {
    // Explicitly disable the option (default)
    update_option( MathJax::OPTION, [ 'fg' => '000000', 'use_single_dollar' => false ] );

    $new_post = [
        'post_title'   => 'Test Chapter: ' . wp_rand(),
        'post_type'    => 'chapter',
        'post_status'  => 'published',
        'post_content' => 'Euler\'s formula $e^{i\pi}+1=0$ is beautiful.',
    ];
    $pid             = $this->factory()->post->create_object( $new_post );
    $GLOBALS['post'] = get_post( $pid );
    $this->assertFalse( $this->mathjax->sectionHasMath() );
}

public function testSectionHasMathCurrencyNotMath() {
    // Enable the option — currency must NOT trigger detection
    update_option( MathJax::OPTION, [ 'fg' => '000000', 'use_single_dollar' => true ] );

    $new_post = [
        'post_title'   => 'Test Chapter: ' . wp_rand(),
        'post_type'    => 'chapter',
        'post_status'  => 'published',
        'post_content' => 'We earned $ 5,000 today and $ 4,000 yesterday.',
    ];
    $pid             = $this->factory()->post->create_object( $new_post );
    $GLOBALS['post'] = get_post( $pid );
    $this->assertFalse( $this->mathjax->sectionHasMath() );
}
```

- [ ] Add the three test methods to `tests/test-mathjax.php`.

### Step 2: Run tests to confirm they fail

```bash
vendor/bin/phpunit --configuration phpunit.xml --filter "testSectionHasMathSingleDollar|testSectionHasMathCurrency" tests/test-mathjax.php
```

Expected: FAILs — `sectionHasMath()` does not check the option yet.

- [ ] Run and confirm failures.

### Step 3: Implement the detection change

In `inc/class-mathjax.php`, `sectionHasMath()`, add after the existing `$has_math` preg_match (around line 255):

```php
// Before (end of method):
$has_math = (bool) preg_match( '/(?:\\\\\[|\\\\\]|\\\\\(|\\\\\)|\$\$|\$latex\s+[^$]+\$|\$\s+[^$]+\s+\$)/', $content );
$this->sectionHasMath[ $id ] = $has_math;
return $has_math;

// After:
$has_math = (bool) preg_match( '/(?:\\\\\[|\\\\\]|\\\\\(|\\\\\)|\$\$|\$latex\s+[^$]+\$|\$\s+[^$]+\s+\$)/', $content );

// Check for single-dollar inline math if opt-in is enabled
if ( ! $has_math ) {
    $options = $this->getOptions();
    if ( $options['use_single_dollar'] ) {
        $has_math = (bool) preg_match( '/(?<!\\\\)\$(?!\s|\$).+?(?<!\s)\$(?!\$)/', $content );
    }
}

$this->sectionHasMath[ $id ] = $has_math;
return $has_math;
```

**Important:** The sectionHasMath cache is keyed by post ID. Tests that set `$GLOBALS['post']` must use a real WP_Post object (not just the ID), because the method calls `get_post()`. The tests above use `get_post( $pid )` to ensure this works.

- [ ] Apply the change to `inc/class-mathjax.php`.

### Step 4: Run tests to confirm they pass

```bash
vendor/bin/phpunit --configuration phpunit.xml --filter "testSectionHasMathSingleDollar|testSectionHasMathCurrency" tests/test-mathjax.php
```

Expected: 3 PASSes.

- [ ] Run and confirm.

### Step 5: Run full test suite

```bash
vendor/bin/phpunit --configuration phpunit.xml tests/test-mathjax.php
```

Expected: All pass.

- [ ] Run and confirm.

### Step 6: Commit

```bash
git add inc/class-mathjax.php tests/test-mathjax.php
git commit -m "feat: detect single dollar sign math in sectionHasMath when option enabled"
```

- [ ] Commit.

---

## Task 3: Webbook client-side — `addHeaders()`

**Files:**
- Modify: `inc/class-mathjax.php` (lines ~276-347)
- Test: `tests/test-mathjax.php`

### Step 1: Write the failing tests

```php
public function testAddHeadersSingleDollarEnabled() {
    update_option( MathJax::OPTION, [ 'fg' => '000000', 'use_single_dollar' => true ] );

    $new_post = [
        'post_title'   => 'Test Chapter: ' . wp_rand(),
        'post_type'    => 'chapter',
        'post_status'  => 'published',
        'post_content' => '$e^{i\pi}+1=0$',
    ];
    $pid             = $this->factory()->post->create_object( $new_post );
    $GLOBALS['post'] = get_post( $pid );

    ob_start();
    $this->mathjax->addHeaders();
    $buffer = ob_get_clean();

    $this->assertStringContainsString( "['$','$']", $buffer );
}

public function testAddHeadersSingleDollarDisabled() {
    update_option( MathJax::OPTION, [ 'fg' => '000000', 'use_single_dollar' => false ] );

    $new_post = [
        'post_title'   => 'Test Chapter: ' . wp_rand(),
        'post_type'    => 'chapter',
        'post_status'  => 'published',
        'post_content' => '[latex]x^2[/latex]',
    ];
    $pid             = $this->factory()->post->create_object( $new_post );
    $GLOBALS['post'] = get_post( $pid );

    ob_start();
    $this->mathjax->addHeaders();
    $buffer = ob_get_clean();

    $this->assertStringNotContainsString( "['$','$']", $buffer );
}
```

- [ ] Add both test methods to `tests/test-mathjax.php`.

### Step 2: Run tests to confirm they fail

```bash
vendor/bin/phpunit --configuration phpunit.xml --filter "testAddHeadersSingleDollar" tests/test-mathjax.php
```

Expected: FAILs.

- [ ] Run and confirm.

### Step 3: Implement the `addHeaders()` change

In `inc/class-mathjax.php`, **replace the entire `addHeaders()` method** (around lines 276–347) with the following. The approach builds the `inlineMath` JS array in PHP before the `echo` block so it can be conditionally extended:

```php
public function addHeaders() {
    if ( ! is_admin() && $this->sectionHasMath() ) {
        $options    = $this->getOptions();
        $inline_math = "[['\\\\\\\\(', '\\\\\\\\)'], ['[latex]','[/latex]']]";
        if ( $options['use_single_dollar'] ) {
            $inline_math = "[['\\\\\\\\(', '\\\\\\\\)'], ['[latex]','[/latex]'], ['$','$']]";
        }
        echo "<script>
window.MathJax = {
	versionWarnings: false,
    loader: {
        load: [
			'input/asciimath',
			'output/chtml',
            '[tex]/ams',
            '[tex]/bbox',
            '[tex]/boldsymbol',
            '[tex]/braket',
            '[tex]/cancel',
            '[tex]/color',
            '[tex]/enclose',
            '[tex]/gensymb',
            '[tex]/mathtools',
            '[tex]/mhchem',
            '[tex]/textmacros',
            '[tex]/newcommand',
            '[tex]/noerrors',
            '[tex]/physics',
            '[tex]/unicode'
        ]
    },
	asciimath: {
			delimiters: [['`','`'],['[asciimath]','[/asciimath]']]
		},
    tex: {
        inlineMath: {$inline_math},
        displayMath: [['$$', '$$'], ['\\\\[', '\\\\]']],
        packages: {
            '[+]': [
                'ams',
                'bbox',
                'boldsymbol',
                'braket',
                'cancel',
                'color',
                'enclose',
                'gensymb',
                'mathtools',
                'mhchem',
                'textmacros',
                'newcommand',
                'noerrors',
                'physics',
                'unicode'
            ]
        },
        tags: 'ams',
            formatError: function (message) {
				return '\\\\color{red}{\\\\text{MathJax error: ' + message + '}}';
			}
    },
    svg: {
        fontCache: 'global'
    }
};
</script>";
        echo <<<STYLES
<style>
.MathJax {
    color: #{$options['fg']} !important;
}
</style>
STYLES;
    }
}
```

**Note on string escaping:** The existing code outputs `\\(` for JavaScript `\(`. The PHP string `'\\\\\\\\('` produces the PHP literal `\\\\(` which in the HTML `<script>` block becomes the JavaScript string `\\(` — which MathJax sees as the LaTeX `\(`. The `['$','$']` pair needs no escaping.

- [ ] Apply the change to `addHeaders()` in `inc/class-mathjax.php`.

### Step 4: Run tests to confirm they pass

```bash
vendor/bin/phpunit --configuration phpunit.xml --filter "testAddHeadersSingleDollar" tests/test-mathjax.php
```

Expected: 2 PASSes.

- [ ] Run and confirm.

### Step 5: Run full test suite

```bash
vendor/bin/phpunit --configuration phpunit.xml tests/test-mathjax.php
```

Expected: All pass.

- [ ] Run and confirm.

### Step 6: Commit

```bash
git add inc/class-mathjax.php tests/test-mathjax.php
git commit -m "feat: add single dollar sign to MathJax client-side config when option enabled"
```

- [ ] Commit.

---

## Task 4: Export pipeline — `replaceLatexDelimitersOnExports()`

**Files:**
- Modify: `inc/class-mathjax.php` (lines ~446-466)
- Test: `tests/test-mathjax.php`

### Step 1: Write the failing tests

```php
public function testExportSingleDollar() {
    update_option( MathJax::OPTION, [ 'fg' => '000000', 'use_single_dollar' => true ] );
    $this->mathjax->usePbMathJax = true;

    $s = $this->mathjax->replaceLatexDelimitersOnExports( '$e^{i\pi}+1=0$' );
    // Should be converted to an <img> tag (inline, no display-math wrapper)
    $this->assertStringStartsWith( '<img', $s );
    $this->assertStringNotContainsString( 'display-math', $s );
}

public function testExportSingleDollarDisabled() {
    update_option( MathJax::OPTION, [ 'fg' => '000000', 'use_single_dollar' => false ] );
    $this->mathjax->usePbMathJax = true;

    $s = $this->mathjax->replaceLatexDelimitersOnExports( '$e^{i\pi}+1=0$' );
    // Should be left unchanged
    $this->assertEquals( '$e^{i\pi}+1=0$', $s );
}

public function testExportSingleDollarFalsePositive() {
    update_option( MathJax::OPTION, [ 'fg' => '000000', 'use_single_dollar' => true ] );
    $this->mathjax->usePbMathJax = true;

    $s = $this->mathjax->replaceLatexDelimitersOnExports( 'We earned $ 5,000 today and $ 4,000 yesterday.' );
    // Currency with spaces must NOT be converted
    $this->assertEquals( 'We earned $ 5,000 today and $ 4,000 yesterday.', $s );
}

public function testExportSingleDollarEscape() {
    update_option( MathJax::OPTION, [ 'fg' => '000000', 'use_single_dollar' => true ] );
    $this->mathjax->usePbMathJax = true;

    $s = $this->mathjax->replaceLatexDelimitersOnExports( '\$5,000' );
    // Escaped dollar sign must NOT be converted
    $this->assertEquals( '\$5,000', $s );
}

public function testExportDoubleDollarUnaffected() {
    // Double dollar should still produce display math, unaffected by single-dollar setting
    update_option( MathJax::OPTION, [ 'fg' => '000000', 'use_single_dollar' => true ] );
    $this->mathjax->usePbMathJax = true;

    $s = $this->mathjax->replaceLatexDelimitersOnExports( '$$e^{i\pi}+1=0$$' );
    $this->assertStringContainsString( 'display-math', $s );
    $this->assertStringStartsWith( '<div class="display-math"><img', $s );
}
```

- [ ] Add all five test methods to `tests/test-mathjax.php`.

### Step 2: Run tests to confirm they fail

```bash
vendor/bin/phpunit --configuration phpunit.xml --filter "testExportSingleDollar|testExportDoubleDollar" tests/test-mathjax.php
```

Expected: FAILs.

- [ ] Run and confirm.

### Step 3: Implement the export change

In `inc/class-mathjax.php`, `replaceLatexDelimitersOnExports()`, replace the method body:

```php
public function replaceLatexDelimitersOnExports( $content ) {
    $patterns = [
        // Match block LaTeX equations: \[ ... \]
        '%\\\\\[(.*?)\\\\\]%s', // \[ ... \]
        // Match inline LaTeX equations: \( ... \)
        '%\\\\\((.*?)\\\\\)%s',
        // Match $$ ... $$ LaTeX equations
        '%\$\$(.*?)\$\$%s', // $$ ... $$
    ];
    foreach ( $patterns as $index => $pattern ) {
        $content = preg_replace_callback( $pattern, function ( $matches ) use ( $index ) {
            $rendered = $this->renderFormula( $matches[1], 'latex' );
            // Wrap in div if it's display math (\[...\]) or $$...$$
            if ( $index === 0 || $index === 2 ) {
                return '<div class="display-math">' . $rendered . '</div>';
            }
            return $rendered;
        }, $content );
    }

    // Conditionally handle single dollar sign inline math
    $options = $this->getOptions();
    if ( $options['use_single_dollar'] ) {
        // Match $...$ where:
        //   (?<!\\)   - opening $ not preceded by backslash (respects \$ escape)
        //   (?!\s|\$) - opening $ not followed by whitespace or another $ (avoids $$ and currency)
        //   (.+?)     - non-greedy single-line capture
        //   (?<!\s)   - closing $ not preceded by whitespace
        //   (?!\$)    - closing $ not followed by $ (avoids $$ collision)
        $content = preg_replace_callback(
            '/(?<!\\\\)\$(?!\s|\$)(.+?)(?<!\s)\$(?!\$)/',
            function ( $matches ) {
                return $this->renderFormula( $matches[1], 'latex' );
            },
            $content
        );
    }

    return $content;
}
```

- [ ] Apply the change to `replaceLatexDelimitersOnExports()` in `inc/class-mathjax.php`.

### Step 4: Run tests to confirm they pass

```bash
vendor/bin/phpunit --configuration phpunit.xml --filter "testExportSingleDollar|testExportDoubleDollar" tests/test-mathjax.php
```

Expected: 5 PASSes.

- [ ] Run and confirm.

### Step 5: Run full test suite

```bash
vendor/bin/phpunit --configuration phpunit.xml tests/test-mathjax.php
```

Expected: All pass.

- [ ] Run and confirm.

### Step 6: Commit

```bash
git add inc/class-mathjax.php tests/test-mathjax.php
git commit -m "feat: handle single dollar sign delimiters in export pipeline when option enabled"
```

- [ ] Commit.

---

## Task 5: Admin UI — Blade template

**Files:**
- Modify: `templates/admin/mathjax.blade.php`

No new test needed (admin UI is covered by the existing `testRenderPage` smoke test which verifies the page renders without crashing).

### Step 1: Update the Blade template

Replace the content of `templates/admin/mathjax.blade.php`:

```blade
<div class="wrap">
    <h1>{{ __( 'MathJax', 'pressbooks' ) }}</h1>
    {!! $test_image !!}
    <p class='test-image'> {{ __( 'If you can see a big integral, then PB-MathJax is configured correctly, and all is well.', 'pressbooks' ) }} </p>
    <form action="" method="post">
        <table class="form-table" role="none">
            <tbody>
            <tr>
                <th scope="row">{{ __( 'Syntax', 'pressbooks' ) }}</th>
                <td class="syntax">
                    <section>
                        <h2>{{ __( 'LaTeX' ,'pressbooks' ) }}</h2>
                        <p>
                            {!! sprintf( __( 'Inline math delimiter: %s', 'pressbooks' ), '<code>\( e^{i \pi} + 1 = 0 \)</code>') !!}
                        </p>
                        <p>
                            {!! sprintf( __( 'Display math delimiter: %s', 'pressbooks' ), '<code>\[ e^{i \pi} + 1 = 0 \]</code>' ) !!}
                        </p>
                        <p>
                            {!! sprintf( __( 'Shortcode syntax: %s', 'pressbooks' ), '<code>[latex]e^{i \pi} + 1 = 0[/latex]</code>' ) !!}
                        </p>
                        <p>
                            {!! sprintf( __( 'Double dollar sign syntax: %s', 'pressbooks' ), '<code>$$ e^{i \pi} + 1 = 0 $$</code>' ) !!}
                        </p>
                        @if ( $use_single_dollar )
                        <p>
                            {!! sprintf( __( 'Single dollar sign syntax: %s', 'pressbooks' ), '<code>$e^{i \pi} + 1 = 0$</code>' ) !!}
                        </p>
                        @endif
                    </section>
                    <section>
                        <h2>{{ __( 'AsciiMath' ,'pressbooks' ) }}</h2>
                        <p>
                            {!! sprintf( __( 'Shortcode syntax: %s', 'pressbooks' ),'<code>[asciimath]e^{i \pi} + 1 = 0[/asciimath]</code>' ) !!}
                        </p>
                        <p>
                            {!! sprintf( __( 'Dollar sign syntax: %s', 'pressbooks' ),'<code>$asciimath e^{i \pi} + 1 = 0$</code>' ) !!}
                        </p>
                    </section>
                    <section>
                        <h2>{{ __( 'MathML' ,'pressbooks' ) }}</h2>
                        <p>{!! sprintf(
                            __( 'Markup syntax: %s', 'pressbooks' ),
                            '<code>&lt;math&gt;&lt;!-- Your math here --&gt;&lt;/math&gt;</code>'
                        ) !!} </p>
                    </section>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="mathjax-use-single-dollar">{{ __( 'Single dollar sign delimiter', 'pressbooks' ) }}</label>
                </th>
                <td>
                    <input type="checkbox" name="use_single_dollar" id="mathjax-use-single-dollar"
                           value="1" {{ $use_single_dollar ? 'checked' : '' }} />
                    <p>
                        {!! sprintf( __( 'When enabled, %s will be treated as inline LaTeX math.', 'pressbooks' ), '<code>$e^{i \pi} + 1 = 0$</code>' ) !!}
                    </p>
                    <p>
                        {{ __( 'The opening $ must not be followed by a space and the closing $ must not be preceded by a space, so currency like "$ 5,000" is not affected.', 'pressbooks' ) }}
                        {!! __( 'Use <code>\$</code> to display a literal dollar sign.', 'pressbooks' ) !!}
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="mathjax-fg">{{ __( 'Text color', 'pressbooks' ) }}</label></th>
                <td>
                    <input type='text' name='fg' value='{{ $fg }}' id='mathjax-fg'/>
                    <p>{!!  __( 'A six digit hexadecimal number like <code>000000</code> or <code>ffffff</code>', 'pressbooks' )  !!}</p>
                </td>
            </tr>
            </tbody>
        </table>
        <p class="submit">
            <input type="submit" class="button-primary" value="{{ __( 'Save Changes', 'pressbooks' ) }}"/>
            {!! $wp_nonce_field !!}
        </p>
    </form>
</div>
```

- [ ] Replace `templates/admin/mathjax.blade.php` with the content above.

### Step 2: Pass `$use_single_dollar` to the template from `renderPage()`

In `inc/class-mathjax.php`, `renderPage()` (around line 163), update the `$blade->render()` call:

```php
// Before:
echo $blade->render(
    'admin.mathjax',
    [
        'wp_nonce_field' => wp_nonce_field( 'save', 'pb-mathjax-nonce', true, false ),
        'test_image'     => $test_image,
        'fg'             => $options['fg'],
    ]
);

// After:
echo $blade->render(
    'admin.mathjax',
    [
        'wp_nonce_field'    => wp_nonce_field( 'save', 'pb-mathjax-nonce', true, false ),
        'test_image'        => $test_image,
        'fg'                => $options['fg'],
        'use_single_dollar' => $options['use_single_dollar'],
    ]
);
```

- [ ] Apply the change to `renderPage()` in `inc/class-mathjax.php`.

### Step 3: Run the existing admin page smoke test

```bash
vendor/bin/phpunit --configuration phpunit.xml --filter testRenderPage tests/test-mathjax.php
```

Expected: PASS (the page renders without crashing).

- [ ] Run and confirm.

### Step 4: Run full test suite

```bash
vendor/bin/phpunit --configuration phpunit.xml tests/test-mathjax.php
```

Expected: All pass.

- [ ] Run and confirm.

### Step 5: Run coding standards check

```bash
composer standards
```

Expected: No errors.

- [ ] Run and confirm.

### Step 6: Commit

```bash
git add inc/class-mathjax.php templates/admin/mathjax.blade.php
git commit -m "feat: add single dollar sign delimiter checkbox to MathJax settings page"
```

- [ ] Commit.

---

## Task 6: Final verification

### Step 1: Run the full test suite one final time

```bash
vendor/bin/phpunit --configuration phpunit.xml tests/test-mathjax.php
```

Expected: All tests pass, including all 13 new tests.

- [ ] Run and confirm.

### Step 2: Run coding standards

```bash
composer standards
```

Expected: No errors in `inc/class-mathjax.php`.

- [ ] Run and confirm.

### Step 3: Verify test count

```bash
vendor/bin/phpunit --configuration phpunit.xml tests/test-mathjax.php --testdox
```

Confirm these new tests appear and pass:
- `testSingleDollarOptionDefault`
- `testSingleDollarOptionSave`
- `testSingleDollarOptionSaveUnchecked`
- `testSectionHasMathSingleDollarEnabled`
- `testSectionHasMathSingleDollarDisabled`
- `testSectionHasMathCurrencyNotMath`
- `testAddHeadersSingleDollarEnabled`
- `testAddHeadersSingleDollarDisabled`
- `testExportSingleDollar`
- `testExportSingleDollarDisabled`
- `testExportSingleDollarFalsePositive`
- `testExportSingleDollarEscape`
- `testExportDoubleDollarUnaffected`

- [ ] Confirm all 13 new tests appear and pass.
