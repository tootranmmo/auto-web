# Programmatic SEO WordPress Plugin

**Version:** 1.0.0

## Giới thiệu

Programmatic SEO là một plugin WordPress mạnh mẽ cho phép bạn tạo và quản lý hàng nghìn trang được tối ưu hóa SEO một cách tự động từ các templates và dữ liệu.

## Các tính năng chính

### 1. **Template Management** - Quản lý Templates Động
- Tạo và chỉnh sửa templates cho các trang SEO
- Hỗ trợ placeholder `{{variable}}` để thay thế dữ liệu
- Quản lý trực tiếp từ admin dashboard
- Kiểm validation templates trước khi sử dụng

### 2. **Data Source Integration** - Kết nối Dữ liệu
- **CSV**: Nhập dữ liệu từ file CSV
- **JSON**: Hỗ trợ file JSON hoặc API JSON
- **API**: Kết nối trực tiếp với external APIs
- **Database**: Truy vấn từ cơ sở dữ liệu WordPress
- Tự động đồng bộ dữ liệu

### 3. **Auto Page Generation** - Tạo Trang Tự động
- Tạo hàng nghìn trang từ template + data với một clic
- Batch generation để xử lý lớn
- Tự động tạo WordPress posts
- URL slug generation tự động

### 4. **SEO Meta Automation** - Tự động hóa SEO Meta
- Tự động tạo title tags (optimized cho SEO - max 60 chars)
- Tự động tạo meta descriptions (max 160 chars)
- Tự động tạo keywords từ dữ liệu
- Open Graph tags cho social media
- Twitter Card tags

### 5. **Schema Markup Generator** - Tạo Structured Data
- JSON-LD Schema generation tự động
- Hỗ trợ multiple schema types:
  - WebPage
  - Article
  - Product
  - BreadcrumbList
  - FAQPage
- Tự động thêm vào page head

### 6. **Internal Linking System** - Hệ thống Liên kết Nội bộ
- Tự động tạo internal links thông minh
- Contextual linking dựa trên keywords
- Tự động linking keywords tới pages liên quan
- Build link network giữa pages
- Anchor text optimization

### 7. **Dynamic Sitemap** - Sitemap Động
- Tự động generate XML sitemap cho tất cả pages
- HTML sitemap cho users
- Sitemap index cho search engines
- Auto-ping Google & Bing
- Shortcode `[pseo_sitemap]` để display HTML sitemap

### 8. **Performance Analytics** - Theo dõi Hiệu suất
- Track page views tự động
- Click tracking
- Bounce rate analysis
- Keyword ranking tracking
- Top performing pages report
- Analytics export (CSV, JSON)
- Real-time analytics dashboard

## Cài đặt

### Requirements
- WordPress 5.0+
- PHP 7.4+
- MySQL 5.6+

### Hướng dẫn cài đặt

1. **Upload Plugin**
   ```bash
   # Copy plugin folder to wp-content/plugins/
   cp -r programmatic-seo /path/to/wp-content/plugins/
   ```

2. **Activate Plugin**
   - Vào WordPress Admin > Plugins
   - Tìm "Programmatic SEO"
   - Nhấn "Activate"

3. **Initial Setup**
   - Plugin sẽ tự động create database tables
   - Flush rewrite rules
   - Register custom post types

## Hướng dẫn Sử dụng

### 1. Tạo Template

```php
// Sử dụng admin interface
1. Vào Programmatic SEO > Templates
2. Nhấn "Create New Template"
3. Nhập Template Name, Slug, Description
4. Viết content với placeholders: {{field_name}}
5. Save template

// Hoặc sử dụng code
$template_id = pseo_create_template([
    'name'              => 'Product Template',
    'slug'              => 'product-template',
    'description'       => 'Template for products',
    'template_content'  => '<h1>{{product_name}}</h1><p>{{description}}</p>',
    'data_mapping'      => [
        'title'         => 'product_name',
        'description'   => 'description',
        'slug_pattern'  => '{{product_name}}'
    ]
]);
```

### 2. Tạo Data Source

```php
// CSV Data Source
$source_id = pseo_create_data_source([
    'name'   => 'Products CSV',
    'type'   => 'csv',
    'config' => [
        'file_path'  => '/path/to/products.csv',
        'delimiter'  => ','
    ]
]);

// JSON Data Source
$source_id = pseo_create_data_source([
    'name'   => 'Products JSON',
    'type'   => 'json',
    'config' => [
        'source'    => 'https://api.example.com/products.json',
        'data_path' => 'products'
    ]
]);

// API Data Source
$source_id = pseo_create_data_source([
    'name'   => 'External API',
    'type'   => 'api',
    'config' => [
        'endpoint'   => 'https://api.example.com/v1/products',
        'headers'    => ['Authorization' => 'Bearer token'],
        'params'     => ['limit' => 100],
        'data_path'  => 'data.products'
    ]
]);
```

### 3. Generate Pages

```php
// Từ admin interface
1. Vào Programmatic SEO > Dashboard
2. Chọn Template và Data Source
3. Nhấn "Generate Pages"
4. Pages sẽ được tạo tự động

// Hoặc từ code
$generated_ids = pseo_generate_pages($template_id, $source_id);
// Returns: [post_id_1, post_id_2, ...]
```

### 4. Quản lý Internal Links

```php
// Tự động generate links cho một page
pseo_generate_links($post_id, $max_links = 5);

// Get related pages
$related = pseo_get_related_pages($post_id, $limit = 5);

// Auto-link keywords
$plugin = pseo_get_plugin();
$plugin->internal_link_manager->auto_link_keywords($post_id, [
    'keyword1',
    'keyword2'
]);
```

### 5. Monitor Analytics

```php
// Get page analytics
$analytics = pseo_get_analytics($post_id);
// Returns: ['total_views' => 100, 'days_tracked' => 30, ...]

// Get top pages
$top_pages = pseo_get_top_pages($limit = 10, $days = 30);

// Get report
$report = $plugin->analytics_manager->generate_report($days = 30);
```

### 6. Sitemap Management

```php
// Generate XML sitemap
$xml = pseo_generate_xml_sitemap();

// Generate HTML sitemap
echo pseo_generate_html_sitemap();

// Use shortcode
// [pseo_sitemap] - displays HTML sitemap on page

// Get sitemap stats
$stats = $plugin->sitemap_generator->get_statistics();
```

## API Reference

### Helper Functions

```php
// Template functions
pseo_create_template($args)          // Create new template
pseo_get_template($id)               // Get template by ID
pseo_get_templates()                 // Get all templates
pseo_render_template($id, $data)     // Render template with data
pseo_get_template_variables($id)     // Get template variables

// Data source functions
pseo_create_data_source($args)       // Create data source
pseo_get_data_source($id)            // Get data source
pseo_fetch_data($id)                 // Fetch data from source

// Page generation
pseo_generate_pages($template_id, $data_source_id)

// Analytics
pseo_get_analytics($post_id)         // Get page analytics
pseo_get_top_pages($limit, $days)   // Get top performing pages

// Links
pseo_generate_links($post_id)        // Generate internal links
pseo_get_related_pages($post_id)     // Get related pages

// Sitemap
pseo_generate_xml_sitemap()          // Generate XML sitemap
pseo_generate_html_sitemap()         // Generate HTML sitemap

// Settings
pseo_get_setting($key)               // Get plugin setting
pseo_update_setting($key, $value)    // Update setting

// Utility
pseo_is_programmatic_page($post_id)  // Check if page is pseo page
pseo_get_meta($post_id, $key)        // Get page meta
pseo_update_meta($post_id, $key, $value) // Update meta
```

### Hooks & Filters

```php
// Filter generated SEO meta
add_filter('pseo_generate_seo_meta', function($meta, $row, $template) {
    // Customize meta generation
    return $meta;
}, 10, 3);

// Hook after page generation
add_action('pseo_page_generated', function($post_id, $template, $data) {
    // Do something after page is generated
}, 10, 3);

// Filter internal links
add_filter('pseo_internal_links', function($links, $post_id) {
    // Customize internal links
    return $links;
}, 10, 2);

// Hook before page deletion
add_action('pseo_before_delete_page', function($post_id) {
    // Cleanup before page deletion
}, 10);
```

## Database Tables

Plugin tạo các bảng sau:

- `wp_pseo_templates` - Lưu templates
- `wp_pseo_data_sources` - Lưu data sources
- `wp_pseo_generated_pages` - Lưu thông tin pages được tạo
- `wp_pseo_internal_links` - Lưu internal links
- `wp_pseo_analytics` - Lưu analytics data
- `wp_pseo_keyword_rankings` - Lưu keyword rankings

## Best Practices

### 1. Template Design
- Sử dụng semantic HTML
- Ensure valid HTML structure
- Optimize for readability

### 2. Data Quality
- Validate data trước khi import
- Remove duplicates
- Ensure required fields exist

### 3. Performance
- Generate pages in batches (1000 at a time)
- Use appropriate data sources (API caching)
- Monitor database size

### 4. SEO Optimization
- Unique titles & descriptions cho mỗi page
- Target specific keywords
- Build natural link structure
- Create valuable content

## Troubleshooting

### Pages not being generated
1. Check template validity
2. Verify data source connection
3. Check database permissions
4. Review error logs

### Internal links not showing
1. Enable internal linking in settings
2. Ensure pages contain anchor text
3. Check link generation is completed

### Analytics not tracking
1. Verify analytics table exists
2. Check page is published
3. Wait for page views to accumulate

## Support & Docs

- Documentation: https://example.com/docs
- GitHub Issues: https://github.com/example/programmatic-seo/issues
- Support Forum: https://example.com/support

## License

GPL-2.0+ License

## Changelog

### 1.0.0 - Initial Release
- Template management system
- Data source integration (CSV, JSON, API, Database)
- Auto page generation
- SEO meta automation
- Schema markup generation
- Internal linking system
- Dynamic sitemap generation
- Performance analytics
- Admin dashboard
- Helper functions & API

---

**Made with ❤️ for WordPress SEO**
