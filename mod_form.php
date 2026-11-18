<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * The settings form of a Wolvy video activity.
 *
 * @package   mod_wolvy
 * @copyright 2026 Wolvy <https://wolvy.net>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * The settings form of a Wolvy video activity.
 *
 * @package   mod_wolvy
 * @copyright 2026 Wolvy <https://wolvy.net>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_wolvy_mod_form extends moodleform_mod {
    /**
     * The form fields.
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name'), ['size' => '48']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements();

        $mform->addElement('text', 'videoid', get_string('videoid', 'wolvy'), ['size' => '40']);
        $mform->setType('videoid', PARAM_RAW_TRIMMED);
        $mform->addRule('videoid', null, 'required', null, 'client');
        $mform->addHelpButton('videoid', 'videoid', 'wolvy');

        $mform->addElement('select', 'aspect', get_string('aspect', 'wolvy'), [
            '' => get_string('aspect_default', 'wolvy'),
            '16:9' => '16:9',
            '4:3' => '4:3',
            '1:1' => '1:1',
            '21:9' => '21:9',
            '9:16' => '9:16',
        ]);

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Check the video ID and the completion percentage.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array Errors by element name.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (\filter_wolvy\text_filter::clean_video_id((string) ($data['videoid'] ?? '')) === '') {
            $errors['videoid'] = get_string('invalidvideoid', 'wolvy');
        }

        $suffix = $this->get_suffix();
        if (!empty($data['completionpercentenabled' . $suffix])) {
            $percent = (int) ($data['completionpercent' . $suffix] ?? 0);
            if ($percent < 1 || $percent > 100) {
                $errors['completionpercentgroup' . $suffix] = get_string('completionpercentrange', 'wolvy');
            }
        }

        return $errors;
    }

    /**
     * The custom completion rule: watched at least N % in one visit.
     *
     * @return string[] Names of the elements added.
     */
    public function add_completion_rules() {
        $mform = $this->_form;
        $suffix = $this->get_suffix();

        $enabledel = 'completionpercentenabled' . $suffix;
        $percentel = 'completionpercent' . $suffix;
        $groupel = 'completionpercentgroup' . $suffix;

        $group = [];
        $group[] = $mform->createElement('checkbox', $enabledel, '', get_string('completionpercent', 'wolvy'));
        $group[] = $mform->createElement('text', $percentel, '', ['size' => 3]);
        $group[] = $mform->createElement('static', 'completionpercentunit' . $suffix, '', '%');
        $mform->setType($percentel, PARAM_INT);
        $mform->addGroup($group, $groupel, '', ' ', false);
        $mform->addHelpButton($groupel, 'completionpercent', 'wolvy');
        $mform->hideIf($percentel, $enabledel, 'notchecked');

        return [$groupel];
    }

    /**
     * Is the custom rule switched on?
     *
     * @param array $data Form data.
     * @return bool
     */
    public function completion_rule_enabled($data) {
        $suffix = $this->get_suffix();

        return !empty($data['completionpercentenabled' . $suffix]) && (int) ($data['completionpercent' . $suffix] ?? 0) > 0;
    }

    /**
     * Fill the checkbox from the stored percentage; 90 % when there is none.
     *
     * @param array $defaultvalues Values passed by reference.
     */
    public function data_preprocessing(&$defaultvalues) {
        parent::data_preprocessing($defaultvalues);

        $suffix = $this->get_suffix();
        $percentel = 'completionpercent' . $suffix;

        $defaultvalues['completionpercentenabled' . $suffix] = !empty($defaultvalues[$percentel]) ? 1 : 0;
        if (empty($defaultvalues[$percentel])) {
            $defaultvalues[$percentel] = 90;
        }
    }

    /**
     * Store 0 when the rule is off or completion is not automatic.
     *
     * Also called by the bulk and default activity completion forms.
     *
     * @param stdClass $data Form data.
     */
    public function data_postprocessing($data) {
        parent::data_postprocessing($data);

        if (!empty($data->completionunlocked)) {
            $suffix = $this->get_suffix();
            $completion = $data->{'completion' . $suffix} ?? null;
            $automatic = !empty($completion) && $completion == COMPLETION_TRACKING_AUTOMATIC;

            if (empty($data->{'completionpercentenabled' . $suffix}) || !$automatic) {
                $data->{'completionpercent' . $suffix} = 0;
            }
        }
    }
}
