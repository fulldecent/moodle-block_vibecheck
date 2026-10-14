# Study companion

[![Lint](https://github.com/modern-training-solutions/moodle-block_vibecheck/actions/workflows/lint.yml/badge.svg)](https://github.com/modern-training-solutions/moodle-block_vibecheck/actions/workflows/lint.yml)
[![Build and test](https://github.com/modern-training-solutions/moodle-block_vibecheck/actions/workflows/build-test.yml/badge.svg)](https://github.com/modern-training-solutions/moodle-block_vibecheck/actions/workflows/build-test.yml)
[![Moodle CI](https://github.com/modern-training-solutions/moodle-block_vibecheck/actions/workflows/ci.yml/badge.svg)](https://github.com/modern-training-solutions/moodle-block_vibecheck/actions/workflows/ci.yml)
[![Moodle compatibility](https://github.com/modern-training-solutions/moodle-block_vibecheck/actions/workflows/moodle-compatibility.yml/badge.svg)](https://github.com/modern-training-solutions/moodle-block_vibecheck/actions/workflows/moodle-compatibility.yml)

Students get a focus timer, a breathing exercise, a theme switch, and a once-a-year rating after every enrolled course is complete.

The block component is `block_vibecheck`. Moodle loads it from `blocks/vibecheck`. The Git repository name is `moodle-block_vibecheck`.

## Try it out

Run a Moodle site with this block on your own computer. These commands are copied into a terminal. On macOS that terminal is Terminal.app, which is already installed. The runtime is [Apple container](https://github.com/apple/container), not Docker.

1. Install Apple container.

    On macOS, `brew install container` and `container system start`.

2. Create a folder for the playground. Other plugins can be copied into the same Moodle tree.

    ```bash
    cd ~/Developer
    mkdir -p moodle-playground && cd moodle-playground
    ```

3. Install Moodle 5.2 stable. This block's `version.php` supports branch 502.

    ```bash
    git clone --depth=1 --branch MOODLE_502_STABLE https://github.com/moodle/moodle.git moodle
    ```

4. Install the block into that Moodle tree.

    ```bash
    git clone https://github.com/modern-training-solutions/moodle-block_vibecheck.git moodle/public/blocks/vibecheck
    ```

    Moodle 5.2 serves plugins from `public/blocks`.

5. Start Postgres and a PHP container. From a clone of this repository:

    ```bash
    testing/run-tests.sh
    ```

    That script builds `vibecheck-php:8.3`, starts Postgres and Selenium on the `vibecheck` network, installs Moodle 5.2 with [moodle-plugin-ci](https://github.com/moodlehq/moodle-plugin-ci), and runs PHP lint, Moodle codechecker, the savepoint check, Mustache lint, PHPDoc, PHPUnit, and Behat. The container exits when the checks finish. It does not leave a website on localhost.

6. To click through the block, clone Moodle `MOODLE_502_STABLE` and symlink this repository to `public/blocks/vibecheck`. Point that checkout at a database, open the site, log in, turn editing on, and add the Study companion block.

## Installation

Install a published zip, or clone the repository into the Moodle tree.

The zip from the releases page unpacks to a single `vibecheck` directory. In Moodle, go to Site administration > Plugins > Install plugins, and upload that zip. Moodle rejects a zip whose top directory has a different name.

To install from git, from the Moodle root on 5.2:

```bash
git clone https://github.com/modern-training-solutions/moodle-block_vibecheck.git public/blocks/vibecheck
```

Open the site. Moodle's notifications page installs the plugin. Upgrade from the copy that lived inside `local_mts_hacks` with the blocks symlink `blocks/vibecheck`. Replace that symlink with this directory before you visit the notifications page. The component name is unchanged, so existing ratings in the `VIBE_RATING` and `VIBE_UPDATED_AT` profile fields stay put.

## Usage

Add the block from the blocks drawer on a course page or the dashboard.

The rating question is shown only when every course the user is enrolled in has a completion time, and the user has not saved a rating in the last twelve 30-day months. Good and bad call the web service `block_vibecheck_update_vibe`.

The focus timer, breathing exercise, background colour, and dark mode stay in the browser. Lofi radio loads a YouTube embed only after the student turns it on.

The setting is at Site administration > Plugins > Blocks > Study companion. `block_vibecheck/force_drawer` opens the Boost blocks drawer when the block is on the page. Production left that behaviour on. Turn it off when the drawer should stay under the student's control.

## Development

Moodle's coding style, the PHPUnit run, and the Behat scenario are what [ci.yml](.github/workflows/ci.yml) runs, on pull requests and on pushes to a protected branch.

The page script lives in [templates/content.mustache](templates/content.mustache) inside `{{#js}}`. It is one block, not a module other pages call, so there is no `amd/` build for Grunt to check.

### Testing

Pull requests run [ci.yml](.github/workflows/ci.yml). On a Mac with Apple container, run the same checks locally:

```bash
testing/run-tests.sh
```

Format the files that workflow does not cover before you push:

```bash
npx prettier@latest --write .
npx markdownlint-cli@latest "**/*.md" --fix
```

PHP syntax is checked by [build-test.yml](.github/workflows/build-test.yml), which also builds `vibecheck.zip`.

PHPUnit covers the rating rule, the block body, the web service, and privacy export and delete. Those tests boot Moodle. Behat covers adding the block and reading its tools. There is no separate unit suite with Moodle mocked out.

### Releases

This repository has two version numbers, and they are not the same number.

The template release is SemVer. Use `fix:`, `feat:` or `BREAKING CHANGE:` in your commit messages. Release Please opens a release pull request from those messages. Merging that pull request tags `v1.2.3` and the release workflow publishes `vibecheck.zip` with a SLSA provenance attestation and a version attestation. With release immutability on, GitHub also signs the release, and `gh release verify v1.2.3` checks that signature.

The plugin version is `$plugin->version` in [version.php](version.php), a `YYYYMMDDXX` integer. Moodle refuses to upgrade a plugin when this number does not increase. Release Please does not write this file. Bump it in the commit that changes plugin behavior.

The file to upload on the first Marketplace listing is `vibecheck.zip` from that GitHub release. The top directory inside the zip is `vibecheck`. The release workflow does not call the Marketplace. After the plugin exists there, later tags can be submitted by [moodle-release.yml](.github/workflows/moodle-release.yml), which calls [moodlehq/moodle-plugin-release](https://github.com/moodlehq/moodle-plugin-release). Create an API token at <https://marketplace.moodle.com/account/security> and save it as the Actions secret `MOODLE_MARKETPLACE_TOKEN` before you run that workflow. A release created with the Actions token does not start it. Catalyst's workflow can still publish to the old plugins directory when `version.php` changes and `MOODLE_ORG_TOKEN` is set. The secret in [ci.yml](.github/workflows/ci.yml) stays commented until you publish under your own component name.

In your GitHub repository settings, under Actions, General, Workflow permissions, select read and write permissions and check "Allow GitHub Actions to create and approve pull requests". Under General, Releases, enable release immutability. Release Please needs the permission. The publish job needs immutability so a release cannot be replaced after it is attested.

A repository created from this work starts with no tags. Release Please reads the latest tag on the default branch. The publish job accepts a tag shaped like `v1.2.3`.

Run these commands from a clone of the new repository. `gh` fills in `{owner}/{repo}` from that clone.

List tags:

```bash
gh api repos/{owner}/{repo}/tags --jq '.[].name'
```

Set the starting tag on the current `main` commit. `v1.1.0` matches `$plugin->release` in this tree. `v1.0.0` already shipped inside `local_mts_hacks` and is not a tag in this repository. Release Please counts forward from the tag you set.

```bash
gh api --method POST repos/{owner}/{repo}/git/refs \
  -f ref="refs/tags/v1.1.0" \
  -f sha="$(gh api repos/{owner}/{repo}/commits/main --jq .sha)"
```

`gh release list` and `gh release create` publish the releases this workflow creates after that tag.

The zip is packed as `vibecheck/`. Moodle's installer rejects a zip whose top directory does not match the component.

### Maintenance

The project administrator completes these maintenance tasks each month. If they are 3+ months late, please remind them or send your own issue or pull request.

1. Read [External actions](https://docs.github.com/en/actions/reference/security/secure-use#using-third-party-actions) versions in [.github/workflows](.github/workflows) and update the ones you have reviewed. Actions under the `actions/` organization need a shorter review.
2. Read the [Moodle compatibility](.github/workflows/moodle-compatibility.yml) run. It installs the plugin on the newest `MOODLE_*_STABLE` branch. When that run is green and [version.php](version.php) still names an older branch in `$plugin->supported`, widen that range.
3. Catalyst's Moodle workflow is taken from `@main` on purpose, so its Moodle branch cache keeps moving. Read their changelog when the [Moodle CI](.github/workflows/ci.yml) run starts failing without a change in this repository.

## Project scope

Moodle sites that already used this block inside `local_mts_hacks` need the same component name, the same web service, and the same profile fields.

This project will keep the block installable on its own, with tests a reviewer can run.

We will not publish the rest of `local_mts_hacks` from this repository. We will not add a second plugin type.

## References

1. We use title case only for proper nouns, including the name of our project.
2. This project is built based on [best practices documented in moodle-local_plugin_template](https://github.com/fulldecent/moodle-local_plugin_template), release v1.0.1.
3. This project is built based on [best practices documented in project-template](https://github.com/fulldecent/project-template), release v1.3.0.
4. [EditorConfig](.editorconfig) and the top of [.gitignore](.gitignore) are taken from project-template release 1.3.0, through that Moodle template. `/dist/` is the local copy of the release zip.
5. The interface files of a Moodle plugin are [GPL-3.0-or-later](https://moodledev.io/general/community/plugincontribution/checklist). [LICENSE.md](LICENSE.md) is that text.
6. Moodle CI on pull requests is [Catalyst's reusable workflow](https://github.com/catalyst/catalyst-moodle-workflows). Its `pre_job` skips `schedule`. [moodlehq/moodle-plugin-ci](https://github.com/moodlehq/moodle-plugin-ci/issues/323) makes you name each Moodle branch, which is why the monthly workflow discovers the newest stable branch itself.
7. Local tests use Apple container. The template's playground uses Docker. This repository does not.
8. This project is released under the [GNU GPL v3 or later](LICENSE.md).
