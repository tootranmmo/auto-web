/**
 * Programmatic SEO Admin JavaScript
 */

jQuery(document).ready(function ($) {
    // Initialize admin interface
    initAdminInterface();
});

function initAdminInterface() {
    // Template form handling
    handleTemplateForm();

    // Data source form handling
    handleDataSourceForm();

    // Page generation
    handlePageGeneration();

    // Analytics charts
    initAnalyticsCharts();
}

/**
 * Handle template form submission
 */
function handleTemplateForm() {
    var $ = jQuery;

    $('#pseo-template-form').on('submit', function (e) {
        e.preventDefault();

        var formData = {
            action: 'pseo_save_template',
            nonce: $('#pseo-template-nonce').val(),
            template_id: $('#template_id').val(),
            name: $('#template_name').val(),
            slug: $('#template_slug').val(),
            description: $('#template_description').val(),
            content: $('#template_content').val(),
        };

        $.post(ajaxurl, formData, function (response) {
            if (response.success) {
                alert('Template saved successfully');
                location.reload();
            } else {
                alert('Error saving template: ' + response.data.message);
            }
        });
    });
}

/**
 * Handle data source form submission
 */
function handleDataSourceForm() {
    var $ = jQuery;

    $('#pseo-data-source-form').on('submit', function (e) {
        e.preventDefault();

        var sourceType = $('#source_type').val();
        var config = getSourceConfig(sourceType);

        var formData = {
            action: 'pseo_save_data_source',
            nonce: $('#pseo-source-nonce').val(),
            source_id: $('#source_id').val(),
            name: $('#source_name').val(),
            type: sourceType,
            config: JSON.stringify(config),
        };

        $.post(ajaxurl, formData, function (response) {
            if (response.success) {
                alert('Data source saved successfully');
                location.reload();
            } else {
                alert('Error saving data source: ' + response.data.message);
            }
        });
    });

    // Show/hide config fields based on source type
    $('#source_type').on('change', function () {
        updateSourceConfigFields($(this).val());
    });
}

/**
 * Get source configuration based on type
 */
function getSourceConfig(sourceType) {
    var $ = jQuery;
    var config = {};

    switch (sourceType) {
        case 'csv':
            config.file_path = $('#csv_file_path').val();
            config.delimiter = $('#csv_delimiter').val() || ',';
            break;

        case 'json':
            config.source = $('#json_source').val();
            config.data_path = $('#json_data_path').val();
            break;

        case 'api':
            config.endpoint = $('#api_endpoint').val();
            config.headers = JSON.parse($('#api_headers').val() || '{}');
            config.params = JSON.parse($('#api_params').val() || '{}');
            config.data_path = $('#api_data_path').val();
            break;

        case 'database':
            config.query = $('#db_query').val();
            break;
    }

    return config;
}

/**
 * Update configuration fields based on source type
 */
function updateSourceConfigFields(sourceType) {
    var $ = jQuery;
    var $configFields = $('.source-config-fields');

    $configFields.hide();

    switch (sourceType) {
        case 'csv':
            $('#csv-config').show();
            break;

        case 'json':
            $('#json-config').show();
            break;

        case 'api':
            $('#api-config').show();
            break;

        case 'database':
            $('#db-config').show();
            break;
    }
}

/**
 * Handle page generation
 */
function handlePageGeneration() {
    var $ = jQuery;

    $('#pseo-generate-btn').on('click', function () {
        var templateId = $('#pseo-template-select').val();
        var dataSourceId = $('#pseo-data-source-select').val();

        if (!templateId || !dataSourceId) {
            alert('Please select both a template and a data source');
            return;
        }

        if (!confirm('This will generate multiple pages. Continue?')) {
            return;
        }

        var $btn = $(this);
        var originalText = $btn.text();

        $btn.prop('disabled', true).text('Generating...');

        $.post(ajaxurl, {
            action: 'pseo_generate_pages',
            nonce: $('#pseo-generate-nonce').val(),
            template_id: templateId,
            data_source_id: dataSourceId,
        }, function (response) {
            $btn.prop('disabled', false).text(originalText);

            if (response.success) {
                alert('Generated ' + response.data.count + ' pages');
                location.reload();
            } else {
                alert('Error generating pages: ' + response.data.message);
            }
        });
    });
}

/**
 * Initialize analytics charts
 */
function initAnalyticsCharts() {
    // Charts will be initialized if Chart.js library is available
    if (typeof Chart === 'undefined') {
        return;
    }

    var $ = jQuery;
    var $chartContainer = $('#pseo-analytics-chart');

    if ($chartContainer.length === 0) {
        return;
    }

    // Chart initialization code
    var ctx = $chartContainer[0].getContext('2d');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [
                {
                    label: 'Page Views',
                    data: [],
                    borderColor: '#0073aa',
                    backgroundColor: 'rgba(0, 115, 170, 0.1)',
                    tension: 0.3,
                },
            ],
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Analytics Overview',
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                },
            },
        },
    });
}

/**
 * Utility: Format numbers with commas
 */
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

/**
 * Utility: Format date
 */
function formatDate(date) {
    var d = new Date(date);
    return d.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}
