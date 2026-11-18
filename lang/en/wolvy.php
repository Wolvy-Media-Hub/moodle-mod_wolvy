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
 * English strings for mod_wolvy.
 *
 * @package   mod_wolvy
 * @copyright 2026 Wolvy <https://wolvy.net>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['aspect'] = 'Shape';
$string['aspect_default'] = 'The default set in the Wolvy video filter';
$string['completiondetail:percent'] = 'Watch at least {$a}% of the video';
$string['completionpercent'] = 'Student must watch at least';
$string['completionpercent_help'] = 'The activity is complete when the student has watched at least this share of the video in one visit. The player counts seconds actually played, each once: skipping ahead or replaying a part does not add to it. It is reported by the student\'s browser, so a determined student can fake it. It needs the Player API switched on for your Wolvy account.';
$string['completionpercentrange'] = 'Enter a percentage from 1 to 100.';
$string['invalidvideoid'] = 'This is not a Wolvy video ID. It has 4 to 64 letters, digits, dots, dashes or underscores.';
$string['modulename'] = 'Wolvy video';
$string['modulename_help'] = 'Shows one video protected by Wolvy. Each student\'s player carries a token signed by this site, so the watermark shows that student. The activity can be marked complete when the student has watched a set share of the video.';
$string['modulenameplural'] = 'Wolvy videos';
$string['playerapioff'] = 'The player did not answer, so viewing progress cannot be recorded and the "watch at least" completion rule cannot be met. Switch on the Player API for your account in the Wolvy dashboard. Only teachers see this message.';
$string['pluginadministration'] = 'Wolvy video administration';
$string['pluginname'] = 'Wolvy video';
$string['privacy:metadata:wolvy'] = 'To play the video, the player loads from Wolvy (wolvy.net) with a viewer token signed by this site. The token carries a viewer ID for the user watching, which Wolvy shows on the video watermark and records with each viewing session.';
$string['privacy:metadata:wolvy:viewerid'] = 'The viewer ID, built from the template in the Wolvy video filter settings (by default the Moodle user ID).';
$string['privacy:metadata:wolvy_progress'] = 'The most of each video a user watched in one visit, for the completion rule.';
$string['privacy:metadata:wolvy_progress:percent'] = 'The share of the video watched, from 0 to 100.';
$string['privacy:metadata:wolvy_progress:timemodified'] = 'When the share last increased.';
$string['privacy:metadata:wolvy_progress:userid'] = 'The user who watched.';
$string['resetprogress'] = 'Delete what students have watched';
$string['videoid'] = 'Wolvy video ID';
$string['videoid_help'] = 'The video\'s ID in Wolvy, as shown in the Wolvy dashboard and in the video\'s embed code. For example: a1b2c3d4e5f60718293a.';
$string['wolvy:addinstance'] = 'Add a Wolvy video activity';
$string['wolvy:view'] = 'View a Wolvy video activity';
$string['yourprogress'] = 'You have watched {$a->best}% of this video in one visit. {$a->needed}% completes it.';
