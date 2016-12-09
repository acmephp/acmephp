* 1.0.0-beta3 (2016-12-09)

 * daa6d1c Merge pull request #40 from acmephp/fix-39
 * 0b8629e Remove RECOVER_REGISTRATION obsolete unused resource
 * de625cb Merge pull request #35 from acmephp/fix-34
 * a7973dc Fix #34 by removing illogical data validation in resources directory
 * 3a77a61 Merge pull request #31 from acmephp/rancher
 * 831f3ca Implement Push to Rancher post-generate action
 * 09437a7 Merge pull request #30 from acmephp/verbosity
 * 794f8d5 Introduce CLI logger to handle different verbosities properly
 * e055695 Bump to dev

* 1.0.0-beta2 (2016-10-19)

 * ed2cc9e Update main and components README files
 * 1adff0d Improve some commands descriptions
 * b727bd3 Merge pull request #29 from acmephp/fix-scrunitizer
 * a774fa1 Decrease code complexity by splitting complex methods into smaller ones
 * 5a72637 Improve readability of monitoring handlers
 * 2c88554 Merge pull request #25 from jderusse/custom-challenger
 * a4bfd2f Create fullchain certificate in nginx-proxy action
 * 93be223 Update version to dev
 * d5f60c6 Use container's tag to easily extends solvers
 * 914f33b Small fixes
 * 6d678dd Small fixes
 * ee753c1 Add tests
 * 2aa1b9d Separate validators from solvers
 * 0e46137 Fix tests
 * 297b724 Add automatic pre-validation
 * 40311b3 Remove data extractor from solvers
 * 6305409 Fix CS
 * 1d1bf00 Rename challenger into SOlver
 * e1289df Allow custom challenger extension

* 1.0.0-beta1 (2016-09-24)

 * f1585a4 Fix type in README
 * 54d68c3 Merge pull request #24 from jderusse/multi-domains
 * 8553f5c Split method firstRequest to reduce complexity
 * bf79270 Improve status's display
 * 7bd4e79 Separate domain and alternativeNames
 * 5a44496 Allow multi-domain in cli
 * f7de82d Automatically agreed with agrement (#26)
 * 7accf30 Fix tests (#27)

* 1.0.0-alpha10 (2016-08-16)

 * 3bfa96c Update RegisterCommand.php (#21)
 * d3d779f Bump version

* 1.0.0-alpha9 (2016-07-27)

 * 3e89b38 Remove unsupported actions from the dist file for the moment
 * 07857e6 Add PHP 7.1 in Travis and improve CI configuration (#19)
 * dfcfdd5 Implement monitoring system with email and slack handler (#16)
 * adf4dc8 Update version as DEV
 * f61df6f Fix Guzzle URI test (#17)
 * 846bbce Fix assertions messages
 * 113b2d8 Fix 404 on documentation link (#15)