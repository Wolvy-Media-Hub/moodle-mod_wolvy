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
 * Test data generator for mod_wolvy.
 *
 * @package   mod_wolvy
 * @category  test
 * @copyright 2026 Wolvy <https://wolvy.net>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_wolvy_generator extends testing_module_generator {
    /**
     * Create an instance with a valid video ID unless one is given.
     *
     * @param array|stdClass|null $record Fields.
     * @param array|null $options Options.
     * @return stdClass
     */
    public function create_instance($record = null, ?array $options = null) {
        $record = (object) (array) $record;
        $record->videoid = $record->videoid ?? 'a1b2c3d4e5f60718293a';
        $record->aspect = $record->aspect ?? '';
        $record->completionpercent = $record->completionpercent ?? 0;

        return parent::create_instance($record, (array) $options);
    }
}
