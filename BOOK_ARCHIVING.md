# Book Archiving Feature

## Overview

The book archiving feature allows network managers to mark books as "archived" while keeping them publicly accessible. This is useful for books that are no longer actively maintained but should remain available for reference.

## How It Works

### For Network Managers

1. **Navigate to Network Admin → Book List**
2. **Find the archived book(s)** in the table
3. **Use one of these methods:**
   - **Individual Book**: Click the checkbox icon in the "Archived" column to toggle archive status
   - **Bulk Action**: Select multiple books and choose "Archive Books" or "Unarchive Books" from the bulk actions dropdown

### Archive Status Features

- **Archived Date**: Stored when a book is archived
- **Archived By**: Records which network manager archived the book
- **Public Access**: Archived books remain fully accessible to readers
- **Cloning**: Archived books can still be cloned (respecting license permissions)
- **Read-Only Banner**: Displays a prominent notice on archived books

## User Experience

### Webbook Display

When a book is archived, visitors see a prominent banner at the top of all pages:

```
This book was archived by its publisher on [Date]. It is now read-only.
```

The banner:
- Uses Pressbooks yellow background (#f2c744)
- Center-aligned text with 0.75rem top/bottom and 2rem left/right padding
- Hidden on print via `@media print { display: none; }`
- Accessible with `role="alert"` and `aria-live="polite"` attributes
- Shows formatted date if available, or generic message if date metadata is missing

### WordPress Core Override

**Important**: This feature overrides WordPress's default "archived" site behavior. 

- **WordPress Default**: Archived sites show "This site has been archived or suspended" and block all access
- **Pressbooks Override**: Public archived books remain fully accessible with a banner notification

This is implemented by hooking into `ms_site_check` filter at priority 1 in `hooks.php`. The filter checks if a book is both:
1. Archived (`$site_details->archived === '1'`)
2. Public (`$site_details->public === '1'`)

If both conditions are met, the book remains accessible and the archive banner displays.

## Technical Implementation

### Data Model

Archive status is stored in WordPress `wp_blogmeta` table:

```php
// Archive date (timestamp when archived)
'pb_book_archived_date' => '2025-11-19 10:30:00'

// Archived by (user ID of network manager who archived it)
'pb_book_archived_by' => 123
```

### Files Modified/Created

**Pressbooks Core Plugin:**
- `inc/datacollector/class-book.php` - Added `ARCHIVED_DATE` and `ARCHIVED_BY` constants
- `hooks.php` - Added two hooks:
  - `ms_site_check` filter (priority 1) - Overrides WordPress default archived site blocking for public archived books
  - `wp_update_site` action - Syncs WordPress native archive checkbox with Pressbooks metadata fields

**Pressbooks Book Theme (McLuhan):**
- `inc/archivebanner/namespace.php` - Archive banner display logic
- `partials/archive-banner.php` - Archive banner Blade template
- `header.php` - Calls archive banner function before reading header
- `assets/styles/web/style.scss` - Archive banner styles (Pressbooks yellow background, centered text)
- `functions.php` - Requires archivebanner namespace

**Pressbooks Network Analytics:**
- `inc/model/class-booklist.php`:
  - Added `$isArchived` property
  - Added `filterIsArchived()` method
  - Added `applyIsArchived()` method
  - Added `archived` case to `action()` method
  - Updated SQL query to include archived fields
- `inc/admin/class-books.php`:
  - Added "Archived" column to table
  - Added archive filter in `filteredBookList()`
  - Added success messages for archive/unarchive actions

### Archive/Unarchive Logic

```php
// Archive a book
$booklist = new BookList();
$booklist->action( $book_id, 'archived', true );
// Sets: pb_book_archived_date, pb_book_archived_by

// Unarchive a book  
$booklist->action( $book_id, 'archived', false );
// Removes: pb_book_archived_date, pb_book_archived_by
```

### Banner Display Logic

The banner appears when:
1. User is on a public page (not admin)
2. WordPress native `archived` flag is set to '1' in `wp_blogs` table
3. The banner optionally includes the formatted archive date if `pb_book_archived_date` metadata exists

**Implementation:**
- Banner logic lives in theme: `pressbooks-book/inc/archivebanner/namespace.php`
- Template: `pressbooks-book/partials/archive-banner.php`
- Called from: `pressbooks-book/header.php` and displayed before the `.reading-header` element
- Styles: `pressbooks-book/assets/styles/web/style.scss`

## Filtering Archived Books

In the Network Admin Book List, you can filter by archive status:

- **Show only archived books**: Use the filters panel
- **Show only non-archived books**: Use the filters panel
- **Show all books**: Don't apply archive filter

## Use Cases

### Accessibility Compliance
Some accessibility regulations require maintaining archived content for reference even after active maintenance stops.

### Content Lifecycle Management
Mark books that have reached end-of-life but should remain available for historical reference.

### Sunset Content
Inform readers that a book is no longer being updated while keeping it accessible.

## Cloning Archived Books

Archived books **can be cloned** if the license permits. The cloned book:
- Does **not** inherit the archived status
- Starts as a regular, non-archived book
- Cloner can modify the content

## API Integration

The archive status is included in book metadata:

```php
$archived_date = get_site_meta( $book_id, \Pressbooks\DataCollector\Book::ARCHIVED_DATE, true );
$archived_by = get_site_meta( $book_id, \Pressbooks\DataCollector\Book::ARCHIVED_BY, true );

if ( $archived_date ) {
    // Book is archived
    $formatted_date = date_i18n( get_option( 'date_format' ), strtotime( $archived_date ) );
}
```

## Architecture Notes

### Plugin vs Theme Separation

**Pressbooks Core Plugin:**
- Data model (constants in DataCollector\Book)
- WordPress hooks (ms_site_check filter, wp_update_site action)
- Network Analytics integration

**Pressbooks Book Theme:**
- Banner display logic and template
- Visual styling (SCSS)
- Integration into book layout

This separation ensures:
- Archive functionality works across all Pressbooks themes (McLuhan and children)
- Plugin focuses on data management and WordPress integration
- Theme handles presentation and user-facing display
- Child themes can override banner template or styles as needed

### WordPress Native Archive Sync

The `wp_update_site` hook in `hooks.php` ensures that archiving a book via:
- Network Analytics interface, OR
- WordPress native "Edit Site" → Archive checkbox

...both methods result in the same stored metadata (`pb_book_archived_date` and `pb_book_archived_by`).

## Troubleshooting

### Banner Not Showing

1. Check if book is actually archived: Query `wp_blogmeta` for `pb_book_archived_date`
2. Clear site cache if using caching plugin
3. Check browser console for CSS/JS errors

### "Site Archived" Message Shows

If you see WordPress's default "This site has been archived or suspended" message:
- The `ms_site_check` filter may not be working
- Check that Pressbooks hooks are loading (`hooks.php`)
- Verify the book ID in the database

### Cannot Archive Books

1. Verify you have network manager permissions
2. Check JavaScript console for AJAX errors
3. Verify nonce is valid (try refreshing the page)

## Testing

To test the implementation:

```bash
# Test archiving
wp eval 'switch_to_blog(123); update_site_meta(123, "pb_book_archived_date", gmdate("Y-m-d H:i:s")); update_site_meta(123, "pb_book_archived_by", 1);'

# Test unarchiving
wp eval 'delete_site_meta(123, "pb_book_archived_date"); delete_site_meta(123, "pb_book_archived_by");'

# Check banner displays
curl -I https://pressbooks.test/yourbook
```