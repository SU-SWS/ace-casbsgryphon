# [Jumpstart UI](https://github.com/SU-SWS/jumpstart_ui)
##### Version: 8.x

Maintainers: [Mike Decker](https://github.com/pookmish), [sherakama](https://github.com/sherakama)  

Changelog: [Changelog.txt](CHANGELOG.txt)

Description
---

This module provides UI tools for developers and tight integration with the Decanter Design System.

Included are:

### Components (ui_patterns + components)
- See: http://decanter.stanford.edu/section-components.html

### Layouts (layout plugin)
- See: http://decanter.stanford.edu/section-layout.html


Accessibility
---
[![WCAG Conformance 2.0 AA Badge](https://www.w3.org/WAI/wcag2AA-blue.png)](https://www.w3.org/TR/WCAG20/)
Evaluation Date: 2020-02-05  
This module conforms to level AA WCAG 2.0 standards as required by the university's accessibility policy. For more information on the policy please visit: [https://ucomm.stanford.edu/policies/accessibility-policy.html](https://ucomm.stanford.edu/policies/accessibility-policy.html).

Installation
---

Install this module like any other module. [See Drupal Documentation](https://drupal.org/documentation/install/modules-themes/modules-8)

Configuration
---

Nothing special needed.


Troubleshooting
---

If you are experiencing issues with this module try reverting the feature first. If you are still experiencing issues try posting an issue on the GitHub issues page.

Developer
---

If you wish to develop on this module you will most likely need to compile some new css. Please use the sass structure provided and compile with the sass compiler packaged in this module. To install:

```
npm install
npm run build
```

Contribution / Collaboration
---

You are welcome to contribute functionality, bug fixes, or documentation to this module. If you would like to suggest a fix or new functionality you may add a new issue to the GitHub issue queue or you may fork this repository and submit a pull request. For more help please see [GitHub's article on fork, branch, and pull requests](https://help.github.com/articles/using-pull-requests)


Releases
---

Steps to build a new release:
- Checkout the latest commit from the `8.x-1.x` branch.
- Create a new branch for the release.
- Commit any necessary changes to the release branch.
  -  These may include, but are not necessarily limited to:
    - Update the version in any `info.yml` files, including in any submodules.
    - Update the CHANGELOG to reflect the changes made in the new release.
- Make a PR to merge your release branch into `master`
- Give the PR a semver-compliant label, e.g., (`patch`, `minor`, `major`).  This may happen automatically via Github actions (if a labeler action is configured).
- When the PR is merged to `master`, a new tag will be created automatically, bumping the version by the semver label.
- The github action is built from: [semver-release-action](https://github.com/K-Phoen/semver-release-action), and further documentation is available there.
