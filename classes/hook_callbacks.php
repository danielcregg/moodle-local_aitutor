<?php
namespace local_aitutor;

defined('MOODLE_INTERNAL') || die();

/**
 * Injects the tutor JS into quiz-attempt pages via the footer hook.
 */
class hook_callbacks {

    public static function inject_tutor(\core\hook\output\before_standard_footer_html_generation $hook): void {
        global $PAGE;

        if (!get_config('local_aitutor', 'enabled')) {
            return;
        }
        // Only on quiz attempt pages.
        if (strpos($PAGE->pagetype ?? '', 'mod-quiz-attempt') !== 0) {
            return;
        }

        $config = [
            'ajaxurl'  => (new \moodle_url('/local/aitutor/ajax.php'))->out(false),
            'sesskey'  => sesskey(),
            'cmid'     => isset($PAGE->cm->id) ? (int) $PAGE->cm->id : 0,
            'maxhints' => (int) (get_config('local_aitutor', 'maxhints') ?: 3),
            'label'    => get_string('hintbutton', 'local_aitutor'),
        ];
        $jsurl = (new \moodle_url('/local/aitutor/tutor.js', ['v' => get_config('local_aitutor', 'version')]))->out(false);

        $html  = '<script>window.AITUTOR=' . json_encode($config, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) . ';</script>';
        $html .= '<script defer src="' . $jsurl . '"></script>';
        $hook->add_html($html);
    }
}
