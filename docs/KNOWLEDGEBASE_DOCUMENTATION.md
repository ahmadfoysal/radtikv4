# Knowledgebase & Documentation System

## Overview

This document describes the complete implementation of the Knowledgebase and Documentation system for RADTik v4. The system provides self-service help resources with search, category filtering, and pagination capabilities.

## Table of Contents

- [Architecture](#architecture)
- [Database Schema](#database-schema)
- [Models](#models)
- [Livewire Components](#livewire-components)
- [Views](#views)
- [Routes](#routes)
- [Seeded Content](#seeded-content)
- [Features](#features)
- [Installation](#installation)
- [Usage](#usage)
- [Customization](#customization)

---

## Architecture

The system follows the existing RADTik architecture and patterns:

- **Framework**: Laravel 12
- **Frontend**: Livewire 3 + MaryUI components
- **Database**: MySQL/SQLite compatible
- **Design System**: TailwindCSS + DaisyUI (via MaryUI)

### File Structure

```
app/
├── Livewire/
│   ├── Knowledgebase/
│   │   ├── Index.php          # Article listing with search/filter
│   │   └── Show.php            # Single article display
│   └── Docs/
│       ├── Index.php           # Documentation listing
│       └── Show.php            # Single documentation display
├── Models/
│   ├── KnowledgebaseArticle.php
│   └── DocumentationArticle.php
database/
├── migrations/
│   ├── 2025_12_09_022140_create_knowledgebase_articles_table.php
│   └── 2025_12_09_022141_create_documentation_articles_table.php
└── seeders/
    ├── KnowledgebaseArticleSeeder.php
    └── DocumentationArticleSeeder.php
resources/
└── views/
    └── livewire/
        ├── knowledgebase/
        │   ├── index.blade.php
        │   └── show.blade.php
        └── docs/
            ├── index.blade.php
            └── show.blade.php
routes/
└── web.php                     # 4 new routes added
```

---

## Database Schema

### Tables Created

#### `knowledgebase_articles`
| Column      | Type       | Description                          |
|-------------|------------|--------------------------------------|
| id          | bigint     | Primary key                          |
| title       | string     | Article title                        |
| slug        | string     | URL-friendly identifier (unique)     |
| category    | string     | Article category                     |
| content     | longtext   | Article content                      |
| is_active   | boolean    | Publish status (default: true)       |
| created_at  | timestamp  | Creation timestamp                   |
| updated_at  | timestamp  | Last update timestamp                |

#### `documentation_articles`
Same schema as `knowledgebase_articles`.

### Indexes
- `slug` column has a unique index for fast lookups and preventing duplicates

---

## Models

### KnowledgebaseArticle

Located at: `app/Models/KnowledgebaseArticle.php`

**Features**:
- Automatic unique slug generation from title
- Handles duplicate titles by appending `-1`, `-2`, etc.
- Updates slug when title changes
- Boolean casting for `is_active`

**Example Usage**:
```php
// Create an article with automatic slug
$article = KnowledgebaseArticle::create([
    'title' => 'How to Add a Router',
    'category' => 'getting-started',
    'content' => 'Step-by-step guide...',
    'is_active' => true,
]);
// $article->slug === 'how-to-add-a-router'

// Create duplicate title (slug auto-adjusted)
$article2 = KnowledgebaseArticle::create([
    'title' => 'How to Add a Router', // Same title
    'category' => 'advanced',
    'content' => 'Advanced guide...',
]);
// $article2->slug === 'how-to-add-a-router-1'
```

**Slug Generation Logic**:
```php
protected static function uniqueSlugFrom(string $title, ?int $ignoreId = null): string
{
    $base = Str::slug($title) ?: Str::random(8);
    $slug = $base;
    $suffix = 1;

    while (static::where('slug', $slug)
        ->when($ignoreId, fn (Builder $query) => $query->where('id', '!=', $ignoreId))
        ->exists()) {
        $slug = $base.'-'.$suffix;
        $suffix++;
    }

    return $slug;
}
```

### DocumentationArticle

Located at: `app/Models/DocumentationArticle.php`

Identical functionality to `KnowledgebaseArticle` but for documentation content.

---

## Livewire Components

### Knowledgebase/Index

**Location**: `app/Livewire/Knowledgebase/Index.php`

**Properties**:
- `$q` - Search query string
- `$category` - Selected category filter
- `$perPage` - Items per page (default: 12)

**Features**:
- Live search with 400ms debounce
- Category dropdown filter
- Pagination
- Query string persistence (bookmarkable URLs)
- Automatic page reset on filter changes

**Methods**:
- `articles()` - Returns paginated articles with filters applied
- `categories()` - Returns distinct category list
- `render()` - Renders the view with data

### Knowledgebase/Show

**Location**: `app/Livewire/Knowledgebase/Show.php`

**Features**:
- Displays single article by slug
- Shows only active articles
- Returns 404 for invalid/inactive articles

### Docs/Index & Docs/Show

Same functionality as Knowledgebase components but for documentation articles.

---

## Views

### Design System

All views follow the existing RADTik design patterns:

**Colors**:
- `bg-base-200` - Card backgrounds
- `badge-primary` - Category badges
- `text-primary` - Icons and accents

**Spacing**:
- `gap-4` - Grid and flex gaps
- `mb-6` - Section margins
- `px-4 py-4` - Card padding

**Rounded Corners**:
- `rounded-2xl` - Large rounded corners on cards

**Typography**:
- `font-semibold` - Headings
- `opacity-70` - Secondary text
- `line-clamp-2` - Title truncation
- `line-clamp-3` - Content preview truncation

### Knowledgebase Index (`knowledgebase/index.blade.php`)

**Layout**:
```
┌─────────────────────────────────────────┐
│  💡 Knowledge Base                      │
│  [Search...] [Category Filter ▼]       │
└─────────────────────────────────────────┘

┌───────────┐ ┌───────────┐ ┌───────────┐
│ Article 1 │ │ Article 2 │ │ Article 3 │
│ [Badge]   │ │ [Badge]   │ │ [Badge]   │
│ Title     │ │ Title     │ │ Title     │
│ Preview   │ │ Preview   │ │ Preview   │
│ [Read →]  │ │ [Read →]  │ │ [Read →]  │
└───────────┘ └───────────┘ └───────────┘

         [Pagination: 1 2 3 >]
```

**Responsive Grid**:
- Mobile (< 768px): 1 column
- Tablet (768px - 1024px): 2 columns
- Desktop (> 1024px): 3 columns

### Knowledgebase Show (`knowledgebase/show.blade.php`)

**Layout**:
```
[← Back to Knowledge Base]

┌─────────────────────────────────────────┐
│ [Badge: Category]                       │
│                                         │
│ Article Title                           │
│ (Large, Bold)                           │
│                                         │
│ 📅 Date  •  🕒 Updated                  │
│                                         │
│ ─────────────────────────────────────  │
│                                         │
│ Full article content...                 │
│ With proper formatting...               │
│                                         │
└─────────────────────────────────────────┘

     [← Back to Knowledge Base]
```

**Container**: 
- `max-w-4xl mx-auto` - Centered, max-width layout
- Typography-optimized for reading

---

## Routes

Added to `routes/web.php` within the `auth` middleware group:

```php
/* Knowledgebase Routes */
Route::get('/knowledgebase', App\Livewire\Knowledgebase\Index::class)
    ->name('knowledgebase.index');
Route::get('/knowledgebase/{slug}', App\Livewire\Knowledgebase\Show::class)
    ->name('knowledgebase.show');

/* Documentation Routes */
Route::get('/docs', App\Livewire\Docs\Index::class)
    ->name('docs.index');
Route::get('/docs/{slug}', App\Livewire\Docs\Show::class)
    ->name('docs.show');
```

**Route Names**:
- `knowledgebase.index` - `/knowledgebase`
- `knowledgebase.show` - `/knowledgebase/{slug}`
- `docs.index` - `/docs`
- `docs.show` - `/docs/{slug}`

**Access Control**:
- All routes require authentication
- Available to: Admin, Reseller, Superadmin roles
- Read-only access for all users

---

## Seeded Content

### Knowledgebase Articles (7)

Located in: `database/seeders/KnowledgebaseArticleSeeder.php`

1. **How to Add a MikroTik Router** (getting-started)
   - Complete guide for adding routers
   - Configuration steps
   - Troubleshooting tips

2. **Understanding Automatic Renewal** (billing)
   - How auto-renewal works
   - Configuration options
   - Benefits and requirements

3. **How Vouchers Sync Between MikroTik and RADIUS** (vouchers)
   - MikroTik mode vs RADIUS mode
   - Synchronization features
   - API endpoints
   - Best practices

4. **Package Management Guide** (packages)
   - Creating packages
   - Assigning to routers
   - Monitoring usage
   - Upgrading/downgrading

5. **Payment & Billing System Usage** (billing)
   - Account balance management
   - Payment gateways (Cryptomus, PayStation)
   - Invoice management
   - Manual adjustments

6. **Managing User Profiles** (profiles)
   - Creating profiles
   - Bandwidth settings
   - Session timeouts
   - Using with vouchers

7. **Zone Management for Multi-Location Setup** (getting-started)
   - Creating zones
   - Organizing routers
   - Multi-zone best practices

### Documentation Articles (6)

Located in: `database/seeders/DocumentationArticleSeeder.php`

1. **API Usage Guide** (api)
   - API endpoints
   - Authentication with tokens
   - Request/response examples
   - Rate limiting
   - Error handling

2. **MikroTik API Integration** (integration)
   - System requirements
   - Enabling MikroTik API
   - Firewall configuration
   - User management
   - Session management

3. **RADIUS Server Setup** (radius)
   - RADIUS overview
   - Configuration in RADTik
   - MikroTik integration
   - Accounting
   - Troubleshooting

4. **Billing System Overview** (billing)
   - Billing components
   - Workflow
   - Invoice management
   - Payment gateway integration
   - Automated notifications

5. **Admin vs Reseller Permissions** (permissions)
   - User roles overview
   - Permission matrix
   - Reseller workflow
   - Best practices

6. **Voucher Generation and Management** (vouchers)
   - Generation process
   - Voucher properties
   - Batch management
   - Printing options
   - Tracking and monitoring

---

## Features

### 🔍 Search Functionality

- **Live Search**: 400ms debounce for smooth user experience
- **Search Fields**: Title and content
- **Case Insensitive**: Works with any case
- **Query String**: Preserves search in URL (`?q=router`)
- **Empty State**: Shows "No articles found" message

**Implementation**:
```php
->when($this->q !== '', function ($query) {
    $term = '%' . strtolower($this->q) . '%';
    $query->where(function ($q) use ($term) {
        $q->whereRaw('LOWER(title) LIKE ?', [$term])
            ->orWhereRaw('LOWER(content) LIKE ?', [$term]);
    });
})
```

### 🏷️ Category Filter

- **Dynamic Categories**: Automatically populated from articles
- **All Categories**: Default option shows all
- **Query String**: Preserves selection in URL (`?category=billing`)
- **Combined Filters**: Works with search simultaneously

### 📄 Pagination

- **Items Per Page**: 12 (configurable)
- **Laravel Pagination**: Standard Laravel pagination links
- **Page Reset**: Automatically resets to page 1 when filters change
- **Query String**: Preserves page in URL (`?page=2`)

### 📱 Responsive Design

- **Mobile First**: Optimized for small screens
- **Breakpoints**:
  - Mobile: 1 column grid
  - Tablet: 2 column grid
  - Desktop: 3 column grid
- **Touch Friendly**: Large tap targets
- **Flexible Layout**: Adapts to any screen size

### 🎨 UI Components

**MaryUI Components Used**:
- `<x-mary-card>` - Card containers
- `<x-mary-input>` - Search input
- `<x-mary-select>` - Category dropdown
- `<x-mary-button>` - Action buttons
- `<x-mary-icon>` - Heroicons

**Consistent Styling**:
- Matches existing voucher and router pages
- Same color scheme and spacing
- Unified user experience

### 🔗 Navigation

- **Wire Navigate**: SPA-like navigation without page reloads
- **Back Buttons**: Easy navigation to list pages
- **Breadcrumbs**: Clear navigation path
- **Named Routes**: Clean, maintainable route references

---

## Installation

### 1. Run Migrations

```bash
php artisan migrate
```

Creates `knowledgebase_articles` and `documentation_articles` tables.

### 2. Seed Database

```bash
# Seed knowledgebase articles
php artisan db:seed --class=KnowledgebaseArticleSeeder

# Seed documentation articles
php artisan db:seed --class=DocumentationArticleSeeder

# Or seed everything
php artisan db:seed
```

### 3. Clear Caches (Optional)

```bash
php artisan route:clear
php artisan view:clear
php artisan config:clear
```

### 4. Verify Installation

Visit these URLs:
- http://your-domain.com/knowledgebase
- http://your-domain.com/docs

Or use sidebar menu:
- Help & Support → Knowledge Base
- Help & Support → Documentation

---

## Usage

### For End Users

**Browsing Articles**:
1. Click "Knowledge Base" or "Documentation" in sidebar
2. View articles in grid layout
3. Use search box to find specific topics
4. Filter by category using dropdown
5. Click "Read More" to view full article

**Reading Articles**:
1. Click any article card
2. Read full content
3. Click "Back" to return to list

**Search Tips**:
- Search works on titles and content
- Use specific keywords for better results
- Combine search with category filter

### For Administrators

**Adding Articles Programmatically**:

```php
use App\Models\KnowledgebaseArticle;

KnowledgebaseArticle::create([
    'title' => 'New Article Title',
    'category' => 'category-name',
    'content' => 'Article content here...',
    'is_active' => true,
]);
```

**Using Tinker**:

```bash
php artisan tinker
```

```php
// Create article
>>> $article = App\Models\KnowledgebaseArticle::create([
...     'title' => 'Test Article',
...     'category' => 'test',
...     'content' => 'Test content',
...     'is_active' => true
... ]);

// View article
>>> $article->slug

// Update article
>>> $article->update(['title' => 'Updated Title']);

// Delete article
>>> $article->delete();

// Get all articles
>>> App\Models\KnowledgebaseArticle::all();

// Get by category
>>> App\Models\KnowledgebaseArticle::where('category', 'billing')->get();

// Search articles
>>> App\Models\KnowledgebaseArticle::whereRaw('LOWER(title) LIKE ?', ['%router%'])->get();
```

**Deactivating Articles**:

```php
$article = KnowledgebaseArticle::find($id);
$article->is_active = false;
$article->save();
```

Deactivated articles won't appear in listings or be accessible via direct URL.

---

## Customization

### Changing Items Per Page

Edit Livewire component:

```php
// app/Livewire/Knowledgebase/Index.php
public int $perPage = 12; // Change to desired number
```

### Modifying Search Debounce

Edit view file:

```blade
{{-- resources/views/livewire/knowledgebase/index.blade.php --}}
<x-mary-input 
    wire:model.live.debounce.400ms="q"  {{-- Change 400ms to desired delay --}}
    placeholder="Search articles..." 
/>
```

### Adding New Categories

Categories are dynamic based on articles. Just create articles with new category names:

```php
KnowledgebaseArticle::create([
    'title' => 'Article Title',
    'category' => 'new-category', // New category
    'content' => '...',
]);
```

The new category will automatically appear in the filter dropdown.

### Customizing Styles

Edit view files in `resources/views/livewire/knowledgebase/` and `resources/views/livewire/docs/`.

**Example - Change card colors**:
```blade
<x-mary-card class="bg-base-300 rounded-2xl"> {{-- Changed from bg-base-200 --}}
```

**Example - Change grid columns**:
```blade
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4"> {{-- Added 4th column --}}
```

### Adding Rich Text Editor

For future enhancement, integrate a WYSIWYG editor like TinyMCE or Quill for content creation.

### Implementing Full-Text Search

For better search performance with large content:

```php
// Migration
Schema::create('knowledgebase_articles', function (Blueprint $table) {
    // ... existing columns
    $table->fullText(['title', 'content']); // Add full-text index
});

// Model query
KnowledgebaseArticle::whereRaw('MATCH(title, content) AGAINST(? IN NATURAL LANGUAGE MODE)', [$searchTerm])
    ->get();
```

Or use Laravel Scout with Algolia/Meilisearch for advanced search.

---

## Menu Integration

Updated menu files:
- `resources/views/components/menu/admin-menu.blade.php`
- `resources/views/components/menu/superadmin-menu.blade.php`
- `resources/views/components/menu/reseller-menu.blade.php`

**Changes**:
- Updated "Knowledge Base" link from `/help` to `/knowledgebase`
- "Documentation" link points to `/docs`
- Available under "Help & Support" menu section

---

## Technical Details

### Security

- **Authentication Required**: All routes protected by `auth` middleware
- **SQL Injection Prevention**: Parameterized queries
- **XSS Prevention**: Blade automatic escaping with `{{ }}` and `{!! !!}` where needed
- **CSRF Protection**: Laravel CSRF tokens
- **Input Validation**: Livewire validation rules

### Performance

- **Pagination**: Prevents loading all articles at once
- **Indexed Columns**: `slug` and `is_active` columns indexed
- **Query Optimization**: Proper use of `when()` clauses
- **Debounced Search**: Reduces unnecessary queries
- **Lazy Loading**: Livewire wire:navigate for SPA feel

### Maintenance

- **Clean Code**: Follows Laravel conventions
- **Type Hints**: Proper PHP type declarations
- **Comments**: Inline documentation where needed
- **Reusable Patterns**: Consistent with existing codebase
- **Testable**: Separated concerns for easy testing

---

## Future Enhancements

### Phase 2 - Admin Interface

- [ ] Admin UI for creating/editing articles
- [ ] WYSIWYG editor (TinyMCE/Quill)
- [ ] Image upload and management
- [ ] Markdown support
- [ ] Draft/publish workflow
- [ ] Article preview

### Phase 3 - Advanced Features

- [ ] Article versioning
- [ ] Comments/feedback system
- [ ] View count tracking
- [ ] Related articles suggestions
- [ ] Full-text search (Scout)
- [ ] Export to PDF
- [ ] Print-friendly views
- [ ] Article ratings
- [ ] Tags in addition to categories

### Phase 4 - Collaboration

- [ ] Multi-author support
- [ ] Approval workflow
- [ ] Article permissions
- [ ] Revision history
- [ ] Change tracking
- [ ] Collaborative editing

### Phase 5 - Localization

- [ ] Multi-language support
- [ ] Translation management
- [ ] RTL language support
- [ ] Language switcher

---

## Troubleshooting

### Routes Not Found

```bash
php artisan route:clear
php artisan route:cache
php artisan route:list | grep knowledgebase
```

### Views Not Rendering

```bash
php artisan view:clear
```

### No Articles Showing

```bash
# Check if articles exist
php artisan tinker
>>> App\Models\KnowledgebaseArticle::count()

# Re-run seeders if needed
php artisan db:seed --class=KnowledgebaseArticleSeeder
```

### Search Not Working

Check database connection and ensure `LIKE` operator is supported.

### 404 on Article Pages

Verify slug exists:
```bash
php artisan tinker
>>> App\Models\KnowledgebaseArticle::pluck('slug')
```

### Slug Conflicts

The system automatically handles duplicates, but if you encounter issues:

```bash
php artisan tinker
>>> $articles = App\Models\KnowledgebaseArticle::get();
>>> foreach ($articles as $article) {
...     $article->slug = null;
...     $article->save(); // Will regenerate unique slug
... }
```

---

## Support

For issues or questions:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Enable debug mode: `APP_DEBUG=true` in `.env`
3. Check browser console for JavaScript errors
4. Verify database connection
5. Ensure migrations ran successfully

---

## Credits

**Developed by**: GitHub Copilot
**Pattern Reference**: RADTik Zone model slug generation
**Design System**: MaryUI + TailwindCSS
**Framework**: Laravel 12 + Livewire 3

---

## Changelog

### Version 1.0.0 (2025-12-09)

**Initial Release**
- ✅ Database migrations for articles
- ✅ Models with unique slug generation
- ✅ Livewire components with search and filtering
- ✅ Responsive views with MaryUI
- ✅ 4 routes for knowledgebase and docs
- ✅ 13 seeded articles with comprehensive content
- ✅ Menu integration for all user roles

**Commits**:
- `8e1245a` - Add knowledgebase and documentation feature
- `508afe7` - Fix slug generation to handle duplicates

---

## License

This implementation follows the RADTik project license.
