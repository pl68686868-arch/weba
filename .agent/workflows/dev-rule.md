---
description: Premium development standards for weba project
---

# Developer Rule: Premium Psychology & Mindfulness Website

## 🎯 CORE PRINCIPLES

### 1. Implementation Plan Adherence
- **ALWAYS** reference `/Users/phamdung/.gemini/antigravity/brain/6d6278b1-2016-44b4-883f-97cbdde8b543/implementation_plan.md` before coding
- Follow the exact database schema, file structure, and API design specified
- Implement features in the order defined in `task.md`
- Never deviate from planned architecture without updating the plan first

### 2. Code Quality Standards

#### Clean Code Principles
- **Single Responsibility**: Each function/class does ONE thing
- **DRY**: Don't repeat yourself - extract reusable utilities to `includes/functions.php`
- **Meaningful Names**: Use descriptive variable/function names (Vietnamese OK for content, English for code)
- **Short Functions**: Max 20-30 lines per function
- **Clear Comments**: Document WHY, not WHAT (code should be self-documenting)

#### PHP Standards
```php
// ✅ GOOD
function calculateReadingTime(string $content): int {
    $wordCount = str_word_count(strip_tags($content));
    return ceil($wordCount / 200); // Average 200 words per minute
}

// ❌ BAD
function calc($c) {
    $w = str_word_count(strip_tags($c));
    return ceil($w / 200);
}
```

- Use **type hints** for all function parameters and return types
- Use **strict_types** declaration at top of each PHP file: `declare(strict_types=1);`
- Follow **PSR-12** coding standard
- Use **prepared statements** for ALL database queries (never string concatenation)
- Implement **error handling** with try-catch blocks
- Use **namespaces** for better organization

#### Database Standards
- **Indexes**: Add indexes on all foreign keys and frequently queried columns
- **Constraints**: Use foreign key constraints with proper CASCADE/SET NULL
- **Normalization**: Follow 3NF (Third Normal Form)
- **Naming**: snake_case for tables/columns, singular for table names
- **Timestamps**: Always include `created_at` and `updated_at`

### 3. Security Requirements (CRITICAL)

#### Input Validation
```php
// ALWAYS validate and sanitize user input
$title = filter_var($_POST['title'], FILTER_SANITIZE_STRING);
$email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);

// Use prepared statements
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = :id");
$stmt->execute(['id' => $postId]);
```

#### Security Checklist (MUST IMPLEMENT ALL)
- [ ] **SQL Injection**: Use PDO prepared statements exclusively
- [ ] **XSS Prevention**: Escape output with `htmlspecialchars($var, ENT_QUOTES, 'UTF-8')`
- [ ] **CSRF Protection**: Generate and validate tokens on all forms
- [ ] **Password Security**: Use `password_hash()` with `PASSWORD_BCRYPT`
- [ ] **File Upload**: Validate file type, size, and rename uploaded files
- [ ] **Session Security**: Regenerate session ID after login
- [ ] **HTTPS**: Force HTTPS in `.htaccess`
- [ ] **Headers**: Set security headers (CSP, X-Frame-Options, etc.)

### 4. Premium UX/UI Standards

#### Design Consistency
- **Colors**: ONLY use colors from plan palette (#FAF9F6, #F5F3EE, #2C5F4F, #3A7D6B)
- **Typography**: Lora for headings, Crimson Text for body, Inter for UI
- **Spacing**: Use multiples of 8px (8, 16, 24, 32, 40, 48...)
- **Breathing Space**: Minimum 60px padding on sections, 24px between elements

#### CSS Architecture
```css
/* Use BEM naming convention */
.article-card { }
.article-card__title { }
.article-card__excerpt { }
.article-card--featured { }

/* Mobile-first responsive */
.container {
    padding: 1rem; /* Mobile default */
}

@media (min-width: 768px) {
    .container {
        padding: 2rem; /* Tablet */
    }
}

@media (min-width: 1024px) {
    .container {
        padding: 3rem; /* Desktop */
    }
}
```

#### Animation Guidelines
- Duration: 0.2s-0.3s for micro-interactions, 0.5s for larger movements
- Easing: `ease-in-out` for most cases, `ease-out` for entrances
- Subtle: Avoid flashy animations, prefer gentle fades and smooth transitions
- Performance: Use `transform` and `opacity` (GPU accelerated), avoid animating `width/height`

### 5. Performance Optimization

#### Frontend Performance
- [ ] **Images**: Convert to WebP, implement lazy loading
- [ ] **CSS**: Inline critical CSS, defer non-critical
- [ ] **JavaScript**: Defer/async non-critical scripts
- [ ] **Fonts**: Use `font-display: swap` to prevent FOIT
- [ ] **Minification**: Minify CSS/JS in production
- [ ] **Caching**: Leverage browser caching (set in `.htaccess`)

#### Backend Performance
- [ ] **Database**: Use indexes, avoid N+1 queries
- [ ] **Caching**: Implement page cache, object cache, query cache
- [ ] **Pagination**: Limit query results (e.g., 10 posts per page)
- [ ] **Lazy Loading**: Load heavy content on demand

### 6. SEO Implementation (CRITICAL)

#### Every Page MUST Have
```php
// In <head>
<title><?= htmlspecialchars($pageTitle) ?> | Dương Trần Minh Đoan</title>
<meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
<meta name="keywords" content="<?= htmlspecialchars($pageKeywords) ?>">
<link rel="canonical" href="<?= $canonicalUrl ?>">

<!-- Open Graph -->
<meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?>">
<meta property="og:description" content="<?= htmlspecialchars($pageDescription) ?>">
<meta property="og:image" content="<?= $ogImage ?>">
<meta property="og:url" content="<?= $canonicalUrl ?>">

<!-- Schema.org -->
<script type="application/ld+json">
<?= json_encode($schemaData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
</script>
```

#### SEO Checklist
- [ ] Unique title tags (50-60 characters)
- [ ] Meta descriptions (150-160 characters)
- [ ] H1 tag (only ONE per page)
- [ ] Proper heading hierarchy (H1 → H2 → H3)
- [ ] Alt text on all images
- [ ] Clean URLs (no query strings, use mod_rewrite)
- [ ] XML sitemap
- [ ] robots.txt
- [ ] Structured data (Article, Person, BreadcrumbList)

### 7. Accessibility (WCAG 2.1)

- [ ] **Semantic HTML**: Use proper HTML5 tags (`<article>`, `<nav>`, `<main>`, etc.)
- [ ] **Alt Text**: Descriptive alt text for all images
- [ ] **Keyboard Navigation**: All interactive elements accessible via keyboard
- [ ] **Focus Indicators**: Visible focus states on all interactive elements
- [ ] **Color Contrast**: Minimum 4.5:1 for normal text, 3:1 for large text
- [ ] **ARIA Labels**: Use when semantic HTML isn't enough
- [ ] **Skip Links**: "Skip to content" link for screen readers

### 8. File Organization

```
weba/
├── api/                    # API endpoints (JSON responses)
│   ├── posts.php
│   ├── categories.php
│   └── auth.php
├── admin/                  # Admin panel (secured)
│   ├── login.php
│   ├── dashboard.php
│   └── posts.php
├── assets/
│   ├── css/
│   │   ├── style.css      # Main frontend styles
│   │   └── admin-style.css
│   ├── js/
│   │   ├── main.js        # Frontend JavaScript
│   │   └── admin.js
│   └── images/
├── config/
│   └── config.php         # Database & environment config
├── database/
│   └── schema.sql         # Complete database schema
├── includes/              # Reusable PHP includes
│   ├── Database.php       # Database class
│   ├── Auth.php          # Authentication class
│   ├── SEO.php           # SEO utilities
│   ├── Cache.php         # Caching system
│   ├── functions.php     # Helper functions
│   ├── header.php        # Shared header
│   └── footer.php        # Shared footer
├── uploads/              # User uploaded files (gitignored)
├── cache/                # Cache files (gitignored)
├── index.php             # Homepage
├── about.php
├── writing.php
├── post.php
├── teaching.php
├── podcast.php
├── contact.php
├── search.php
├── tag.php
├── 404.php
├── .htaccess
├── robots.txt
└── sitemap.xml.php
```

### 9. Testing Requirements

Before marking ANY feature as complete:
- [ ] Test on Chrome, Firefox, Safari
- [ ] Test responsive on mobile (375px), tablet (768px), desktop (1440px)
- [ ] Validate HTML (W3C validator)
- [ ] Test with keyboard navigation
- [ ] Check console for JavaScript errors
- [ ] Verify database queries are optimized (no N+1)
- [ ] Test SEO with Google Rich Results Test
- [ ] Check PageSpeed Insights score (target: 90+)

### 10. Error Handling

```php
// ALWAYS implement proper error handling
try {
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = :id");
    $stmt->execute(['id' => $postId]);
    $post = $stmt->fetch();
    
    if (!$post) {
        http_response_code(404);
        require '404.php';
        exit;
    }
    
} catch (PDOException $e) {
    error_log($e->getMessage()); // Log to file, never display to user
    http_response_code(500);
    echo "Đã có lỗi xảy ra. Vui lòng thử lại sau.";
    exit;
}
```

### 11. Content Guidelines

#### Vietnamese Language Best Practices
- Use proper Vietnamese diacritics (á, à, ả, ã, ạ, etc.)
- Database charset: `utf8mb4_unicode_ci`
- HTML charset: `<meta charset="UTF-8">`
- Respectful tone (anh/chị, quý vị for formal contexts)

#### Breathing Space Aesthetic
- Never cramped layouts
- Generous line-height (1.8 for body text)
- Wide margins and padding
- Minimalist approach (remove unnecessary elements)
- Focus on typography and whitespace

### 12. Git Workflow (If Using Version Control)

```bash
# Feature branch workflow
git checkout -b feature/seo-meta-tags
# Make changes
git add .
git commit -m "feat: Add comprehensive SEO meta tags system

- Implement SEO.php utility class
- Add Open Graph tags to all pages
- Generate schema.org JSON-LD for articles
- Add canonical URLs"

git checkout main
git merge feature/seo-meta-tags
```

Commit message format: `type: brief description`
- `feat`: New feature
- `fix`: Bug fix
- `refactor`: Code refactoring
- `style`: CSS/design changes
- `docs`: Documentation
- `perf`: Performance improvement

### 13. Documentation Requirements

Every PHP class MUST have PHPDoc:
```php
/**
 * SEO utility class for generating meta tags and structured data
 * 
 * @package Weba
 * @author Danny Duong
 */
class SEO {
    /**
     * Generate JSON-LD structured data for article
     * 
     * @param array $post Post data including title, content, author
     * @return string JSON-LD script tag
     */
    public function generateArticleSchema(array $post): string {
        // Implementation
    }
}
```

---

## 🚀 IMPLEMENTATION WORKFLOW

When user requests coding:

1. **Review Context**
   - Check implementation_plan.md
   - Review task.md for current status
   - Understand which feature to implement

2. **Plan Implementation**
   - Identify all files to create/modify
   - Consider dependencies
   - Think about edge cases

3. **Code with Quality**
   - Follow ALL standards above
   - Write clean, documented code
   - Implement security from the start (not afterthought)
   - Test as you go

4. **Verify Completeness**
   - All security measures in place?
   - SEO implemented?
   - Responsive design tested?
   - Accessibility considered?
   - Performance optimized?

5. **Update Task.md**
   - Mark completed items with [x]
   - Update in-progress items with [/]

---

## ⚠️ CRITICAL RULES (NEVER BREAK)

1. **NEVER** use `mysql_*` functions (deprecated) - ONLY PDO
2. **NEVER** trust user input - ALWAYS validate and sanitize
3. **NEVER** display raw error messages to users - log them instead
4. **NEVER** commit sensitive data (passwords, API keys) - use .env
5. **NEVER** use inline styles - separate CSS in style.css
6. **NEVER** write SQL with string concatenation - use prepared statements
7. **NEVER** assume data exists - always check before using
8. **NEVER** skip mobile responsive - mobile-first always

---

## 💡 PREMIUM MINDSET

This is not just a website - it's a **digital sanctuary** for mindful reflection.

Every line of code should reflect:
- **Intentionality**: Nothing accidental, everything purposeful
- **Breathing Space**: Room to think and reflect
- **Clarity**: Easy to understand and navigate
- **Respect**: For the reader's time and attention
- **Sustainability**: Code that lasts and scales

Code as if you're creating a peaceful temple for the mind, not just a website.

---

**When in doubt, ask: "Does this serve the reader's journey toward deeper self-understanding?"**

If no, remove it. If yes, refine it until it's excellent.
