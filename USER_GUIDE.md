# Programmatic SEO WordPress Plugin - User Guide

## Mục lục
1. [Giới thiệu](#giới-thiệu)
2. [Cài đặt và Kích hoạt](#cài-đặt-và-kích-hoạt)
3. [Bắt đầu nhanh](#bắt-đầu-nhanh)
4. [Quản lý Templates](#quản-lý-templates)
5. [Quản lý Data Sources](#quản-lý-data-sources)
6. [Tạo Pages Tự động](#tạo-pages-tự-động)
7. [SEO Optimization](#seo-optimization)
8. [Internal Linking](#internal-linking)
9. [Analytics & Reports](#analytics--reports)
10. [Tips & Best Practices](#tips--best-practices)

---

## Giới thiệu

**Programmatic SEO** là giải pháp hoàn hảo để tạo và quản lý hàng nghìn trang SEO tự động cho WordPress. Plugin này giúp bạn:

- ⚡ Tạo pages hàng loạt từ templates
- 📊 Tích hợp dữ liệu từ CSV, JSON, API
- 🎯 Tự động tối ưu SEO cho mỗi page
- 🔗 Xây dựng internal link network tự động
- 📈 Theo dõi performance và analytics

---

## Cài đặt và Kích hoạt

### Yêu cầu hệ thống
- WordPress 5.0 hoặc cao hơn
- PHP 7.4 hoặc cao hơn
- MySQL 5.6 hoặc cao hơn
- Quyền ghi file và database

### Các bước cài đặt

#### Bước 1: Upload Plugin
```bash
# Giải nén file plugin
unzip programmatic-seo.zip

# Copy vào thư mục plugins
cp -r programmatic-seo /path/to/wordpress/wp-content/plugins/
```

#### Bước 2: Kích hoạt
1. Đăng nhập vào WordPress Admin
2. Vào **Plugins** → **Installed Plugins**
3. Tìm "Programmatic SEO"
4. Click **Activate**

#### Bước 3: Xác nhận cài đặt
Sau khi kích hoạt, plugin sẽ tự động:
- ✅ Tạo database tables
- ✅ Đăng ký custom post types
- ✅ Setup rewrite rules
- ✅ Tạo menu "Programmatic SEO" trong Admin

---

## Bắt đầu nhanh

### Workflow cơ bản

```
1. Tạo Template → 2. Add Data Source → 3. Generate Pages → 4. Monitor Analytics
```

### Example: Tạo 100 Product Pages

#### 1. Chuẩn bị dữ liệu CSV
Tạo file `products.csv`:
```csv
product_name,price,description,category
"Product A",99.99,"Description A","Electronics"
"Product B",149.99,"Description B","Home"
"Product C",79.99,"Description C","Fashion"
```

#### 2. Tạo Template
Vào **Programmatic SEO** → **Templates** → **Create New**

**Template Content:**
```html
<div class="product-page">
  <h1>{{product_name}} - Best Price: ${{price}}</h1>

  <div class="product-info">
    <p class="category">Category: {{category}}</p>
    <p class="price">Only ${{price}}</p>
  </div>

  <div class="description">
    <h2>About {{product_name}}</h2>
    <p>{{description}}</p>
  </div>

  <div class="cta">
    <button>Buy Now - ${{price}}</button>
  </div>
</div>
```

**Data Mapping:**
```json
{
  "title": "product_name",
  "description": "description",
  "slug_pattern": "{{product_name}}"
}
```

#### 3. Tạo Data Source
Vào **Programmatic SEO** → **Data Sources** → **Add New**

- **Name:** Products CSV
- **Type:** CSV
- **File Path:** `/path/to/products.csv`

Click **Save & Sync**

#### 4. Generate Pages
Vào **Programmatic SEO** → **Dashboard**

1. Select **Template:** Product Template
2. Select **Data Source:** Products CSV
3. Click **Generate Pages**
4. ✅ Done! 100 pages created

---

## Quản lý Templates

### Tạo Template mới

#### Sử dụng Admin Interface

1. Vào **Programmatic SEO** → **Templates**
2. Click **Create New Template**
3. Điền thông tin:
   - **Name:** Tên template (vd: "Product Template")
   - **Slug:** URL slug (vd: "product-template")
   - **Description:** Mô tả ngắn gọn
   - **Template Content:** Nội dung HTML với placeholders

#### Placeholders
Sử dụng `{{field_name}}` để đại diện cho dữ liệu:

```html
<h1>{{title}}</h1>
<p>{{description}}</p>
<img src="{{image_url}}" alt="{{title}}">
<span class="price">${{price}}</span>
```

#### Data Mapping
Cấu hình mapping giữa template variables và data fields:

```json
{
  "title": "product_name",
  "description": "product_description",
  "slug_pattern": "{{category}}-{{product_name}}"
}
```

### Best Practices cho Templates

#### ✅ DO:
- Sử dụng semantic HTML (`<article>`, `<section>`, `<header>`)
- Thêm alt text cho images
- Sử dụng heading hierarchy đúng (H1 → H2 → H3)
- Optimize cho mobile
- Include structured data placeholders

#### ❌ DON'T:
- Duplicate H1 tags
- Sử dụng inline styles
- Hardcode URLs
- Quá nhiều placeholders không cần thiết

### Template Examples

#### Blog Post Template
```html
<article class="blog-post">
  <header>
    <h1>{{post_title}}</h1>
    <div class="meta">
      <span class="author">By {{author_name}}</span>
      <time datetime="{{publish_date}}">{{publish_date}}</time>
    </div>
  </header>

  <div class="featured-image">
    <img src="{{featured_image}}" alt="{{post_title}}">
  </div>

  <div class="content">
    {{post_content}}
  </div>

  <footer>
    <div class="tags">{{tags}}</div>
    <div class="share">Share this article</div>
  </footer>
</article>
```

#### Location Page Template
```html
<div class="location-page">
  <h1>Best Services in {{city}}, {{state}}</h1>

  <div class="location-info">
    <h2>About {{city}}</h2>
    <p>{{city_description}}</p>
  </div>

  <div class="services">
    <h2>Our Services in {{city}}</h2>
    <ul>
      <li>Service 1 in {{city}}</li>
      <li>Service 2 in {{city}}</li>
      <li>Service 3 in {{city}}</li>
    </ul>
  </div>

  <div class="contact">
    <h2>Contact Us in {{city}}</h2>
    <p>Phone: {{phone}}</p>
    <p>Address: {{address}}, {{city}}, {{state}} {{zip}}</p>
  </div>
</div>
```

---

## Quản lý Data Sources

### Loại Data Sources hỗ trợ

#### 1. CSV Files

**Cấu hình:**
```php
Name: Products CSV
Type: CSV
Config:
  - file_path: /path/to/products.csv
  - delimiter: ,
  - encoding: UTF-8
```

**Format CSV:**
```csv
id,name,price,description
1,"Product A",99.99,"Description A"
2,"Product B",149.99,"Description B"
```

#### 2. JSON Files/URLs

**Cấu hình:**
```php
Name: Products JSON
Type: JSON
Config:
  - source: https://api.example.com/products.json
  - data_path: data.products
  - cache_ttl: 3600
```

**JSON Format:**
```json
{
  "data": {
    "products": [
      {
        "id": 1,
        "name": "Product A",
        "price": 99.99
      }
    ]
  }
}
```

#### 3. External API

**Cấu hình:**
```php
Name: External API
Type: API
Config:
  - endpoint: https://api.example.com/v1/products
  - method: GET
  - headers:
      Authorization: Bearer YOUR_TOKEN
  - params:
      limit: 100
      status: active
  - data_path: results
```

#### 4. Database Query

**Cấu hình:**
```php
Name: WooCommerce Products
Type: Database
Config:
  - query: |
      SELECT
        post_title as title,
        post_content as description,
        meta_value as price
      FROM wp_posts
      LEFT JOIN wp_postmeta ON wp_posts.ID = wp_postmeta.post_id
      WHERE post_type = 'product'
      AND post_status = 'publish'
```

### Sync Data Sources

#### Auto Sync
Enable auto-sync trong settings:
```php
Settings → Data Sources → Auto Sync
- Enabled: Yes
- Interval: Every 6 hours
```

#### Manual Sync
Vào **Data Sources** → Click **Sync** button bên cạnh data source

---

## Tạo Pages Tự động

### Batch Generation

#### Từ Admin Interface
1. **Dashboard** → Select Template & Data Source
2. Click **Generate Pages**
3. Progress bar sẽ hiển thị
4. Hoàn thành → View generated pages

#### Từ Code
```php
// Generate all pages
$page_ids = pseo_generate_pages($template_id, $data_source_id);

// Returns array of post IDs
// [123, 124, 125, ...]
```

### Customization

#### Modify generation process
```php
// Hook before generation
add_action('pseo_before_generate', function($template_id, $source_id) {
    // Prepare data
    // Log activity
}, 10, 2);

// Filter generated content
add_filter('pseo_generated_content', function($content, $data, $template) {
    // Add custom sections
    // Modify structure
    return $content;
}, 10, 3);

// Hook after generation
add_action('pseo_page_generated', function($post_id, $template, $data) {
    // Send notification
    // Update external systems
}, 10, 3);
```

### Error Handling

#### Check generation status
```php
$status = pseo_get_generation_status($batch_id);

if ($status['failed'] > 0) {
    $errors = pseo_get_generation_errors($batch_id);
    foreach ($errors as $error) {
        echo $error['message'];
    }
}
```

---

## SEO Optimization

### Auto SEO Meta Tags

Plugin tự động tạo:
- ✅ Title tag (optimized cho 60 chars)
- ✅ Meta description (optimized cho 160 chars)
- ✅ Keywords
- ✅ Open Graph tags
- ✅ Twitter Card tags

### Schema Markup

#### Auto Schema Types:
- **WebPage** - Default cho tất cả pages
- **Article** - For blog posts
- **Product** - For e-commerce pages
- **LocalBusiness** - For location pages
- **FAQ** - If FAQs present

#### Custom Schema
```php
// Add custom schema
add_filter('pseo_schema_data', function($schema, $post_id, $data) {
    // Modify schema
    $schema['@type'] = 'Product';
    $schema['offers'] = [
        '@type' => 'Offer',
        'price' => $data['price'],
        'priceCurrency' => 'USD'
    ];

    return $schema;
}, 10, 3);
```

### SEO Best Practices

#### Title Optimization
```php
// Good titles:
✅ "{{product_name}} - Buy Online at Best Price"
✅ "{{service}} in {{city}} | Company Name"
✅ "Complete Guide to {{topic}} [2024]"

// Bad titles:
❌ "{{product_name}}" (too short)
❌ "Buy {{product}} cheap online best price..." (too long)
❌ "Product Page" (not unique)
```

#### Description Optimization
```php
// Good descriptions:
✅ "Discover {{product}} at ${{price}}. {{description}}. Free shipping..."
✅ "Looking for {{service}} in {{city}}? We provide professional..."

// Bad descriptions:
❌ "{{product}}" (too short)
❌ Duplicate descriptions
❌ Keyword stuffing
```

---

## Internal Linking

### Auto Internal Linking

#### Enable trong Settings
```
Settings → SEO → Internal Linking
- Auto Link: Enabled
- Max Links per Page: 5
- Link Placement: Contextual
```

### Build Link Network

#### Automatic
Plugin tự động tạo links dựa trên:
- Keyword matching
- Content similarity
- Category relationships
- Tag associations

#### Manual Control
```php
// Generate links cho specific page
pseo_generate_links($post_id, $max_links = 5);

// Auto-link keywords
$plugin = pseo_get_plugin();
$plugin->internal_link_manager->auto_link_keywords($post_id, [
    'product name',
    'category',
    'brand'
]);

// Build network cho tất cả pages
$plugin->internal_link_manager->build_link_network([], 3);
```

### Link Strategies

#### Contextual Linking
Links xuất hiện tự nhiên trong content:
```html
<p>Our {{product}} is the best choice for {{category}}...</p>
<!-- Auto converts to: -->
<p>Our <a href="/products/product-a">Product A</a> is the
best choice for <a href="/category/electronics">Electronics</a>...</p>
```

#### Related Pages
Display related pages widget:
```php
// Get related pages
$related = pseo_get_related_pages($post_id, 5);

foreach ($related as $page) {
    echo '<a href="' . get_permalink($page->ID) . '">';
    echo $page->post_title;
    echo '</a>';
}
```

---

## Analytics & Reports

### Performance Dashboard

#### Key Metrics
- **Total Page Views** - Tổng lượt xem
- **Unique Visitors** - Số visitors unique
- **Average Time on Page** - Thời gian trung bình
- **Bounce Rate** - Tỷ lệ bounce
- **CTR** - Click-through rate

#### Access Analytics
```
Programmatic SEO → Analytics
```

### Get Analytics Data

```php
// Page analytics
$analytics = pseo_get_analytics($post_id);
echo "Total Views: " . $analytics['total_views'];
echo "Avg Views/Day: " . $analytics['avg_views_per_day'];

// Top performing pages
$top_pages = pseo_get_top_pages(10, 30); // Top 10, last 30 days

foreach ($top_pages as $page) {
    echo $page->post_title . ": " . $page->total_views . " views";
}

// Generate report
$plugin = pseo_get_plugin();
$report = $plugin->analytics_manager->generate_report(30);

print_r($report);
/*
Array (
    [period] => 30 days
    [total_views] => 10500
    [total_pages] => 500
    [avg_views_per_page] => 21
    [top_pages] => Array(...)
)
*/
```

### Export Analytics

```php
// Export to CSV
$csv = $plugin->analytics_manager->export_analytics('csv', 30);

// Export to JSON
$json = $plugin->analytics_manager->export_analytics('json', 30);
```

### Keyword Tracking

```php
// Track keyword ranking
$plugin->analytics_manager->track_keyword_ranking(
    $post_id,
    'keyword phrase',
    $position = 5,
    $search_volume = 1000
);

// Get rankings
$rankings = $plugin->analytics_manager->get_keyword_rankings($post_id);
```

---

## Tips & Best Practices

### 1. Template Design
- ✅ Create semantic, SEO-friendly HTML
- ✅ Use consistent structure across templates
- ✅ Include breadcrumbs
- ✅ Add schema markup placeholders
- ✅ Optimize for mobile devices

### 2. Data Quality
- ✅ Validate data before import
- ✅ Remove duplicates
- ✅ Ensure required fields exist
- ✅ Clean and normalize text
- ✅ Use consistent formatting

### 3. Performance
- ✅ Generate pages in batches (max 1000)
- ✅ Use appropriate caching
- ✅ Optimize images before import
- ✅ Monitor database size
- ✅ Regular cleanup of old data

### 4. SEO Strategy
- ✅ Unique content for each page
- ✅ Target long-tail keywords
- ✅ Build natural internal links
- ✅ Create valuable, helpful content
- ✅ Regular content updates

### 5. Monitoring
- ✅ Track performance weekly
- ✅ Monitor top performing pages
- ✅ Identify and fix low performers
- ✅ Adjust templates based on data
- ✅ A/B test different approaches

---

## Troubleshooting

### Common Issues

#### Pages not generating
**Solutions:**
1. Check template has valid syntax
2. Verify data source connection
3. Check database permissions
4. Review PHP error logs
5. Increase PHP memory limit

#### Internal links not showing
**Solutions:**
1. Enable internal linking in settings
2. Generate links manually
3. Check link generation completed
4. Verify pages are published

#### Slow performance
**Solutions:**
1. Generate pages in smaller batches
2. Enable caching
3. Optimize database queries
4. Check server resources
5. Use object caching

---

## Support

- 📖 **Documentation:** https://example.com/docs
- 🐛 **Report Issues:** https://github.com/example/issues
- 💬 **Community:** https://example.com/community
- 📧 **Email:** support@example.com

---

**Happy Scaling with Programmatic SEO! 🚀**
