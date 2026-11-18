# Wolvy video activity for Moodle (`mod_wolvy`)

An activity that shows one protected [Wolvy](https://wolvy.net) video, and can be marked complete when the student has watched a set share of it.

- **Per-student watermark.** The player carries a viewer token signed on your Moodle server for the student watching, as in the [Wolvy video filter](https://github.com/MAIM-For-Digital-Solutions/moodle-filter_wolvy), which this activity uses.
- **Completion: "Student must watch at least N %".** The player counts seconds actually played, each once, so skipping ahead or replaying one part does not add to it. The best share from any single visit is kept per student.
- **Also:** "View" completion, course reset, backup and restore with or without user data, and the Privacy API (export and delete).

## Requirements

- Moodle 4.5 (LTS) to 5.3 (LTS).
- The Wolvy video filter (`filter_wolvy`), installed and configured with your account ID and signing secret. The filter does not have to be switched on for this activity to work.
- For the completion rule: the **Player API** switched on for your account in the Wolvy dashboard. Without it the player reports nothing; teachers see a warning on the activity page after 15 seconds.

## Install

1. Install the Wolvy video filter first.
2. Copy this folder to `mod/wolvy` in your Moodle code (on Moodle 5.1 and later, `public/mod/wolvy`), or install the ZIP under *Site administration > Plugins > Install plugins*.
3. Visit *Site administration > Notifications*.

## Using it

Add an activity, choose **Wolvy video**, give it a name and the video's ID from the Wolvy dashboard. Under *Completion conditions*, choose *Add requirements* and tick **Student must watch at least**, with a percentage (90 by default).

## How progress is recorded

The page listens to the player's `progress` messages (Wolvy Player API, raw `postMessage` protocol, checked against the origin `https://embed.wolvy.stream` and the player's own window). It sends the share to Moodle through the web service `mod_wolvy_report_progress` every 5 percentage points, when the threshold is crossed and when the video ends. Moodle keeps the highest value per student and updates completion.

Like anything a browser reports, a determined student can fake it. Wolvy measures watch time per viewer on its own servers (viewer sessions in the Wolvy dashboard and API) when you need figures you can stand behind.

## Privacy

Stored in Moodle: the best share watched per student and activity, with the time it last increased. Sent to Wolvy: the viewer ID inside the signed token (see the filter). Both are declared through the Privacy API; the stored share can be exported and deleted.

## Development

- `amd/build/progress.min.js` is a hand-written equivalent of `amd/src/progress.js`. Regenerate it with `npx grunt amd` in a Moodle checkout before a release.
- Tests: `vendor/bin/phpunit --testsuite mod_wolvy_testsuite`.

## Licence

GNU GPL v3 or later. See `LICENSE`.
