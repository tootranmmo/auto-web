/**
 * Programmatic SEO Frontend JavaScript
 */

jQuery(document).ready(function ($) {
    // Initialize frontend interface
    initFrontendInterface();
});

function initFrontendInterface() {
    var $ = jQuery;

    // Track page interactions
    trackPageViews();

    // Initialize table of contents
    initTableOfContents();

    // Initialize related pages
    initRelatedPages();

    // Initialize internal links highlighting
    initInternalLinksHighlight();
}

/**
 * Track page views and interactions
 */
function trackPageViews() {
    var $ = jQuery;

    // Track scroll depth
    var scrollPercentage = 0;
    var maxScroll = 0;

    $(window).on('scroll', function () {
        var scrollTop = $(window).scrollTop();
        var docHeight = $(document).height() - $(window).height();
        scrollPercentage = (scrollTop / docHeight) * 100;

        if (scrollPercentage > maxScroll) {
            maxScroll = scrollPercentage;
        }

        // Send tracking data on certain scroll depths
        if (maxScroll > 25 && !$(document).data('scroll-25')) {
            trackEvent('scroll_25');
            $(document).data('scroll-25', true);
        }

        if (maxScroll > 50 && !$(document).data('scroll-50')) {
            trackEvent('scroll_50');
            $(document).data('scroll-50', true);
        }

        if (maxScroll > 75 && !$(document).data('scroll-75')) {
            trackEvent('scroll_75');
            $(document).data('scroll-75', true);
        }

        if (maxScroll > 90 && !$(document).data('scroll-90')) {
            trackEvent('scroll_90');
            $(document).data('scroll-90', true);
        }
    });

    // Track link clicks
    $('a[href^="' + window.location.origin + '"]').on('click', function () {
        trackEvent('internal_link_click', {
            url: $(this).attr('href'),
            text: $(this).text(),
        });
    });
}

/**
 * Send tracking event
 */
function trackEvent(eventType, data) {
    var $ = jQuery;

    // Send event via AJAX
    $.post(pseo_ajax_url, {
        action: 'pseo_track_event',
        event_type: eventType,
        data: data ? JSON.stringify(data) : null,
    });
}

/**
 * Initialize table of contents
 */
function initTableOfContents() {
    var $ = jQuery;
    var $post = $('.pseo-page');

    if ($post.length === 0) {
        return;
    }

    var headings = $post.find('h2, h3');

    if (headings.length === 0) {
        return;
    }

    // Generate table of contents
    var $toc = $('<div class="pseo-table-of-contents"><h3>Table of Contents</h3><ul></ul></div>');
    var $list = $toc.find('ul');

    headings.each(function (index) {
        var $heading = $(this);
        var id = $heading.attr('id') || 'heading-' + index;
        var text = $heading.text();
        var level = parseInt($heading.prop('tagName').slice(1));

        if (!$heading.attr('id')) {
            $heading.attr('id', id);
        }

        var indent = (level - 2) * 20;
        $list.append(
            '<li style="margin-left: ' +
            indent +
            'px"><a href="#' +
            id +
            '">' +
            text +
            '</a></li>'
        );
    });

    if (headings.length > 3) {
        $post.prepend($toc);
    }
}

/**
 * Initialize related pages widget
 */
function initRelatedPages() {
    var $ = jQuery;

    // This can be enhanced with AJAX to load related pages dynamically
    $('.pseo-related-pages').on('click', 'a', function () {
        trackEvent('related_page_click', {
            url: $(this).attr('href'),
        });
    });
}

/**
 * Initialize internal links highlighting
 */
function initInternalLinksHighlight() {
    var $ = jQuery;

    // Highlight internal links on hover
    $('.pseo-internal-links a').on('mouseenter', function () {
        $(this).addClass('highlighted');
    });

    $('.pseo-internal-links a').on('mouseleave', function () {
        $(this).removeClass('highlighted');
    });

    // Add visual feedback
    $('a.pseo-internal-link').each(function () {
        var $link = $(this);
        var originalText = $link.text();

        $link.on('mouseenter', function () {
            $link.css({
                'background-color': '#0073aa',
                color: 'white',
            });
        });

        $link.on('mouseleave', function () {
            $link.css({
                'background-color': 'transparent',
                color: '#0073aa',
            });
        });
    });
}

/**
 * Load content via AJAX
 */
function loadContent(url, callback) {
    jQuery.ajax({
        url: url,
        type: 'GET',
        success: function (data) {
            if (typeof callback === 'function') {
                callback(data);
            }
        },
        error: function () {
            console.error('Error loading content from ' + url);
        },
    });
}

/**
 * Share functionality
 */
function initShareButtons() {
    var $ = jQuery;
    var title = document.title;
    var url = window.location.href;

    // Share on social media
    $('.pseo-share-facebook').on('click', function () {
        window.open(
            'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url),
            'facebook-share-dialog',
            'width=800,height=600'
        );
    });

    $('.pseo-share-twitter').on('click', function () {
        window.open(
            'https://twitter.com/intent/tweet?text=' +
            encodeURIComponent(title) +
            '&url=' +
            encodeURIComponent(url),
            'twitter-share-dialog',
            'width=800,height=600'
        );
    });

    $('.pseo-share-linkedin').on('click', function () {
        window.open(
            'https://www.linkedin.com/sharing/share-offsite/?url=' + encodeURIComponent(url),
            'linkedin-share-dialog',
            'width=800,height=600'
        );
    });
}

/**
 * Copy to clipboard
 */
function copyToClipboard(text) {
    var temp = jQuery('<input>');
    jQuery('body').append(temp);
    temp.val(text).select();
    document.execCommand('copy');
    temp.remove();
    alert('Copied to clipboard!');
}

/**
 * Initialize search functionality
 */
function initSearch() {
    var $ = jQuery;
    var $searchInput = $('.pseo-search-input');

    if ($searchInput.length === 0) {
        return;
    }

    $searchInput.on('keyup', function () {
        var query = $(this).val().toLowerCase();
        var $results = $('.pseo-search-results');

        if (query.length < 2) {
            $results.hide();
            return;
        }

        // Search in page content
        var content = $('.pseo-page').text().toLowerCase();
        var matches = [];

        // Simple search implementation
        var regex = new RegExp(query, 'g');
        var match;

        while ((match = regex.exec(content)) !== null) {
            matches.push(match);
        }

        if (matches.length > 0) {
            $results.show().text('Found ' + matches.length + ' matches');
        } else {
            $results.hide();
        }
    });
}
