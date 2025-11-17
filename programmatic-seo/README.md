# Programmatic SEO Pro

A powerful WordPress plugin for creating and managing programmatic SEO content at scale. Generate hundreds or thousands of SEO-optimized pages automatically using templates and data.

## Features

### 🚀 Core Features

1. **Bulk Content Generation**
   - Create hundreds of posts from a single template
   - Support for dynamic variables and data injection
   - CSV import for easy bulk content creation

2. **Advanced Template System**
   - Dynamic template engine with variables: `{{city}}`, `{{keyword}}`, etc.
   - Conditional blocks: `{{#if variable}}...{{/if}}`
   - Loops: `{{#each items}}...{{/each}}`
   - Transform functions: `{{uppercase:variable}}`, `{{lowercase:variable}}`, etc.
   - Spintax support: `{option1|option2|option3}`

3. **CSV Import & Export**
   - Import data from CSV files
   - Map CSV columns to template variables
   - Batch processing with progress tracking
   - Export generated content back to CSV

4. **Auto SEO Optimization**
   - Automatic meta description generation
   - Title optimization
   - Schema.org structured data (JSON-LD)
   - Open Graph tags
   - Twitter Card tags
   - Image alt text optimization
   - SEO score calculation

5. **Internal Linking Engine**
   - Automatically build internal links between related posts
   - Keyword-based linking
   - Category and tag-based relationships
   - Configurable link density
   - Link statistics and analytics

6. **Content Variation (Spinning)**
   - Synonym replacement to avoid duplicate content
   - Sentence structure variation
   - Configurable variation levels (low, medium, high)
   - Custom synonym database support

7. **Bulk Scheduler**
   - Schedule posts for future publishing
   - Distribute posts evenly over time
   - Multiple scheduling patterns (hourly, daily, weekly)
   - Automated publishing via cron

8. **Keyword Management**
   - Automatic keyword extraction
   - Keyword tracking and analytics
   - Related post suggestions based on keywords

9. **Analytics Dashboard**
   - Track scheduled posts
   - Internal linking statistics
   - Import history
   - SEO scores

## Installation

### Method 1: Upload via WordPress Admin

1. Download the plugin ZIP file
2. Go to WordPress Admin > Plugins > Add New
3. Click "Upload Plugin"
4. Choose the ZIP file and click "Install Now"
5. Activate the plugin

### Method 2: Manual Installation

1. Download and extract the plugin
2. Upload the `programmatic-seo` folder to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress

## Quick Start Guide

### Step 1: Create a Template

1. Go to **Programmatic SEO > Templates**
2. Click "Add New"
3. Fill in the template details:

**Title Template Example:**
```
Best {{service}} in {{city}} - {{date:Y}}
```

**Content Template Example:**
```
Looking for the best {{service}} in {{city}}? Look no further!

{{#if price}}
Our {{service}} services start at just ${{price}}.
{{/if}}

We offer {professional|expert|top-quality} {{service}} services throughout {{city}}.

Key features:
{{#each features}}
- {{this}}
{{/each}}

Contact us today for a free quote!
```

**Meta Description Template:**
```
Find the best {{service}} in {{city}}. Professional {{lowercase:service}} services starting at ${{price}}. Call now for a free quote!
```

### Step 2: Prepare Your CSV

Create a CSV file with columns matching your template variables:

```csv
city,service,price,features
New York,Plumbing,299,"24/7 Emergency Service|Licensed & Insured|5-Year Warranty"
Los Angeles,Electrical,399,"Same Day Service|Free Estimates|Certified Electricians"
Chicago,HVAC,499,"Energy Efficient|Expert Installation|Maintenance Plans"
```

### Step 3: Import & Generate

1. Go to **Programmatic SEO > Import CSV**
2. Select your template
3. Upload your CSV file
4. Choose whether to publish immediately or schedule
5. Click "Import & Generate Content"

### Step 4: Optimize & Build Links

The plugin will automatically:
- Optimize meta tags and descriptions
- Add schema markup
- Extract keywords
- Build internal links (if enabled in settings)

## Template Syntax Reference

### Variables

Simple variable replacement:
```
{{variable_name}}
{{city}}
{{keyword}}
```

With spaces (also supported):
```
{{ city }}
{{ keyword }}
```

### Functions

Transform functions:
```
{{uppercase:city}}          → NEW YORK
{{lowercase:service}}       → plumbing
{{capitalize:service}}      → Plumbing Service
{{slug:keyword}}            → seo-friendly-url
{{date:Y-m-d}}             → 2025-11-17
{{random:1:100}}           → Random number between 1-100
```

### Conditionals

Show content only if variable exists:
```
{{#if price}}
Our services start at ${{price}}.
{{/if}}
```

### Loops

Iterate over array data:
```
{{#each features}}
- {{this}}
{{/each}}
```

With array of objects:
```
{{#each products}}
Name: {{name}}
Price: ${{price}}
{{/each}}
```

### Spintax (Content Variation)

Random selection from options:
```
{Best|Top|Leading|Premium} {{service}}
We {offer|provide|deliver} {excellent|outstanding|superior} service
{Contact us|Get in touch|Reach out} today!
```

## Settings

### General Settings

- **Max Posts per Batch**: Maximum posts to process at once (default: 50)

### SEO Optimization

- **Auto SEO Optimization**: Enable automatic SEO optimization
- **Schema Markup**: Add structured data to posts

### Internal Linking

- **Auto Internal Linking**: Automatically build internal links

### Content Variation

- **Variation Level**: Choose how much to vary spun content
  - Low: 10% variation
  - Medium: 30% variation
  - High: 50% variation

### Scheduling

- **Publishing Interval**: How often to check for scheduled posts
  - Hourly
  - Twice Daily
  - Daily

## Advanced Usage

### Custom Synonyms

Add your own synonyms for content spinning:

```php
// In your theme's functions.php
add_action('init', function() {
    $spinner = new PSEO_Content_Spinner();
    $spinner->add_custom_synonym('excellent', array(
        'outstanding',
        'exceptional',
        'remarkable',
        'superb'
    ));
});
```

### Programmatic Generation

Generate content programmatically:

```php
$template_id = 1;
$data = array(
    array('city' => 'New York', 'service' => 'Plumbing'),
    array('city' => 'Los Angeles', 'service' => 'Electrical')
);

$engine = new PSEO_Template_Engine();
$result = $engine->generate_content($template_id, $data);
```

### Custom Schema Types

Supported schema types:
- Article
- BlogPosting
- NewsArticle
- Product
- Service
- FAQPage

## Best Practices

### Template Design

1. **Use descriptive variables**: `{{service_name}}` instead of `{{s}}`
2. **Include variations**: Use spintax to create unique content
3. **Keep it natural**: Don't over-optimize with keywords
4. **Test with sample data**: Preview templates before bulk generation

### Content Quality

1. **Start small**: Test with 10-20 posts before scaling up
2. **Review generated content**: Check quality before publishing
3. **Use medium variation**: Balance between uniqueness and quality
4. **Add unique elements**: Include location-specific or custom data

### SEO Optimization

1. **Optimize meta descriptions**: Keep them 150-160 characters
2. **Use proper schema**: Choose the right schema type for your content
3. **Internal linking**: 3-5 links per post is ideal
4. **Schedule wisely**: Spread posts over time to appear natural

### Performance

1. **Batch processing**: Don't generate too many posts at once
2. **Use scheduling**: Distribute posts over days/weeks
3. **Monitor server**: Watch for resource usage spikes
4. **Clean up regularly**: Remove old scheduled post records

## Troubleshooting

### Import fails or times out

- Reduce batch size in settings
- Check CSV file encoding (should be UTF-8)
- Ensure sufficient server resources (memory, execution time)

### Template variables not replaced

- Check variable names match CSV columns exactly
- Ensure proper syntax: `{{variable}}` not `{variable}`
- Verify CSV file has header row

### Internal links not created

- Enable internal linking in settings
- Ensure posts are published (not drafts)
- Check that posts have enough content for linking

### Schema markup not showing

- Enable schema markup in settings
- Verify post is published
- Check with Google's Rich Results Test

## Hooks & Filters

### Actions

```php
// Before post is generated
do_action('pseo_before_generate_post', $template_id, $data);

// After post is generated
do_action('pseo_after_generate_post', $post_id, $template_id, $data);

// Before SEO optimization
do_action('pseo_before_seo_optimize', $post_id);

// After internal links are built
do_action('pseo_after_build_links', $post_ids);
```

### Filters

```php
// Modify generated post data
add_filter('pseo_post_data', function($post_data, $template, $data) {
    // Modify post data before creation
    return $post_data;
}, 10, 3);

// Modify schema markup
add_filter('pseo_schema_markup', function($schema, $post_id) {
    // Add custom schema fields
    return $schema;
}, 10, 2);

// Modify synonym database
add_filter('pseo_synonyms', function($synonyms) {
    // Add or modify synonyms
    return $synonyms;
});
```

## FAQ

**Q: Can I use this for any post type?**
A: Yes, the plugin supports all public post types including custom post types.

**Q: Is there a limit on how many posts I can generate?**
A: No hard limit, but batch size is configurable. For best performance, process in batches of 50-100.

**Q: Will this create duplicate content issues?**
A: The content spinner helps create variations, but you should ensure your templates and data create unique, valuable content.

**Q: Can I schedule posts months in advance?**
A: Yes, you can schedule posts for any future date.

**Q: Does this work with page builders?**
A: The plugin generates standard WordPress content. Compatibility with page builders depends on how they handle content.

## Support

For issues, questions, or feature requests:
- Check the documentation
- Review the FAQ
- Contact support

## Changelog

### Version 1.0.0 (2025-11-17)

Initial release featuring:
- Template system with advanced syntax
- CSV import/export
- Auto SEO optimization
- Internal linking engine
- Content spinner
- Bulk scheduler
- Analytics dashboard

## License

GPL v2 or later

## Credits

Developed by [Your Name]

---

**Note**: This plugin is designed for creating legitimate, valuable programmatic content at scale. Please use it responsibly and ensure all generated content provides value to your users.
