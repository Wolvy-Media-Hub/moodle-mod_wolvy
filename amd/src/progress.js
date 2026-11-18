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
 * Reports how much of the Wolvy video was watched, for the completion rule.
 *
 * Listens to the Wolvy Player API's raw postMessage protocol
 * (docs.wolvy.net/player-api/#raw). No outside script is loaded.
 *
 * @module     mod_wolvy/progress
 * @copyright  2026 Wolvy <https://wolvy.net>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import {getString} from 'core/str';

/** The only origin the player's messages may come from. */
const PLAYER = 'https://embed.wolvy.stream';

/** Report again after this many more percentage points. */
const STEP = 5;

/** If the player has not answered after this long, the Player API is off. */
const SILENCE_MS = 15000;

/**
 * Start listening.
 *
 * @param {number} cmid Course module id.
 * @param {number} needed Percent that completes the activity.
 * @param {number} best Best percent stored so far.
 */
export const init = (cmid, needed, best) => {
    const container = document.getElementById('mod-wolvy-player-' + cmid);
    const frame = container ? container.querySelector('iframe') : null;
    if (!frame) {
        return;
    }

    const progressNode = document.getElementById('mod-wolvy-progress-' + cmid);
    const apiOffNode = document.getElementById('mod-wolvy-apioff-' + cmid);

    let stored = best;
    let visit = 0;
    let heard = false;
    let inflight = false;
    let pending = false;

    const report = () => {
        if (inflight) {
            pending = true;
            return;
        }
        inflight = true;
        const percent = visit;

        Ajax.call([{methodname: 'mod_wolvy_report_progress', args: {cmid: cmid, percent: percent}}])[0]
            .then((result) => {
                stored = Math.max(stored, result.percent);
                return progressNode ? getString('yourprogress', 'mod_wolvy', {best: stored, needed: needed}) : null;
            })
            .then((text) => {
                if (text && progressNode) {
                    progressNode.textContent = text;
                }
                return text;
            })
            .catch(() => {
                // Not fatal: the next report carries the same or a higher value.
                return null;
            })
            .then(() => {
                inflight = false;
                if (pending) {
                    pending = false;
                    if (visit > stored) {
                        report();
                    }
                }
                return null;
            })
            .catch(() => null);
    };

    const worthReporting = () => visit > stored
        && (visit - stored >= STEP || (visit >= needed && stored < needed) || visit >= 100);

    window.addEventListener('message', (event) => {
        if (event.origin !== PLAYER || event.source !== frame.contentWindow) {
            return;
        }
        const message = event.data;
        if (!message || message.type !== 'wolvy:player') {
            return;
        }
        heard = true;
        if (apiOffNode) {
            apiOffNode.hidden = true;
        }

        if (message.event === 'progress' && message.data) {
            const percent = Number(message.data.percent);
            if (Number.isFinite(percent)) {
                visit = Math.max(visit, Math.min(100, percent));
                if (worthReporting()) {
                    report();
                }
            }
        } else if (message.event === 'ended' && visit > stored) {
            report();
        }
    });

    // Ask the player whether it is there; it answers only when the Player API is on.
    const ping = () => {
        if (!heard && frame.contentWindow) {
            frame.contentWindow.postMessage({type: 'wolvy:player', v: 1, id: 'mod_wolvy_ping', method: 'ping'}, PLAYER);
        }
    };
    frame.addEventListener('load', () => {
        ping();
        const timer = window.setInterval(ping, 3000);
        window.setTimeout(() => {
            window.clearInterval(timer);
            if (!heard && apiOffNode) {
                apiOffNode.hidden = false;
            }
        }, SILENCE_MS);
    });
};
