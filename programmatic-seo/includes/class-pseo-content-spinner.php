<?php
/**
 * Content Spinner Class
 * Creates content variations to avoid duplicate content
 */

if (!defined('ABSPATH')) {
    exit;
}

class PSEO_Content_Spinner {

    private $synonym_database = array();

    public function __construct() {
        $this->load_synonyms();
    }

    /**
     * Load synonym database
     */
    private function load_synonyms() {
        $this->synonym_database = array(
            'good' => array('great', 'excellent', 'wonderful', 'fantastic', 'superb'),
            'bad' => array('poor', 'terrible', 'awful', 'horrible', 'dreadful'),
            'big' => array('large', 'huge', 'enormous', 'massive', 'gigantic'),
            'small' => array('tiny', 'little', 'mini', 'compact', 'petite'),
            'beautiful' => array('gorgeous', 'stunning', 'attractive', 'lovely', 'pretty'),
            'important' => array('significant', 'crucial', 'essential', 'vital', 'critical'),
            'fast' => array('quick', 'rapid', 'swift', 'speedy', 'hasty'),
            'slow' => array('sluggish', 'gradual', 'leisurely', 'unhurried', 'steady'),
            'easy' => array('simple', 'straightforward', 'effortless', 'uncomplicated', 'basic'),
            'difficult' => array('hard', 'challenging', 'tough', 'complex', 'complicated'),
            'new' => array('recent', 'fresh', 'modern', 'latest', 'current'),
            'old' => array('ancient', 'aged', 'vintage', 'antique', 'historic'),
            'happy' => array('joyful', 'delighted', 'pleased', 'cheerful', 'content'),
            'sad' => array('unhappy', 'sorrowful', 'melancholy', 'depressed', 'downcast'),
            'help' => array('assist', 'aid', 'support', 'guide', 'facilitate'),
            'show' => array('display', 'demonstrate', 'reveal', 'present', 'exhibit'),
            'make' => array('create', 'produce', 'build', 'construct', 'generate'),
            'use' => array('utilize', 'employ', 'apply', 'implement', 'leverage'),
            'find' => array('discover', 'locate', 'identify', 'detect', 'uncover'),
            'get' => array('obtain', 'acquire', 'receive', 'gain', 'secure'),
            'give' => array('provide', 'offer', 'supply', 'deliver', 'grant'),
            'think' => array('consider', 'believe', 'suppose', 'assume', 'reckon'),
            'know' => array('understand', 'comprehend', 'realize', 'recognize', 'grasp'),
            'want' => array('desire', 'wish', 'need', 'require', 'seek'),
            'very' => array('extremely', 'highly', 'remarkably', 'exceptionally', 'incredibly'),
            'also' => array('additionally', 'furthermore', 'moreover', 'besides', 'likewise'),
            'however' => array('nevertheless', 'nonetheless', 'yet', 'still', 'although'),
            'because' => array('since', 'as', 'due to', 'owing to', 'given that'),
            'many' => array('numerous', 'various', 'several', 'multiple', 'countless'),
            'different' => array('various', 'diverse', 'distinct', 'separate', 'unique'),
            'best' => array('finest', 'top', 'premier', 'supreme', 'optimal'),
            'popular' => array('well-known', 'famous', 'renowned', 'celebrated', 'prominent')
        );

        // Allow custom synonyms from options
        $custom_synonyms = get_option('pseo_custom_synonyms', array());
        if (!empty($custom_synonyms)) {
            $this->synonym_database = array_merge($this->synonym_database, $custom_synonyms);
        }
    }

    /**
     * Spin content with variations
     */
    public function spin_content($content, $variation_level = 'medium') {
        $spun_content = $content;

        switch ($variation_level) {
            case 'low':
                $spun_content = $this->spin_with_synonyms($content, 0.1); // 10% replacement
                break;

            case 'medium':
                $spun_content = $this->spin_with_synonyms($content, 0.3); // 30% replacement
                $spun_content = $this->vary_sentence_structure($spun_content, 0.2); // 20% variation
                break;

            case 'high':
                $spun_content = $this->spin_with_synonyms($content, 0.5); // 50% replacement
                $spun_content = $this->vary_sentence_structure($spun_content, 0.4); // 40% variation
                $spun_content = $this->add_transitional_phrases($spun_content);
                break;
        }

        return $spun_content;
    }

    /**
     * Spin content using synonyms
     */
    private function spin_with_synonyms($content, $replacement_rate = 0.3) {
        $words = str_word_count(strtolower($content), 1);

        foreach ($this->synonym_database as $word => $synonyms) {
            if (rand(0, 100) / 100 > $replacement_rate) {
                continue; // Skip based on replacement rate
            }

            if (in_array($word, $words)) {
                $synonym = $synonyms[array_rand($synonyms)];

                // Replace whole words only
                $pattern = '/\b' . preg_quote($word, '/') . '\b/i';
                $content = preg_replace_callback($pattern, function($matches) use ($synonym) {
                    // Preserve original case
                    if (ctype_upper($matches[0][0])) {
                        return ucfirst($synonym);
                    }
                    return $synonym;
                }, $content, 1); // Replace only first occurrence
            }
        }

        return $content;
    }

    /**
     * Vary sentence structure
     */
    private function vary_sentence_structure($content, $variation_rate = 0.2) {
        // Split into sentences
        $sentences = preg_split('/(?<=[.!?])\s+/', $content, -1, PREG_SPLIT_NO_EMPTY);

        $varied_sentences = array();

        foreach ($sentences as $sentence) {
            if (rand(0, 100) / 100 <= $variation_rate) {
                // Apply variations
                $sentence = $this->apply_sentence_variation($sentence);
            }

            $varied_sentences[] = $sentence;
        }

        return implode(' ', $varied_sentences);
    }

    /**
     * Apply variations to sentence
     */
    private function apply_sentence_variation($sentence) {
        $variations = array(
            // Passive to active or vice versa
            function($s) {
                if (preg_match('/\bis\s+(\w+ed)\b/i', $s)) {
                    return preg_replace('/\bis\s+(\w+ed)\b/i', '$1', $s, 1);
                }
                return $s;
            },

            // Add transition words at beginning
            function($s) {
                $transitions = array('Moreover, ', 'Furthermore, ', 'Additionally, ', 'In addition, ', 'Besides, ');
                return $transitions[array_rand($transitions)] . lcfirst($s);
            },

            // Swap clause order (simple cases)
            function($s) {
                if (strpos($s, ', ') !== false) {
                    $parts = explode(', ', $s, 2);
                    if (count($parts) === 2) {
                        return ucfirst($parts[1]) . ', ' . lcfirst($parts[0]);
                    }
                }
                return $s;
            }
        );

        // Apply random variation
        $variation = $variations[array_rand($variations)];
        return $variation($sentence);
    }

    /**
     * Add transitional phrases
     */
    private function add_transitional_phrases($content) {
        $paragraphs = explode("\n\n", $content);

        $transitions = array(
            'On the other hand',
            'In contrast',
            'Similarly',
            'As a result',
            'Consequently',
            'For instance',
            'For example',
            'In particular',
            'Specifically',
            'Indeed',
            'In fact',
            'Actually'
        );

        $new_paragraphs = array();

        foreach ($paragraphs as $index => $paragraph) {
            if ($index > 0 && rand(0, 100) < 30) { // 30% chance
                $transition = $transitions[array_rand($transitions)];
                $paragraph = $transition . ', ' . lcfirst($paragraph);
            }

            $new_paragraphs[] = $paragraph;
        }

        return implode("\n\n", $new_paragraphs);
    }

    /**
     * Generate spintax format
     */
    public function generate_spintax($words) {
        if (!is_array($words)) {
            $words = array($words);
        }

        $spintax_parts = array();

        foreach ($words as $word) {
            $word = strtolower(trim($word));

            // Get synonyms
            $variations = array($word);

            if (isset($this->synonym_database[$word])) {
                $variations = array_merge($variations, $this->synonym_database[$word]);
            }

            $spintax_parts[] = '{' . implode('|', array_unique($variations)) . '}';
        }

        return implode(' ', $spintax_parts);
    }

    /**
     * Parse and spin spintax
     */
    public function spin_spintax($spintax) {
        while (preg_match('/\{([^{}]+)\}/', $spintax, $matches)) {
            $options = explode('|', $matches[1]);
            $selected = $options[array_rand($options)];
            $spintax = str_replace($matches[0], $selected, $spintax);
        }

        return $spintax;
    }

    /**
     * Generate multiple variations
     */
    public function generate_variations($content, $count = 5, $variation_level = 'medium') {
        $variations = array();

        for ($i = 0; $i < $count; $i++) {
            $variations[] = $this->spin_content($content, $variation_level);
        }

        return $variations;
    }

    /**
     * Calculate similarity between two texts
     */
    public function calculate_similarity($text1, $text2) {
        $text1 = strtolower(wp_strip_all_tags($text1));
        $text2 = strtolower(wp_strip_all_tags($text2));

        // Simple word-based similarity
        $words1 = str_word_count($text1, 1);
        $words2 = str_word_count($text2, 1);

        $common_words = array_intersect($words1, $words2);
        $total_words = array_unique(array_merge($words1, $words2));

        if (empty($total_words)) {
            return 0;
        }

        $similarity = (count($common_words) / count($total_words)) * 100;

        return round($similarity, 2);
    }

    /**
     * Ensure uniqueness of spun content
     */
    public function ensure_uniqueness($spun_content, $existing_contents, $min_uniqueness = 70) {
        $max_attempts = 10;
        $attempts = 0;

        while ($attempts < $max_attempts) {
            $is_unique = true;

            foreach ($existing_contents as $existing) {
                $similarity = $this->calculate_similarity($spun_content, $existing);

                if ($similarity > (100 - $min_uniqueness)) {
                    $is_unique = false;
                    break;
                }
            }

            if ($is_unique) {
                return $spun_content;
            }

            // Spin again with higher variation
            $variation_level = $attempts < 3 ? 'medium' : 'high';
            $spun_content = $this->spin_content($spun_content, $variation_level);

            $attempts++;
        }

        return $spun_content; // Return even if not unique after max attempts
    }

    /**
     * Add custom synonyms
     */
    public function add_custom_synonym($word, $synonyms) {
        $custom_synonyms = get_option('pseo_custom_synonyms', array());

        if (!is_array($synonyms)) {
            $synonyms = array($synonyms);
        }

        $custom_synonyms[$word] = $synonyms;

        update_option('pseo_custom_synonyms', $custom_synonyms);

        $this->synonym_database[$word] = $synonyms;
    }

    /**
     * Get synonym suggestions
     */
    public function get_synonym_suggestions($word) {
        $word = strtolower(trim($word));

        if (isset($this->synonym_database[$word])) {
            return $this->synonym_database[$word];
        }

        return array();
    }
}
