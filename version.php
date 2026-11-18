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
 * Version metadata for mod_wolvy.
 *
 * @package   mod_wolvy
 * @copyright 2026 Wolvy <https://wolvy.net>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'mod_wolvy';
$plugin->version = 2026111800;
$plugin->release = '1.0.0';
$plugin->requires = 2024100700; // Moodle 4.5.
$plugin->supported = [405, 503]; // Moodle 4.5 (LTS) to 5.3 (LTS).
$plugin->maturity = MATURITY_STABLE;
// The account ID, the signing secret and the player markup live in the filter.
$plugin->dependencies = ['filter_wolvy' => 2026111800];
