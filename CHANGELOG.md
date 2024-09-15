# Changelog

## [6.0.0](https://github.com/Vatsim-Scandinavia/controlcenter/compare/v5.3.2...v6.0.0) (2024-09-15)


### ⚠ BREAKING CHANGES

* **api:** The User API will now return facilities in "facility" array instead of "position" to match the recent rename.
* Facility list is now removed in favour of the previously released ATC Roster.
* Removed VATSIM API v1 support for member list. You must now hold a v2 key for this functionality.
* ENV file fallbacks are deprecated and removed. Please make sure you no longer use APP_OWNER/APP_OWNER_SHORT variables.
* Deprecated since CC v2.0.3 training policy functions are removed. Should not effect the average user.
* The legacy API endpoints are now fully deprecated and removed. Users must move over the the new /api/user endpoint instead.
* waiting times are now defined per area
* Link position mae to endorsement instead of boolean

### Features

* Add information that shortening solo endorsement sends it email to student ([#965](https://github.com/Vatsim-Scandinavia/controlcenter/issues/965)) ([5a37319](https://github.com/Vatsim-Scandinavia/controlcenter/commit/5a37319f4f38e633867d679b1b48be80071aa276))
* Carbon v3 support ([5e2cb20](https://github.com/Vatsim-Scandinavia/controlcenter/commit/5e2cb2056aea84560a603b95e8747d2befa8b717))
* login as functionality for local env ([a47166b](https://github.com/Vatsim-Scandinavia/controlcenter/commit/a47166b7622801fa2cef4cce15b1ddbb20f110d6))
* make atc roster single link in sidebar if only one area exists ([6eb3ee9](https://github.com/Vatsim-Scandinavia/controlcenter/commit/6eb3ee947578f74e3c62ec02ae9172eae20b581f)), closes [#906](https://github.com/Vatsim-Scandinavia/controlcenter/issues/906)
* public `api/positions` added ([53dd288](https://github.com/Vatsim-Scandinavia/controlcenter/commit/53dd28818277d4502ca5aaa02f281dca810fde0b)), closes [#989](https://github.com/Vatsim-Scandinavia/controlcenter/issues/989)
* solo creation validates with divisionApi ([de3e478](https://github.com/Vatsim-Scandinavia/controlcenter/commit/de3e478ef542ff57d3d5c82a48b6a61b93771ab2)), closes [#908](https://github.com/Vatsim-Scandinavia/controlcenter/issues/908)
* support for S3 storage ([#996](https://github.com/Vatsim-Scandinavia/controlcenter/issues/996)) ([1e20771](https://github.com/Vatsim-Scandinavia/controlcenter/commit/1e2077114d62f5ba06b5ac9d31b792bef9d0e6ff))
* training activity report now includes reports ([b699d68](https://github.com/Vatsim-Scandinavia/controlcenter/commit/b699d68348f7682a9d03b4664cc351e89615346e)), closes [#918](https://github.com/Vatsim-Scandinavia/controlcenter/issues/918)
* upgrade to Laravel 11 ([#952](https://github.com/Vatsim-Scandinavia/controlcenter/issues/952)) ([f7efdba](https://github.com/Vatsim-Scandinavia/controlcenter/commit/f7efdba7e9930b0887f65fe96bd8e9747cec9568)), closes [#914](https://github.com/Vatsim-Scandinavia/controlcenter/issues/914)
* waiting times are now defined per area ([06a7d24](https://github.com/Vatsim-Scandinavia/controlcenter/commit/06a7d240ea6dd4a48a8797774dc65a93e30ece66)), closes [#919](https://github.com/Vatsim-Scandinavia/controlcenter/issues/919)


### Bug Fixes

* content loss warning when editing reports ([87bbcd8](https://github.com/Vatsim-Scandinavia/controlcenter/commit/87bbcd8677564051718c52a887f6825740a497b5)), closes [#913](https://github.com/Vatsim-Scandinavia/controlcenter/issues/913)
* double mentors in mentor report ([336492a](https://github.com/Vatsim-Scandinavia/controlcenter/commit/336492a2250cafa478301dab922e467039a92e03))
* inactivity warnings to observers on network ([5d4297d](https://github.com/Vatsim-Scandinavia/controlcenter/commit/5d4297d1f6bd4f0a1ebc519147af0682bf31169c)), closes [#910](https://github.com/Vatsim-Scandinavia/controlcenter/issues/910)
* Link position mae to endorsement instead of boolean ([5b26a37](https://github.com/Vatsim-Scandinavia/controlcenter/commit/5b26a372fe7d65bcaf538b87eafd444a2d6ecb50)), closes [#885](https://github.com/Vatsim-Scandinavia/controlcenter/issues/885)
* made tasks quick add only show mods ([4017b73](https://github.com/Vatsim-Scandinavia/controlcenter/commit/4017b736000c2518d2ab4268cfe02d1894b999f5)), closes [#973](https://github.com/Vatsim-Scandinavia/controlcenter/issues/973)
* mentor examination notification error if no mentors ([1cd3f08](https://github.com/Vatsim-Scandinavia/controlcenter/commit/1cd3f086395e5d866d2c48b2a69fe46756160425))
* moved application hour counter to VATSIM API v2 ([f515767](https://github.com/Vatsim-Scandinavia/controlcenter/commit/f5157674592dc61f0e107e91dde395a1f54bf357))
* passport causing log error each day ([ae037f7](https://github.com/Vatsim-Scandinavia/controlcenter/commit/ae037f788887e77c67f418bb64c0070e68e84262))
* placeholder for task recepient ([4405fae](https://github.com/Vatsim-Scandinavia/controlcenter/commit/4405faebf930f2f9d4b244bc69b3aa9c8fbb057d))
* previous migrations acting up ([d860f20](https://github.com/Vatsim-Scandinavia/controlcenter/commit/d860f2019d6e5e863eb96440ecdb2d69676a846d))
* set 10mb as max upload size per file ([5b51584](https://github.com/Vatsim-Scandinavia/controlcenter/commit/5b51584012640abf42b51cace700c38f5dadfd97))
* show atc hours card instead of last training on mobile ([72a74a6](https://github.com/Vatsim-Scandinavia/controlcenter/commit/72a74a667dfa1abebca95388d1d8723ed148f0d3)), closes [#997](https://github.com/Vatsim-Scandinavia/controlcenter/issues/997)
* task quickadd filter only on moderators+admins ([ef40a77](https://github.com/Vatsim-Scandinavia/controlcenter/commit/ef40a77b7cce5743b9662a8ad8f82eaccc91cafe))


### Miscellaneous Chores

* **api:** Change `controllers` array to `roster_members` in division roster api ([#880](https://github.com/Vatsim-Scandinavia/controlcenter/issues/880)) ([a6e05af](https://github.com/Vatsim-Scandinavia/controlcenter/commit/a6e05afb99e5e517e2d0436735fd2567acdc6d90))
* **api:** user api facility return name change ([da58f00](https://github.com/Vatsim-Scandinavia/controlcenter/commit/da58f001f3bb248d7cabed2c89c014cf786f1b47))
* **deps:** update dependency @vitejs/plugin-vue to v5.1.1 ([#966](https://github.com/Vatsim-Scandinavia/controlcenter/issues/966)) ([bd39e73](https://github.com/Vatsim-Scandinavia/controlcenter/commit/bd39e7385fc3ab179adc8e61638af4de4e8d0275))
* **deps:** update dependency @vitejs/plugin-vue to v5.1.2 ([#976](https://github.com/Vatsim-Scandinavia/controlcenter/issues/976)) ([20285d4](https://github.com/Vatsim-Scandinavia/controlcenter/commit/20285d4e8dff792d101283184e00ecd56240e638))
* **deps:** update dependency @vitejs/plugin-vue to v5.1.3 ([#1004](https://github.com/Vatsim-Scandinavia/controlcenter/issues/1004)) ([cfcf2fc](https://github.com/Vatsim-Scandinavia/controlcenter/commit/cfcf2fcc23cc3e174a76094cfc90f131411bc1c9))
* **deps:** update dependency bootstrap-table to v1.23.2 ([#971](https://github.com/Vatsim-Scandinavia/controlcenter/issues/971)) ([49cb75f](https://github.com/Vatsim-Scandinavia/controlcenter/commit/49cb75f40f866bc8c5efb13eb3fe51b4aebb6be8))
* **deps:** update dependency chart.js to v4.4.4 ([#1001](https://github.com/Vatsim-Scandinavia/controlcenter/issues/1001)) ([69eefb5](https://github.com/Vatsim-Scandinavia/controlcenter/commit/69eefb53f8c2fcf770c66145bb7062c211007d3f))
* **deps:** update dependency core-js to v3.38.0 ([#980](https://github.com/Vatsim-Scandinavia/controlcenter/issues/980)) ([7977b36](https://github.com/Vatsim-Scandinavia/controlcenter/commit/7977b36c745aa6bb6aa8a405394da7a5115cb748))
* **deps:** update dependency core-js to v3.38.1 ([#999](https://github.com/Vatsim-Scandinavia/controlcenter/issues/999)) ([30aa2ed](https://github.com/Vatsim-Scandinavia/controlcenter/commit/30aa2ed0ab4b2bc94084a6b9fc6b6a54980682c5))
* **deps:** update dependency guzzlehttp/guzzle to v7.9.2 ([#950](https://github.com/Vatsim-Scandinavia/controlcenter/issues/950)) ([503221d](https://github.com/Vatsim-Scandinavia/controlcenter/commit/503221dcf797e532a02ba6c0faed7cc2cddd4754))
* **deps:** update dependency laravel/framework to v11.19.0 ([#967](https://github.com/Vatsim-Scandinavia/controlcenter/issues/967)) ([88c315e](https://github.com/Vatsim-Scandinavia/controlcenter/commit/88c315e6c609e6440ce3215c417cfd80f2d58dd4))
* **deps:** update dependency laravel/framework to v11.20.0 ([#984](https://github.com/Vatsim-Scandinavia/controlcenter/issues/984)) ([7089111](https://github.com/Vatsim-Scandinavia/controlcenter/commit/7089111600547ea58f9868ef8aaf155510e6d13a))
* **deps:** update dependency laravel/framework to v11.23.5 ([#1000](https://github.com/Vatsim-Scandinavia/controlcenter/issues/1000)) ([5f9b4f4](https://github.com/Vatsim-Scandinavia/controlcenter/commit/5f9b4f4bffa5163a42b321930e4aa9f7084b025f))
* **deps:** update dependency laravel/pint to v1.17.0 ([c77ad87](https://github.com/Vatsim-Scandinavia/controlcenter/commit/c77ad87f404e19f7542643b188c8523e9c5d9ca7))
* **deps:** update dependency laravel/pint to v1.17.1 ([#968](https://github.com/Vatsim-Scandinavia/controlcenter/issues/968)) ([e99b501](https://github.com/Vatsim-Scandinavia/controlcenter/commit/e99b501b1e60d2de59a648a6efda580c956c84aa))
* **deps:** update dependency laravel/pint to v1.17.2 ([#981](https://github.com/Vatsim-Scandinavia/controlcenter/issues/981)) ([107a7e7](https://github.com/Vatsim-Scandinavia/controlcenter/commit/107a7e73cb5e3e644b79ea7ca54737b3861bc153))
* **deps:** update dependency laravel/pint to v1.17.3 ([#1005](https://github.com/Vatsim-Scandinavia/controlcenter/issues/1005)) ([ee1cf95](https://github.com/Vatsim-Scandinavia/controlcenter/commit/ee1cf951f8aae82140fe1f250c1d1ffde071bf13))
* **deps:** update dependency league/commonmark to v2.5.1 ([#963](https://github.com/Vatsim-Scandinavia/controlcenter/issues/963)) ([e28a5bf](https://github.com/Vatsim-Scandinavia/controlcenter/commit/e28a5bfadfa8c474ce284697f00d1fef126037ba))
* **deps:** update dependency nunomaduro/collision to v8.4.0 ([#979](https://github.com/Vatsim-Scandinavia/controlcenter/issues/979)) ([7902e92](https://github.com/Vatsim-Scandinavia/controlcenter/commit/7902e92aec3deef6feffbd95608bc8f2572071eb))
* **deps:** update dependency phpunit/phpunit to v11.2.9 ([#974](https://github.com/Vatsim-Scandinavia/controlcenter/issues/974)) ([4100e89](https://github.com/Vatsim-Scandinavia/controlcenter/commit/4100e89486b27adb5bdcc0dff9b44ff80e46776f))
* **deps:** update dependency phpunit/phpunit to v11.3.0 ([#978](https://github.com/Vatsim-Scandinavia/controlcenter/issues/978)) ([a709d4c](https://github.com/Vatsim-Scandinavia/controlcenter/commit/a709d4ce3463fa2e663abbb03e1c8c0cb07686be))
* **deps:** update dependency phpunit/phpunit to v11.3.5 ([#990](https://github.com/Vatsim-Scandinavia/controlcenter/issues/990)) ([3529f07](https://github.com/Vatsim-Scandinavia/controlcenter/commit/3529f07be49d0288fed4527f99eb55de638c9ed3))
* **deps:** update dependency sass to v1.78.0 ([#1007](https://github.com/Vatsim-Scandinavia/controlcenter/issues/1007)) ([791d717](https://github.com/Vatsim-Scandinavia/controlcenter/commit/791d717120af9d88c5cc946ca9e52c7e460c4b29))
* **deps:** update dependency spatie/laravel-login-link to v1.3.1 ([#987](https://github.com/Vatsim-Scandinavia/controlcenter/issues/987)) ([0ac13c6](https://github.com/Vatsim-Scandinavia/controlcenter/commit/0ac13c6d550fcd5151ded33e0e551c61e1841b91))
* **deps:** update dependency vite to v5.3.5 ([#970](https://github.com/Vatsim-Scandinavia/controlcenter/issues/970)) ([0a630b0](https://github.com/Vatsim-Scandinavia/controlcenter/commit/0a630b0ba3ad479c4afca2941dbae68bc9c4267a))
* **deps:** update dependency vite to v5.4.0 ([#988](https://github.com/Vatsim-Scandinavia/controlcenter/issues/988)) ([aa0fdfd](https://github.com/Vatsim-Scandinavia/controlcenter/commit/aa0fdfd0b7ecebf079f220c4bba3c69379460edb))
* **deps:** update dependency vite to v5.4.5 ([#994](https://github.com/Vatsim-Scandinavia/controlcenter/issues/994)) ([62702a1](https://github.com/Vatsim-Scandinavia/controlcenter/commit/62702a18e4c30950a812fd3c215a2b6aa54c09cc))
* **deps:** update dependency vue to v3.4.35 ([#947](https://github.com/Vatsim-Scandinavia/controlcenter/issues/947)) ([19b0fae](https://github.com/Vatsim-Scandinavia/controlcenter/commit/19b0faeff381e461551f97edd5f55ba136dc2892))
* **deps:** update dependency vue to v3.4.37 ([#982](https://github.com/Vatsim-Scandinavia/controlcenter/issues/982)) ([a943b10](https://github.com/Vatsim-Scandinavia/controlcenter/commit/a943b1006c01bdf00ad2911c231e3460cf351356))
* **deps:** update dependency vue to v3.5.5 ([#993](https://github.com/Vatsim-Scandinavia/controlcenter/issues/993)) ([7a57bea](https://github.com/Vatsim-Scandinavia/controlcenter/commit/7a57beac5f9e76bef6ddd58a649209f6ab8d4225))
* **deps:** update docker.io/library/mysql docker tag to v9.0.1 ([#964](https://github.com/Vatsim-Scandinavia/controlcenter/issues/964)) ([efd4e56](https://github.com/Vatsim-Scandinavia/controlcenter/commit/efd4e568d486e1d858b93ea0d355117ea1056ccb))
* **deps:** update docker.io/library/node docker tag to v22.6.0 ([#986](https://github.com/Vatsim-Scandinavia/controlcenter/issues/986)) ([958f837](https://github.com/Vatsim-Scandinavia/controlcenter/commit/958f83794cba557d0aa5058915543e58ab292780))
* **deps:** update docker.io/library/php docker tag to v8.3.10 ([#977](https://github.com/Vatsim-Scandinavia/controlcenter/issues/977)) ([aa8a6aa](https://github.com/Vatsim-Scandinavia/controlcenter/commit/aa8a6aaf06252fba8eb720444a34c03345d7aa09))
* **deps:** update docker.io/library/php docker tag to v8.3.11 ([#1006](https://github.com/Vatsim-Scandinavia/controlcenter/issues/1006)) ([d5f4263](https://github.com/Vatsim-Scandinavia/controlcenter/commit/d5f426330a828cb70f192418b6c82947ef3b789a))
* **deps:** update docker.io/library/redis docker tag to v7.4 ([#975](https://github.com/Vatsim-Scandinavia/controlcenter/issues/975)) ([55f1b2c](https://github.com/Vatsim-Scandinavia/controlcenter/commit/55f1b2cc8ea105fe2cb0253488685023956c531a))
* **deps:** update mlocati/php-extension-installer docker tag to v2.3.2 ([#972](https://github.com/Vatsim-Scandinavia/controlcenter/issues/972)) ([2244709](https://github.com/Vatsim-Scandinavia/controlcenter/commit/2244709a28258fc3626b5e52920a6d3943d56d56))
* **deps:** update mlocati/php-extension-installer docker tag to v2.3.5 ([#985](https://github.com/Vatsim-Scandinavia/controlcenter/issues/985)) ([8959ef2](https://github.com/Vatsim-Scandinavia/controlcenter/commit/8959ef2083f8cbae12a9891a47d8ca31663ecb63))
* **deps:** update mlocati/php-extension-installer docker tag to v2.5.0 ([#991](https://github.com/Vatsim-Scandinavia/controlcenter/issues/991)) ([3779b4c](https://github.com/Vatsim-Scandinavia/controlcenter/commit/3779b4c883f7551d70e33cfea2e4073de06471b0))
* **deps:** update node.js to v22.8.0 ([#1002](https://github.com/Vatsim-Scandinavia/controlcenter/issues/1002)) ([4168f0b](https://github.com/Vatsim-Scandinavia/controlcenter/commit/4168f0b0e5d8511440c0a618001e0bf8251f4e58))
* env owner fallbacks are deprecated ([ddd72db](https://github.com/Vatsim-Scandinavia/controlcenter/commit/ddd72db0ac8a58da7b79e5f3fc016e27a90981ff))
* facility list is removed ([dff7890](https://github.com/Vatsim-Scandinavia/controlcenter/commit/dff78902101b3b8c3173cf38d4971aa716a783e9))
* fixed booking and examination tests ([1374672](https://github.com/Vatsim-Scandinavia/controlcenter/commit/137467200949724ae9ab19411d0ce8b4e0343a10))
* position api doc ([e02ba71](https://github.com/Vatsim-Scandinavia/controlcenter/commit/e02ba7142d1810435b6d01cbd5d197cb1620602f)), closes [#1003](https://github.com/Vatsim-Scandinavia/controlcenter/issues/1003)
* removed deprecated API endpoints ([3240851](https://github.com/Vatsim-Scandinavia/controlcenter/commit/32408514184fc97078ed4f499a1760a8066dcadc))
* removed deprecated training policy functions ([88e3a9c](https://github.com/Vatsim-Scandinavia/controlcenter/commit/88e3a9cd62b6b49c6285f6e28e87f31eceb99556))
* removed unused model in training.index ([8449303](https://github.com/Vatsim-Scandinavia/controlcenter/commit/8449303a799758eebbec7c869b8905ebf1f3c697))
* removed VATSIM API v1 support ([d208de3](https://github.com/Vatsim-Scandinavia/controlcenter/commit/d208de36b066ffb8dbc8dea8d9a832f368ff41a0))
* renamed MASC to FACILITY ([2d770c2](https://github.com/Vatsim-Scandinavia/controlcenter/commit/2d770c278042917377d38bc7832df2264252b90d))

## [5.3.2](https://github.com/Vatsim-Scandinavia/controlcenter/compare/v5.3.1...v5.3.2) (2024-07-17)


### Bug Fixes

* ATC Roster table filtering displays "Visiting" option incorrectly ([6f83756](https://github.com/Vatsim-Scandinavia/controlcenter/commit/6f83756006da8511887868b1afd8d9f28579b63e)), closes [#945](https://github.com/Vatsim-Scandinavia/controlcenter/issues/945)
* closing paused trainings marked it as paused again ([b5d6130](https://github.com/Vatsim-Scandinavia/controlcenter/commit/b5d61301e0a57875f3cf83b282d0ca756a71521c)), closes [#909](https://github.com/Vatsim-Scandinavia/controlcenter/issues/909)
* eud core link visible for non-moderators ([7b866a7](https://github.com/Vatsim-Scandinavia/controlcenter/commit/7b866a72fa32f0dd72b53bf213c1a95a28953ef5)), closes [#907](https://github.com/Vatsim-Scandinavia/controlcenter/issues/907)
* html characters in comments breaking filters ([f0b4ac6](https://github.com/Vatsim-Scandinavia/controlcenter/commit/f0b4ac62313ed98a1a4f65c9110f3b38ae275956))


### Miscellaneous Chores

* **deps:** update dependency @fortawesome/fontawesome-free to v6.6.0 ([#944](https://github.com/Vatsim-Scandinavia/controlcenter/issues/944)) ([a0415db](https://github.com/Vatsim-Scandinavia/controlcenter/commit/a0415db17b89e1baf9a5b31d0849d05f1314cab8))
* **deps:** update dependency bootstrap-table to v1.23.1 ([#942](https://github.com/Vatsim-Scandinavia/controlcenter/issues/942)) ([af65787](https://github.com/Vatsim-Scandinavia/controlcenter/commit/af657870a574a44fc64c5b4c3b12269389267e13))
* **deps:** update dependency sass to v1.77.8 ([#941](https://github.com/Vatsim-Scandinavia/controlcenter/issues/941)) ([562c33b](https://github.com/Vatsim-Scandinavia/controlcenter/commit/562c33bcc7cd038cb0147ac244cba8f06fc9caf6))
* **deps:** update dependency vite to v5.3.4 ([#943](https://github.com/Vatsim-Scandinavia/controlcenter/issues/943)) ([075ea9e](https://github.com/Vatsim-Scandinavia/controlcenter/commit/075ea9e945dea9da55ec31a1c9a581858939ce88))
* **deps:** update mlocati/php-extension-installer docker tag to v2.2.19 ([#939](https://github.com/Vatsim-Scandinavia/controlcenter/issues/939)) ([b3ce55f](https://github.com/Vatsim-Scandinavia/controlcenter/commit/b3ce55f51314d3fe285994b07fe4d784ede384a3))

## [5.3.1](https://github.com/Vatsim-Scandinavia/controlcenter/compare/v5.3.0...v5.3.1) (2024-07-11)


### Bug Fixes

* table filters missing (dependency missing) ([cacc27b](https://github.com/Vatsim-Scandinavia/controlcenter/commit/cacc27bdcb5556fd7d96b7d0c1e54ddede44ff65)), closes [#937](https://github.com/Vatsim-Scandinavia/controlcenter/issues/937)


### Miscellaneous Chores

* **deps:** update dependency phpunit/phpunit to v10.5.27 ([#936](https://github.com/Vatsim-Scandinavia/controlcenter/issues/936)) ([0e19c8e](https://github.com/Vatsim-Scandinavia/controlcenter/commit/0e19c8e512bb0bcc483726e8640793e91310922a))

## [5.3.0](https://github.com/Vatsim-Scandinavia/controlcenter/compare/v5.2.5...v5.3.0) (2024-07-10)


### Features

* api/bookings available publicly with limited data ([8c2cd11](https://github.com/Vatsim-Scandinavia/controlcenter/commit/8c2cd114acd7d8de2a9af64aa0223e599baa7e78)), closes [#901](https://github.com/Vatsim-Scandinavia/controlcenter/issues/901)
* notify and attach exam report to mentors ([8d54c9b](https://github.com/Vatsim-Scandinavia/controlcenter/commit/8d54c9b62298150d1e034fb9ef7589785a843337)), closes [#915](https://github.com/Vatsim-Scandinavia/controlcenter/issues/915)
* open bookings api endpoint ([38bd8ae](https://github.com/Vatsim-Scandinavia/controlcenter/commit/38bd8ae87f4e343732719ad227116a795579eb79))
* pre-training completed state ([#920](https://github.com/Vatsim-Scandinavia/controlcenter/issues/920)) ([858edc8](https://github.com/Vatsim-Scandinavia/controlcenter/commit/858edc8a1ecb527d89b58ac56f6658ff648c532f))


### Bug Fixes

* In mentor assigned email, change "you training" to "your training" ([#912](https://github.com/Vatsim-Scandinavia/controlcenter/issues/912)) ([d0d878e](https://github.com/Vatsim-Scandinavia/controlcenter/commit/d0d878e94915eec0fb0b48aed99fb3456f448263))
* mentors could read all attachments ([edad858](https://github.com/Vatsim-Scandinavia/controlcenter/commit/edad858ca1cc49f194f46c0252b23ba99046081f)), closes [#921](https://github.com/Vatsim-Scandinavia/controlcenter/issues/921)
* missing error handling on non-existent solo positions ([8752d99](https://github.com/Vatsim-Scandinavia/controlcenter/commit/8752d99cbca445fd8e1cda40abdf499d998edceb)), closes [#911](https://github.com/Vatsim-Scandinavia/controlcenter/issues/911)


### Miscellaneous Chores

* **deps:** update dependency @vitejs/plugin-vue to v5.0.5 ([#922](https://github.com/Vatsim-Scandinavia/controlcenter/issues/922)) ([3d65081](https://github.com/Vatsim-Scandinavia/controlcenter/commit/3d65081632cc4454140d262ca19b28a2c219b5c3))
* **deps:** update dependency anlutro/l4-settings to v1.4.1 ([#928](https://github.com/Vatsim-Scandinavia/controlcenter/issues/928)) ([137f535](https://github.com/Vatsim-Scandinavia/controlcenter/commit/137f5358a70626d01eab34c8962efa896d904534))
* **deps:** update dependency barryvdh/laravel-debugbar to v3.13.5 ([#895](https://github.com/Vatsim-Scandinavia/controlcenter/issues/895)) ([a72824d](https://github.com/Vatsim-Scandinavia/controlcenter/commit/a72824d534a20fb6d49b67fd5b3004e93f0bf75b))
* **deps:** update dependency bootstrap-table to v1.23.0 ([#929](https://github.com/Vatsim-Scandinavia/controlcenter/issues/929)) ([bb24a89](https://github.com/Vatsim-Scandinavia/controlcenter/commit/bb24a895338d506b4de02f934ce4aa17bc7d8a5d))
* **deps:** update dependency chart.js to v4.4.3 ([#923](https://github.com/Vatsim-Scandinavia/controlcenter/issues/923)) ([cb5d873](https://github.com/Vatsim-Scandinavia/controlcenter/commit/cb5d87314c04e8f2fa6013d8d82c37148e8f6d5e))
* **deps:** update dependency doctrine/dbal to v3.8.6 ([#924](https://github.com/Vatsim-Scandinavia/controlcenter/issues/924)) ([f91ec89](https://github.com/Vatsim-Scandinavia/controlcenter/commit/f91ec891e44bb666b3e91ecffc023bc06638369c))
* **deps:** update dependency laravel-vite-plugin to v1.0.5 ([#925](https://github.com/Vatsim-Scandinavia/controlcenter/issues/925)) ([2b19ff7](https://github.com/Vatsim-Scandinavia/controlcenter/commit/2b19ff71bc387a6fb229d27a2c6b0d19ebcbacba))
* **deps:** update dependency laravel/framework to v10.48.16 ([#896](https://github.com/Vatsim-Scandinavia/controlcenter/issues/896)) ([537ef72](https://github.com/Vatsim-Scandinavia/controlcenter/commit/537ef72691f3eb287e3580b107852b0cd42a09f8))
* **deps:** update dependency laravel/ui to v4.5.2 ([#926](https://github.com/Vatsim-Scandinavia/controlcenter/issues/926)) ([a16b0af](https://github.com/Vatsim-Scandinavia/controlcenter/commit/a16b0afea85c9ca46c1bbd7dcc42ce743d571745))
* **deps:** update dependency mockery/mockery to v1.6.12 ([#927](https://github.com/Vatsim-Scandinavia/controlcenter/issues/927)) ([f5d9bdd](https://github.com/Vatsim-Scandinavia/controlcenter/commit/f5d9bdd9f0bda0b3fe9c9427990b9a8a7bc2e1c3))
* **deps:** update dependency phpunit/phpunit to v10.5.26 ([#902](https://github.com/Vatsim-Scandinavia/controlcenter/issues/902)) ([cfb1ea4](https://github.com/Vatsim-Scandinavia/controlcenter/commit/cfb1ea42314b31dd2931a369ea7327e5931f541d))
* **deps:** update dependency sass to v1.77.7 ([#899](https://github.com/Vatsim-Scandinavia/controlcenter/issues/899)) ([e341abf](https://github.com/Vatsim-Scandinavia/controlcenter/commit/e341abf59d20eaa4a4a9691e960bb5a9566ffb74))
* **deps:** update dependency spatie/laravel-ignition to v2.8.0 ([#905](https://github.com/Vatsim-Scandinavia/controlcenter/issues/905)) ([49e3fba](https://github.com/Vatsim-Scandinavia/controlcenter/commit/49e3fba148c5125223b6852941e94d51d40bc23e))
* **deps:** update dependency vite to v5.3.3 ([#904](https://github.com/Vatsim-Scandinavia/controlcenter/issues/904)) ([94225b9](https://github.com/Vatsim-Scandinavia/controlcenter/commit/94225b920c78ebb69f9dfb1fbe5db271430979a2))
* **deps:** update dependency vue to v3.4.31 ([#903](https://github.com/Vatsim-Scandinavia/controlcenter/issues/903)) ([1fe5cc2](https://github.com/Vatsim-Scandinavia/controlcenter/commit/1fe5cc24043f071260e25ea7dd64c408c2dd5a2f))
* **deps:** update docker.io/library/mysql docker tag to v9 ([#933](https://github.com/Vatsim-Scandinavia/controlcenter/issues/933)) ([39f7100](https://github.com/Vatsim-Scandinavia/controlcenter/commit/39f7100bcd1199cf890868eda03f57ede2c86037))
* **deps:** update mlocati/php-extension-installer docker tag to v2.2.18 ([#894](https://github.com/Vatsim-Scandinavia/controlcenter/issues/894)) ([3cbbda5](https://github.com/Vatsim-Scandinavia/controlcenter/commit/3cbbda52d9db6b4112ef0de24f9ec3577478b903))
* **deps:** update node.js to v21.7.3 ([#897](https://github.com/Vatsim-Scandinavia/controlcenter/issues/897)) ([a4e2bdf](https://github.com/Vatsim-Scandinavia/controlcenter/commit/a4e2bdf6ece4d69573dfd7025fd1a24db4f7c3a5))

## [5.2.5](https://github.com/Vatsim-Scandinavia/controlcenter/compare/v5.2.4...v5.2.5) (2024-04-09)


### Bug Fixes

* mails not bcc'ing to work email setting ([8e1a876](https://github.com/Vatsim-Scandinavia/controlcenter/commit/8e1a876145e6d4569f7f08e81cded5b5c8297d59))


### Miscellaneous Chores

* **deps:** update dependency @fortawesome/fontawesome-free to v6.5.2 ([#888](https://github.com/Vatsim-Scandinavia/controlcenter/issues/888)) ([0c4787b](https://github.com/Vatsim-Scandinavia/controlcenter/commit/0c4787bac78a8662ca8a7491cde93d86074b2a72))
* **deps:** update dependency barryvdh/laravel-debugbar to v3.13.3 ([#890](https://github.com/Vatsim-Scandinavia/controlcenter/issues/890)) ([9f009a5](https://github.com/Vatsim-Scandinavia/controlcenter/commit/9f009a521a1d664014bac7dc0e29f21d1d9f61a9))
* **deps:** update dependency bootstrap-table to v1.22.4 ([#882](https://github.com/Vatsim-Scandinavia/controlcenter/issues/882)) ([f73f080](https://github.com/Vatsim-Scandinavia/controlcenter/commit/f73f080e4bb5a6e60765b416961227bc29ebf65d))
* **deps:** update dependency graham-campbell/markdown to v15.2.0 ([#872](https://github.com/Vatsim-Scandinavia/controlcenter/issues/872)) ([aaf7c1c](https://github.com/Vatsim-Scandinavia/controlcenter/commit/aaf7c1c0c22c4550dfeb2503c42ab58d0238d55d))
* **deps:** update dependency laravel/framework to v10.48.5 ([#879](https://github.com/Vatsim-Scandinavia/controlcenter/issues/879)) ([2d6ab6d](https://github.com/Vatsim-Scandinavia/controlcenter/commit/2d6ab6d838b0784924b3456a65dddf93c479e117))
* **deps:** update dependency laravel/pint to v1.15.1 ([#891](https://github.com/Vatsim-Scandinavia/controlcenter/issues/891)) ([8025653](https://github.com/Vatsim-Scandinavia/controlcenter/commit/80256535ce3c5cd9dd493cd28aae7b3f75ab997d))
* **deps:** update dependency laravel/ui to v4.5.1 ([#884](https://github.com/Vatsim-Scandinavia/controlcenter/issues/884)) ([6ef13a5](https://github.com/Vatsim-Scandinavia/controlcenter/commit/6ef13a5243ce69b050ee491b9d19aeac49d0dc21))
* **deps:** update dependency mockery/mockery to v1.6.11 ([#874](https://github.com/Vatsim-Scandinavia/controlcenter/issues/874)) ([1276b70](https://github.com/Vatsim-Scandinavia/controlcenter/commit/1276b70ac19bbd5a80b46a4e0bf3062054aa2265))
* **deps:** update dependency phpunit/phpunit to v10.5.17 ([#878](https://github.com/Vatsim-Scandinavia/controlcenter/issues/878)) ([e579487](https://github.com/Vatsim-Scandinavia/controlcenter/commit/e5794879eeafc8e4deec02e5c4c1a96b77443c21))
* **deps:** update dependency sass to v1.74.1 ([#892](https://github.com/Vatsim-Scandinavia/controlcenter/issues/892)) ([aa94623](https://github.com/Vatsim-Scandinavia/controlcenter/commit/aa94623701de6a8bb039a1a2fafc355d2e2fd6ba))
* **deps:** update dependency spatie/laravel-ignition to v2.5.1 ([#893](https://github.com/Vatsim-Scandinavia/controlcenter/issues/893)) ([2dc90b7](https://github.com/Vatsim-Scandinavia/controlcenter/commit/2dc90b716279ae4231bad538fe1190b8c680259c))
* **deps:** update dependency vite to v5.1.7 [security] ([#886](https://github.com/Vatsim-Scandinavia/controlcenter/issues/886)) ([93b499c](https://github.com/Vatsim-Scandinavia/controlcenter/commit/93b499cfdc909ff68ab349a15060ac6c990647da))
* **deps:** update dependency vite to v5.2.8 ([#875](https://github.com/Vatsim-Scandinavia/controlcenter/issues/875)) ([a0c1d62](https://github.com/Vatsim-Scandinavia/controlcenter/commit/a0c1d6260016f5ab0b6168ddc3d80c259491184b))
* **deps:** update docker.io/library/php docker tag to v8.3.4 ([#870](https://github.com/Vatsim-Scandinavia/controlcenter/issues/870)) ([835bcb6](https://github.com/Vatsim-Scandinavia/controlcenter/commit/835bcb6b1131e054c836bafdcd99594b7ac7a724))
* **deps:** update mlocati/php-extension-installer docker tag to v2.2.7 ([#873](https://github.com/Vatsim-Scandinavia/controlcenter/issues/873)) ([8944307](https://github.com/Vatsim-Scandinavia/controlcenter/commit/89443078573eebe8cd054906bdea084c3ad92632))
* **deps:** update node.js to v21.7.2 ([#889](https://github.com/Vatsim-Scandinavia/controlcenter/issues/889)) ([302d382](https://github.com/Vatsim-Scandinavia/controlcenter/commit/302d38292c48b81e445bb553073755078d6cb4db))

## [5.2.4](https://github.com/Vatsim-Scandinavia/controlcenter/compare/v5.2.3...v5.2.4) (2024-03-26)


### Bug Fixes

* booking api error messages ([4d25f28](https://github.com/Vatsim-Scandinavia/controlcenter/commit/4d25f284f634fe9c6c45e0dcc30bac2ffd46ed37))
* booking of positions with long callsign ([d04786e](https://github.com/Vatsim-Scandinavia/controlcenter/commit/d04786eb548f88c3081efd20b7b43ef6f66f0149))

## [5.2.3](https://github.com/Vatsim-Scandinavia/controlcenter/compare/v5.2.2...v5.2.3) (2024-03-25)


### Bug Fixes

* being able to submit multiple votes ([e7c8117](https://github.com/Vatsim-Scandinavia/controlcenter/commit/e7c8117cd484ba1429459983689c9aee6e46280e))
* typo in endorsements ([e9954bb](https://github.com/Vatsim-Scandinavia/controlcenter/commit/e9954bb3a67f6cad13988ff7cf5ce585dbca1aa2))

## [5.2.2](https://github.com/Vatsim-Scandinavia/controlcenter/compare/v5.2.1...v5.2.2) (2024-03-15)


### Bug Fixes

* added missing User-Agent for division api ([6aed00b](https://github.com/Vatsim-Scandinavia/controlcenter/commit/6aed00bf8df9db1cf8891d6e5b7b301b104fa3d1)), closes [#866](https://github.com/Vatsim-Scandinavia/controlcenter/issues/866)
* **ci:** move extra-files into config ([4607ed5](https://github.com/Vatsim-Scandinavia/controlcenter/commit/4607ed5bbb827cb55ea3b9c9a9045b5a3909faa1))
* **ci:** update app version in release-please ([#857](https://github.com/Vatsim-Scandinavia/controlcenter/issues/857)) ([4a5cfa3](https://github.com/Vatsim-Scandinavia/controlcenter/commit/4a5cfa33a5b847a8f7447891c9b012b6f0eaa9b4))
* division exam icons ([0bdf3ce](https://github.com/Vatsim-Scandinavia/controlcenter/commit/0bdf3cef71f74977a526a2c2d1e8277a29c9b38f))
* unnecessary amount of calls by endorsement sync ([752dbb3](https://github.com/Vatsim-Scandinavia/controlcenter/commit/752dbb3cd2ef28d8f0eb5fced370183af891986d))
* vote criteria that only members can vote ([24fd1f8](https://github.com/Vatsim-Scandinavia/controlcenter/commit/24fd1f8caebc187e7147b493b0ab1db007427264)), closes [#867](https://github.com/Vatsim-Scandinavia/controlcenter/issues/867)


### Miscellaneous Chores

* **deps:** update dependency barryvdh/laravel-debugbar to v3.12.2 ([#861](https://github.com/Vatsim-Scandinavia/controlcenter/issues/861)) ([4099f8f](https://github.com/Vatsim-Scandinavia/controlcenter/commit/4099f8fd2e1039450fb0bbc801aaf4a30e428b9b))
* **deps:** update dependency laravel/framework to v10.48.3 ([#868](https://github.com/Vatsim-Scandinavia/controlcenter/issues/868)) ([02e0990](https://github.com/Vatsim-Scandinavia/controlcenter/commit/02e09906a51ffcc581f2c115045ecb8abc67fbc4))
* **deps:** update dependency sass to v1.72.0 ([#865](https://github.com/Vatsim-Scandinavia/controlcenter/issues/865)) ([0c69fe2](https://github.com/Vatsim-Scandinavia/controlcenter/commit/0c69fe230ed950212b25a95c2752d1ef46c4f5fc))
* **deps:** update mlocati/php-extension-installer docker tag to v2.2.3 ([#869](https://github.com/Vatsim-Scandinavia/controlcenter/issues/869)) ([05cd0f7](https://github.com/Vatsim-Scandinavia/controlcenter/commit/05cd0f773650b1ef1bc0b9637cfbbd237e9ad855))

## [5.2.1](https://github.com/Vatsim-Scandinavia/controlcenter/compare/v5.2.0...v5.2.1) (2024-03-12)


### Bug Fixes

* error when applying for training ([575379a](https://github.com/Vatsim-Scandinavia/controlcenter/commit/575379a323ea892300876bde83bd5e52c627e356))
* subdivision short names ([#848](https://github.com/Vatsim-Scandinavia/controlcenter/issues/848)) ([3bfa594](https://github.com/Vatsim-Scandinavia/controlcenter/commit/3bfa594be6830755418cb65d1bf0e39d4393c8af))


### Miscellaneous Chores

* **deps:** update dependency barryvdh/laravel-debugbar to v3.12.1 ([#851](https://github.com/Vatsim-Scandinavia/controlcenter/issues/851)) ([9d328e8](https://github.com/Vatsim-Scandinavia/controlcenter/commit/9d328e8411800c4ce077fcd3e7147c514035a9a0))
* **deps:** update dependency laravel/framework to v10.48.2 ([#854](https://github.com/Vatsim-Scandinavia/controlcenter/issues/854)) ([13d27e3](https://github.com/Vatsim-Scandinavia/controlcenter/commit/13d27e398021f64f5cbba22d2854f1abe3a4eb4c))
* **deps:** update dependency mockery/mockery to v1.6.9 ([#852](https://github.com/Vatsim-Scandinavia/controlcenter/issues/852)) ([93cdbb3](https://github.com/Vatsim-Scandinavia/controlcenter/commit/93cdbb3bdbdd391fa5ca649ddd109c503dcc5276))
* **deps:** update dependency phpunit/phpunit to v10.5.13 ([#853](https://github.com/Vatsim-Scandinavia/controlcenter/issues/853)) ([b9657ca](https://github.com/Vatsim-Scandinavia/controlcenter/commit/b9657ca4a3f6e4d8044605f9b6c99aa3eddb9830))

## [5.2.0](https://github.com/Vatsim-Scandinavia/controlcenter/compare/v5.1.1...v5.2.0) (2024-03-11)


### Features

* integration with VATSIM API Core v2 (rating times) ([26a30f5](https://github.com/Vatsim-Scandinavia/controlcenter/commit/26a30f5d1155e095e3e898e91d091482d8fed558))
* integration with VATSIM API Core v2 (roster) ([b3ba08a](https://github.com/Vatsim-Scandinavia/controlcenter/commit/b3ba08a31537ce2d48def05314dd6d8b624d5480))
* subdivision short names ([686985d](https://github.com/Vatsim-Scandinavia/controlcenter/commit/686985d5820c532e9b3fa18ab00222077880e7be))


### Bug Fixes

* activity logic when applying ([e7f38b0](https://github.com/Vatsim-Scandinavia/controlcenter/commit/e7f38b069a2ced933dae5a37feacdcadd63d4f2e))
* missing Content-Type in FileController ([#841](https://github.com/Vatsim-Scandinavia/controlcenter/issues/841)) ([321cb28](https://github.com/Vatsim-Scandinavia/controlcenter/commit/321cb283d004fda82bea1d96ccc71a3941a7c5ed))
* s1 exams no longer uploaded to division ([d519055](https://github.com/Vatsim-Scandinavia/controlcenter/commit/d51905558fe855dfdc5e08a1294c3be41e504dc2))


### Miscellaneous Chores

* **deps:** update dependency barryvdh/laravel-debugbar to v3.11.1 ([#845](https://github.com/Vatsim-Scandinavia/controlcenter/issues/845)) ([c69f961](https://github.com/Vatsim-Scandinavia/controlcenter/commit/c69f961215191dd31817025e8e6e15dc56767b9f))
* **deps:** update dependency phpunit/phpunit to v10.5.12 ([#846](https://github.com/Vatsim-Scandinavia/controlcenter/issues/846)) ([3a9d33d](https://github.com/Vatsim-Scandinavia/controlcenter/commit/3a9d33df5ccee973c31ceecd5269f68791ec3237))
* **deps:** update dependency vite to v5.1.6 ([#847](https://github.com/Vatsim-Scandinavia/controlcenter/issues/847)) ([ba5e0d3](https://github.com/Vatsim-Scandinavia/controlcenter/commit/ba5e0d3cdf108dbdabb91ae9c19537b30fccbd67))
* **deps:** update node.js to v21.7.1 ([#843](https://github.com/Vatsim-Scandinavia/controlcenter/issues/843)) ([ee75f42](https://github.com/Vatsim-Scandinavia/controlcenter/commit/ee75f42f6796ed33bf0918c7024fccc455fd7c0a))

## [5.1.1](https://github.com/Vatsim-Scandinavia/controlcenter/compare/v5.1.0...v5.1.1) (2024-03-05)


### Bug Fixes

* only upload CPT exams to integration ([239b0b9](https://github.com/Vatsim-Scandinavia/controlcenter/commit/239b0b9cde26be688f5203d84090104a8eb40145)), closes [#832](https://github.com/Vatsim-Scandinavia/controlcenter/issues/832)


### Miscellaneous Chores

* **config:** migrate config .github/renovate.json ([0b6b635](https://github.com/Vatsim-Scandinavia/controlcenter/commit/0b6b635d8392708e6ee0f17185ef8a0ad21d0faa))
* **config:** migrate renovate config ([#839](https://github.com/Vatsim-Scandinavia/controlcenter/issues/839)) ([0b6b635](https://github.com/Vatsim-Scandinavia/controlcenter/commit/0b6b635d8392708e6ee0f17185ef8a0ad21d0faa))
* **deps:** update dependency doctrine/dbal to v3.8.3 ([b8df474](https://github.com/Vatsim-Scandinavia/controlcenter/commit/b8df474f9814fde6afde6cce686a98f2fbb3e45f))
* **deps:** update dependency laravel/framework to v10.47.0 ([2ff02ba](https://github.com/Vatsim-Scandinavia/controlcenter/commit/2ff02ba745ff292d8d02b31e9cae54e1f1be06a4))
* **deps:** update dependency laravel/ui to v4.5.0 ([eea1d9d](https://github.com/Vatsim-Scandinavia/controlcenter/commit/eea1d9d4ac118d0fd120289cbcad8d55bb8cc7fa))
* **deps:** update dependency vite to v5.1.5 ([1f2511a](https://github.com/Vatsim-Scandinavia/controlcenter/commit/1f2511a099094e5d1064640d95182596f7b4987a))

## [5.1.0](https://github.com/Vatsim-Scandinavia/controlcenter/compare/v5.0.4...v5.1.0) (2024-03-03)


### Features

* division API integration to tasks ([#789](https://github.com/Vatsim-Scandinavia/controlcenter/issues/789)) ([a0ab835](https://github.com/Vatsim-Scandinavia/controlcenter/commit/a0ab83562584badb26e27cdcd3e78281311fdf50))
* Mark users who leave subdiv as inactive ([0ddceb0](https://github.com/Vatsim-Scandinavia/controlcenter/commit/0ddceb00cd185221c5097010ceef859c7a4b27d1))
* require refresh to include all endorsements ([308d1b4](https://github.com/Vatsim-Scandinavia/controlcenter/commit/308d1b420e09a6b34d989dfd96d33f616c37ab4d))


### Bug Fixes

* error when missing fields in manual training creation ([4f58bd9](https://github.com/Vatsim-Scandinavia/controlcenter/commit/4f58bd9b636b1a5f789d6c660d64f8c3a7bc4b9c))
* improved sort of endorsements in user view ([891c81c](https://github.com/Vatsim-Scandinavia/controlcenter/commit/891c81c951042a42fe0302fa1529bed6789c5dba))
* re-opening trainings causing student to have multiple trainings ([89fd02f](https://github.com/Vatsim-Scandinavia/controlcenter/commit/89fd02f291be0dbc8c917ad62518379b21ffe21e))
* rename position to facility endorsements ([67a805d](https://github.com/Vatsim-Scandinavia/controlcenter/commit/67a805d79370dd2ca220d9e4dd349c80d63079c0))
* scoped task assign suggestion based on area ([4c19076](https://github.com/Vatsim-Scandinavia/controlcenter/commit/4c19076fd9cf01a6d2fde06bd43866c7dc98b21e))
* show visiting in roster ([5a3cd6d](https://github.com/Vatsim-Scandinavia/controlcenter/commit/5a3cd6d3de11aa3a40460bc7691dd78ecf4cddb9))
* showing double of same endorsement for visitors ([cc9f118](https://github.com/Vatsim-Scandinavia/controlcenter/commit/cc9f1186b1bb0572f4c32777610dfb29b7c82a4d))


### Miscellaneous Chores

* **deps:** update dependency barryvdh/laravel-debugbar to v3.10.6 ([#822](https://github.com/Vatsim-Scandinavia/controlcenter/issues/822)) ([d605e32](https://github.com/Vatsim-Scandinavia/controlcenter/commit/d605e32c0fb8c6a82c46026a9401a35671282de9))

## [5.0.4](https://github.com/Vatsim-Scandinavia/controlcenter/compare/v5.0.3...v5.0.4) (2024-03-01)


### Bug Fixes

* make users with familiarisation training active ([3f2f1b2](https://github.com/Vatsim-Scandinavia/controlcenter/commit/3f2f1b243c5143bf0f5ff6c9c659fd337f5587a7))
* user activity not set on completion if they had no hours in area ([1ffca2d](https://github.com/Vatsim-Scandinavia/controlcenter/commit/1ffca2d885ffe6715a843d3a655ab42b4bc9f473))

## [5.0.3](https://github.com/Vatsim-Scandinavia/controlcenter/compare/v5.0.2...v5.0.3) (2024-02-29)


### Bug Fixes

* added endorsement type ([cf524f5](https://github.com/Vatsim-Scandinavia/controlcenter/commit/cf524f5a93fe4c247a80376d67447b2e62e15fc2))
* area_id migration unsync ([12d02f5](https://github.com/Vatsim-Scandinavia/controlcenter/commit/12d02f5d0a1a19b83d36927e4af1a279643cafe9))
* force user to choose training ([063e64b](https://github.com/Vatsim-Scandinavia/controlcenter/commit/063e64b951e2794b734c250be09bb6e5ab2a655f))
* removed autobundle with ratings ([3f94802](https://github.com/Vatsim-Scandinavia/controlcenter/commit/3f94802a6c86cb41b41c4f0626741f4226c2cc8d))
* support for websites as student SOP ([c3a904e](https://github.com/Vatsim-Scandinavia/controlcenter/commit/c3a904ee53fed72c5bbce0a2cf1c50c4eebc5087))


### Miscellaneous Chores

* **deps:** update dependency bootstrap to v5.3.3 ([dea9883](https://github.com/Vatsim-Scandinavia/controlcenter/commit/dea9883f7669f9f1d232a0d9a67d552eb50ecede))
* **deps:** update dependency bootstrap-table to v1.22.3 ([7116357](https://github.com/Vatsim-Scandinavia/controlcenter/commit/71163578f18be31488067f833ca0d4e0edef44a6))
* **deps:** update dependency chart.js to v4.4.2 ([8277249](https://github.com/Vatsim-Scandinavia/controlcenter/commit/827724947ef5777e4092f396b511994b512ade6b))
* **deps:** update dependency laravel-vite-plugin to v1.0.2 ([aedaf76](https://github.com/Vatsim-Scandinavia/controlcenter/commit/aedaf766e9a9de57c63cf145ea4f0356aa7ee9f3))
* **deps:** update dependency laravel/framework to v10.46.0 ([d469fa1](https://github.com/Vatsim-Scandinavia/controlcenter/commit/d469fa1ad22dd51b764e3c213b030f564df885f6))
* **deps:** update dependency laravel/pint to v1.14.0 ([#812](https://github.com/Vatsim-Scandinavia/controlcenter/issues/812)) ([e9299a1](https://github.com/Vatsim-Scandinavia/controlcenter/commit/e9299a1dfc49ef5a4a07865e18cb00ba561c3209))
* **deps:** update dependency phpunit/phpunit to v10.5.11 ([#814](https://github.com/Vatsim-Scandinavia/controlcenter/issues/814)) ([99a09d4](https://github.com/Vatsim-Scandinavia/controlcenter/commit/99a09d46b4cc82cdee23e86ff242d4fade9ccd28))
* **deps:** update dependency sass to v1.71.1 ([ff1fd4c](https://github.com/Vatsim-Scandinavia/controlcenter/commit/ff1fd4cacdcf3d4ab2c62759ee43309dca0f9b55))
* **deps:** update dependency vite to v5.1.4 ([6c9a5bf](https://github.com/Vatsim-Scandinavia/controlcenter/commit/6c9a5bf9e48cf99019125158aa2ec40ec263b9fc))
* **deps:** update dependency vue to v3.4.21 ([de4ff64](https://github.com/Vatsim-Scandinavia/controlcenter/commit/de4ff640ed0698f49c0f04b1aaec5de022c17ff2))
* **deps:** update docker.io/library/php docker tag to v8.3.3 ([#809](https://github.com/Vatsim-Scandinavia/controlcenter/issues/809)) ([8777b5c](https://github.com/Vatsim-Scandinavia/controlcenter/commit/8777b5c940b72dc93efc7923f4c79f2156c07f68))
* **deps:** update node.js to v21.6.2 ([#804](https://github.com/Vatsim-Scandinavia/controlcenter/issues/804)) ([5fbae0e](https://github.com/Vatsim-Scandinavia/controlcenter/commit/5fbae0eca516d60368082588dc61539e17731382))

## [5.0.2](https://github.com/Vatsim-Scandinavia/controlcenter/compare/v5.0.1...v5.0.2) (2024-02-16)


### Bug Fixes

* area-&gt;positions relationship ([b3d5a7f](https://github.com/Vatsim-Scandinavia/controlcenter/commit/b3d5a7f13e90a4bc9b528dfa83d724400743f5c7))
* rounded atc hours in training list ([19e40d5](https://github.com/Vatsim-Scandinavia/controlcenter/commit/19e40d5936e930a90218bbabcbceaabef35d4902))

## [5.0.1](https://github.com/Vatsim-Scandinavia/controlcenter/compare/v5.0.0...v5.0.1) (2024-02-15)


### Bug Fixes

* ATC hours in training index was zero ([1b82ba9](https://github.com/Vatsim-Scandinavia/controlcenter/commit/1b82ba99722da5d77f7d700f6e144c499c457970)), closes [#792](https://github.com/Vatsim-Scandinavia/controlcenter/issues/792)
* check online controllers missing null check ([c151f0a](https://github.com/Vatsim-Scandinavia/controlcenter/commit/c151f0aee0bf4b18549ce6fca22409abee28be1d)), closes [#793](https://github.com/Vatsim-Scandinavia/controlcenter/issues/793)
* move activity and notification to area level fixes [#795](https://github.com/Vatsim-Scandinavia/controlcenter/issues/795) ([a05f7c3](https://github.com/Vatsim-Scandinavia/controlcenter/commit/a05f7c3bfeebca61a4e77ed6c4d6ba9ae7022c70))
* roster dropdown rating filter ([460ddd5](https://github.com/Vatsim-Scandinavia/controlcenter/commit/460ddd583e6a6549ea4d39b0005dedd898e6a7c2))
* work email displaying wrong in settings ([11b8e51](https://github.com/Vatsim-Scandinavia/controlcenter/commit/11b8e51d454578cdbfeebad27bcb8f4ce8cf9cd9)), closes [#791](https://github.com/Vatsim-Scandinavia/controlcenter/issues/791)


### Miscellaneous Chores

* **deps:** update dependency barryvdh/laravel-debugbar to v3.10.1 ([#794](https://github.com/Vatsim-Scandinavia/controlcenter/issues/794)) ([9b607d4](https://github.com/Vatsim-Scandinavia/controlcenter/commit/9b607d45098104abe876b2b8d4831247dcfd6eb4))
* **deps:** update dependency barryvdh/laravel-debugbar to v3.10.5 ([#796](https://github.com/Vatsim-Scandinavia/controlcenter/issues/796)) ([4e1d154](https://github.com/Vatsim-Scandinavia/controlcenter/commit/4e1d154219d23bb1e0e108fe9b19a893dc67e2d3))
* **deps:** update dependency doctrine/dbal to v3.8.2 ([#803](https://github.com/Vatsim-Scandinavia/controlcenter/issues/803)) ([03aa548](https://github.com/Vatsim-Scandinavia/controlcenter/commit/03aa54810e7dc060467bd1562a9f9f5897a09615))
* **deps:** update dependency laravel/framework to v10.44.0 ([#798](https://github.com/Vatsim-Scandinavia/controlcenter/issues/798)) ([356501c](https://github.com/Vatsim-Scandinavia/controlcenter/commit/356501cfb8a8a3aa51a64a27634c58cf3552cbdc))
* **deps:** update dependency laravel/pint to v1.13.11 ([#799](https://github.com/Vatsim-Scandinavia/controlcenter/issues/799)) ([c2f4b6b](https://github.com/Vatsim-Scandinavia/controlcenter/commit/c2f4b6bedbaeba63ebad03107b4bf264bae9fff1))
* **deps:** update dependency vite to v5.1.3 ([#801](https://github.com/Vatsim-Scandinavia/controlcenter/issues/801)) ([cd17348](https://github.com/Vatsim-Scandinavia/controlcenter/commit/cd17348163c23e8511a7600aca4bb193a158ab76))
* **deps:** update dependency vue to v3.4.19 ([#797](https://github.com/Vatsim-Scandinavia/controlcenter/issues/797)) ([f4c9239](https://github.com/Vatsim-Scandinavia/controlcenter/commit/f4c9239da4363c5ae6d81a04f1ac7ae9ea4bb139))

## [5.0.0](https://github.com/Vatsim-Scandinavia/controlcenter/compare/v4.5.0...v5.0.0) (2024-02-11)


### ⚠ BREAKING CHANGES

* GCAP Support (rosters and removal of S1 functions) ([#764](https://github.com/Vatsim-Scandinavia/controlcenter/issues/764))

### Features

* activity graph on user profile ([af40e1b](https://github.com/Vatsim-Scandinavia/controlcenter/commit/af40e1b1abc4993a64ae516a992fbfdb3b7381ac)), closes [#507](https://github.com/Vatsim-Scandinavia/controlcenter/issues/507)
* feedback url in completed emails ([15cb267](https://github.com/Vatsim-Scandinavia/controlcenter/commit/15cb267a87e430dd3d09c50045f3559bff39aa77)), closes [#759](https://github.com/Vatsim-Scandinavia/controlcenter/issues/759)
* GCAP Support (rosters and removal of S1 functions) ([#764](https://github.com/Vatsim-Scandinavia/controlcenter/issues/764)) ([a1b73d5](https://github.com/Vatsim-Scandinavia/controlcenter/commit/a1b73d5873d97a8a073cc54cb64dce1f6bfcd13c)), closes [#753](https://github.com/Vatsim-Scandinavia/controlcenter/issues/753)
* related tasks in training view ([7db334c](https://github.com/Vatsim-Scandinavia/controlcenter/commit/7db334c9f86614200b0f65959ca8d26639163412)), closes [#762](https://github.com/Vatsim-Scandinavia/controlcenter/issues/762)


### Bug Fixes

* **ci:** extract global settings from packages ([d9bfbd2](https://github.com/Vatsim-Scandinavia/controlcenter/commit/d9bfbd2c93ff2304fdbe4f8838885007463c7695))
* **ci:** remove draft & redundant release-type ([84c0181](https://github.com/Vatsim-Scandinavia/controlcenter/commit/84c01811dc84210c66aa9c7c62cf6243ab1cafc0))
* editable training comments by other than author ([92404ef](https://github.com/Vatsim-Scandinavia/controlcenter/commit/92404ef0d0c55f4b8ed81f183cbd83f7f48be1ed)), closes [#752](https://github.com/Vatsim-Scandinavia/controlcenter/issues/752)


### Miscellaneous Chores

* **deps:** manual composer upgrades ([71c0473](https://github.com/Vatsim-Scandinavia/controlcenter/commit/71c0473c3b22534ea7ad66de555d450e45459817))
* **deps:** manual update of composer/npm ([86300c2](https://github.com/Vatsim-Scandinavia/controlcenter/commit/86300c217d2eecdaea4cddbd231f17733123f5fe))
* **deps:** update actions/cache action to v4 ([#777](https://github.com/Vatsim-Scandinavia/controlcenter/issues/777)) ([ba8cfb5](https://github.com/Vatsim-Scandinavia/controlcenter/commit/ba8cfb513661caee3965e5e666953e9e37f794ac))
* **deps:** update dependency @vitejs/plugin-vue to v5.0.3 ([fca082d](https://github.com/Vatsim-Scandinavia/controlcenter/commit/fca082d15e832b54332ec5f2f5e18ade09882397))
* **deps:** update dependency @vitejs/plugin-vue to v5.0.4 ([b7a4b6b](https://github.com/Vatsim-Scandinavia/controlcenter/commit/b7a4b6b0bc467f830dc29c385a714a5ce51dda66))
* **deps:** update dependency bootstrap-table to v1.22.2 ([a3bd11a](https://github.com/Vatsim-Scandinavia/controlcenter/commit/a3bd11a752312b3e4d1775d199ade0fddd43d50f))
* **deps:** update dependency doctrine/dbal to v3.8.1 ([9194830](https://github.com/Vatsim-Scandinavia/controlcenter/commit/9194830dda8a626265e6f8283ccf90415b407a5c))
* **deps:** update dependency fakerphp/faker to v1.23.1 ([#746](https://github.com/Vatsim-Scandinavia/controlcenter/issues/746)) ([79ce97e](https://github.com/Vatsim-Scandinavia/controlcenter/commit/79ce97e24f2ac4d953dd730a5520683833c8330e))
* **deps:** update dependency hisorange/browser-detect to v5.0.2 ([5dff455](https://github.com/Vatsim-Scandinavia/controlcenter/commit/5dff455fea568c35faeb02bd055718f146c1c535))
* **deps:** update dependency hisorange/browser-detect to v5.0.3 ([36f841e](https://github.com/Vatsim-Scandinavia/controlcenter/commit/36f841e0947db5faf5069673719209fb2d60bcc1))
* **deps:** update dependency laravel-vite-plugin to v1.0.1 ([adb7b24](https://github.com/Vatsim-Scandinavia/controlcenter/commit/adb7b24303daf3dec43bb4b76754860a77813994))
* **deps:** update dependency laravel/ui to v4.4.0 ([2218e5c](https://github.com/Vatsim-Scandinavia/controlcenter/commit/2218e5c5d1e1efe6f1c2e04ff18b538d1755b3b3))
* **deps:** update dependency league/commonmark to v2.4.2 ([b53a4cd](https://github.com/Vatsim-Scandinavia/controlcenter/commit/b53a4cdc6882f6fa52a6ae335556d69700c39676))
* **deps:** update dependency moment to v2.30.1 ([b51312f](https://github.com/Vatsim-Scandinavia/controlcenter/commit/b51312f5ebad1b2688495ca3b24e1942354674ba))
* **deps:** update dependency phpunit/phpunit to v10.5.10 ([#740](https://github.com/Vatsim-Scandinavia/controlcenter/issues/740)) ([beacd12](https://github.com/Vatsim-Scandinavia/controlcenter/commit/beacd12a79a7004d0d60ee1604e6291607821402))
* **deps:** update dependency sass to v1.70.0 ([6f4c2c2](https://github.com/Vatsim-Scandinavia/controlcenter/commit/6f4c2c2e4fb71b066bdda38910cec1e08c3d4d36))
* **deps:** update dependency spatie/laravel-ignition to v2.4.2 ([634b6f6](https://github.com/Vatsim-Scandinavia/controlcenter/commit/634b6f6e651742c7ee82a95d5a5b3f38708de60b))
* **deps:** update dependency vite to v5.0.12 [security] ([f94e764](https://github.com/Vatsim-Scandinavia/controlcenter/commit/f94e764d536a3fd604d75b82ed26642178f209d7))
* **deps:** update dependency vite to v5.1.1 ([#786](https://github.com/Vatsim-Scandinavia/controlcenter/issues/786)) ([914a595](https://github.com/Vatsim-Scandinavia/controlcenter/commit/914a595f37b50eec967153c6839115485ece3c8a))
* **deps:** update dependency vue to v3.4.1 ([456bcc6](https://github.com/Vatsim-Scandinavia/controlcenter/commit/456bcc6495a14602a3e3112dabc536d5e239a82b))
* **deps:** update dependency vue to v3.4.18 ([3998510](https://github.com/Vatsim-Scandinavia/controlcenter/commit/39985106a419fc8d1cbc582e405b8a48f8049ae2))
* **deps:** update docker.io/library/mysql docker tag to v8.3.0 ([#770](https://github.com/Vatsim-Scandinavia/controlcenter/issues/770)) ([4e072aa](https://github.com/Vatsim-Scandinavia/controlcenter/commit/4e072aac4990d1242365b4f0dfe53d0aa6f70553))
* **deps:** update docker.io/library/php docker tag to v8.3.2 ([#742](https://github.com/Vatsim-Scandinavia/controlcenter/issues/742)) ([4c270c2](https://github.com/Vatsim-Scandinavia/controlcenter/commit/4c270c2164ceb428eec9839958d72b6e6901bc86))
* **deps:** update google-github-actions/release-please-action action to v4 ([#731](https://github.com/Vatsim-Scandinavia/controlcenter/issues/731)) ([084130c](https://github.com/Vatsim-Scandinavia/controlcenter/commit/084130ce705014e0f5b825b5e39aa1e729ad3257))
* **deps:** update mlocati/php-extension-installer docker tag to v2.1.82 ([#766](https://github.com/Vatsim-Scandinavia/controlcenter/issues/766)) ([969312b](https://github.com/Vatsim-Scandinavia/controlcenter/commit/969312b21eb97a7a45a5b2af2a197fab2ef90b11))
* **deps:** update mlocati/php-extension-installer docker tag to v2.2.0 ([#782](https://github.com/Vatsim-Scandinavia/controlcenter/issues/782)) ([f1cfecd](https://github.com/Vatsim-Scandinavia/controlcenter/commit/f1cfecd6b2c7ccd1091313b02ed1571a55ae0fc7))
* **deps:** update mlocati/php-extension-installer docker tag to v2.2.1 ([#783](https://github.com/Vatsim-Scandinavia/controlcenter/issues/783)) ([ba39cee](https://github.com/Vatsim-Scandinavia/controlcenter/commit/ba39ceefaea961fa570e53eeda79cd791e4cf8da))
* **deps:** update mlocati/php-extension-installer docker tag to v2.2.2 ([#787](https://github.com/Vatsim-Scandinavia/controlcenter/issues/787)) ([c46fdc1](https://github.com/Vatsim-Scandinavia/controlcenter/commit/c46fdc1ce2024790e86ccfc74dde4ad8e8edc046))
* **deps:** update node.js to v21.6.1 ([#771](https://github.com/Vatsim-Scandinavia/controlcenter/issues/771)) ([55de624](https://github.com/Vatsim-Scandinavia/controlcenter/commit/55de6247aa2417b0053b7b53cdf7564a14f519c5))
* **deps:** update pdm-project/setup-pdm action to v4 ([#779](https://github.com/Vatsim-Scandinavia/controlcenter/issues/779)) ([5d4b7fa](https://github.com/Vatsim-Scandinavia/controlcenter/commit/5d4b7faf14e8df1defa37d5841fa434d4eff746e))
* **deps:** update pre-commit/action action to v3.0.1 ([#784](https://github.com/Vatsim-Scandinavia/controlcenter/issues/784)) ([b664df4](https://github.com/Vatsim-Scandinavia/controlcenter/commit/b664df4915501492ee4945a4124d666e8ca1a8f2))

## [4.5.0](https://github.com/Vatsim-Scandinavia/controlcenter/compare/v4.4.2...v4.5.0) (2024-01-06)


### Features

* added cleanup of permanent endorsements ([6bda09c](https://github.com/Vatsim-Scandinavia/controlcenter/commit/6bda09c197d0dc401367f649da99d5782992b8a0))
* quick add for tasks ([4a3e10b](https://github.com/Vatsim-Scandinavia/controlcenter/commit/4a3e10b74d0b31249e10d77b8dbf8cb1ff6ae671))


### Bug Fixes

* added moderators to exam task assignment autocomplete ([9f624d5](https://github.com/Vatsim-Scandinavia/controlcenter/commit/9f624d525c976fb89543b5ac28a033a05675d938)), closes [#713](https://github.com/Vatsim-Scandinavia/controlcenter/issues/713)
* autodump warning due to lowercasing ([0e1ebd1](https://github.com/Vatsim-Scandinavia/controlcenter/commit/0e1ebd180fb8a3ce1e6bf925827934fa0ddfd955))
* completed training banner on dashboard ([de5922b](https://github.com/Vatsim-Scandinavia/controlcenter/commit/de5922ba3d94d3f75c0ac91f57da92891fedd1b1)), closes [#719](https://github.com/Vatsim-Scandinavia/controlcenter/issues/719)
* **deps:** update dependency @fortawesome/fontawesome-free to v6.5.1 ([#716](https://github.com/Vatsim-Scandinavia/controlcenter/issues/716)) ([fb3451e](https://github.com/Vatsim-Scandinavia/controlcenter/commit/fb3451e769623cd38332d73714d4a55b9463343c))
* **deps:** update dependency @vitejs/plugin-vue to v4.5.2 ([#714](https://github.com/Vatsim-Scandinavia/controlcenter/issues/714)) ([59fc250](https://github.com/Vatsim-Scandinavia/controlcenter/commit/59fc2504c2c5948c817003317d78339bb762ad7c))
* **deps:** update dependency @vitejs/plugin-vue to v4.6.0 ([#735](https://github.com/Vatsim-Scandinavia/controlcenter/issues/735)) ([c1bab9f](https://github.com/Vatsim-Scandinavia/controlcenter/commit/c1bab9f30de273947a4cf89f730b8143dc32f936))
* **deps:** update dependency chart.js to v4.4.1 ([#724](https://github.com/Vatsim-Scandinavia/controlcenter/issues/724)) ([8d9e32d](https://github.com/Vatsim-Scandinavia/controlcenter/commit/8d9e32d06c06d5ac6e23b97ba4c8dd029b060923))
* **deps:** update dependency graham-campbell/markdown to v15.1.0 ([#729](https://github.com/Vatsim-Scandinavia/controlcenter/issues/729)) ([6ed40a8](https://github.com/Vatsim-Scandinavia/controlcenter/commit/6ed40a8c7d4c502cdb257755f3990a617cb6d535))
* **deps:** update dependency guzzlehttp/guzzle to v7.8.1 ([#725](https://github.com/Vatsim-Scandinavia/controlcenter/issues/725)) ([9dadc27](https://github.com/Vatsim-Scandinavia/controlcenter/commit/9dadc278f77bf1c0ca31d5b94608d261c7935617))
* **deps:** update dependency hisorange/browser-detect to v5.0.1 ([#726](https://github.com/Vatsim-Scandinavia/controlcenter/issues/726)) ([bfb0210](https://github.com/Vatsim-Scandinavia/controlcenter/commit/bfb021019d0f6a80b572a514d8f004d6d19b519a))
* **deps:** update dependency laravel/ui to v4.3.0 ([#712](https://github.com/Vatsim-Scandinavia/controlcenter/issues/712)) ([ffd61f1](https://github.com/Vatsim-Scandinavia/controlcenter/commit/ffd61f1be7854eec4b2df815fa7774eda8d1cac9))
* **deps:** update dependency vite to v4.5.1 [security] ([#717](https://github.com/Vatsim-Scandinavia/controlcenter/issues/717)) ([467f413](https://github.com/Vatsim-Scandinavia/controlcenter/commit/467f4131c21f2612e7e064c2901bce790cae849b))
* **deps:** update dependency vue to v3.3.13 ([#710](https://github.com/Vatsim-Scandinavia/controlcenter/issues/710)) ([8a2dbc3](https://github.com/Vatsim-Scandinavia/controlcenter/commit/8a2dbc3d4359438da727d1f4d3fbf57570184504))
* dont notify already completed/declined tasks ([4e8dec9](https://github.com/Vatsim-Scandinavia/controlcenter/commit/4e8dec9e217cd1cb30a7131915f55feb389ba355)), closes [#737](https://github.com/Vatsim-Scandinavia/controlcenter/issues/737)
* pinting new nullable types ([33820c4](https://github.com/Vatsim-Scandinavia/controlcenter/commit/33820c445c48b253a674d12a227e2e1d68986e8a))
* RouteServiceProvider issues ([ac2d665](https://github.com/Vatsim-Scandinavia/controlcenter/commit/ac2d665ab02ffea60fb22bebff43d6286a9a3fd3)), closes [#689](https://github.com/Vatsim-Scandinavia/controlcenter/issues/689)
* task datalist autocomplete bug introduced in 4a3e10b ([b84e22e](https://github.com/Vatsim-Scandinavia/controlcenter/commit/b84e22e0dfd4c3eef3c5178b94d9c695c7102f7d))
* tasks required checkbox config ([dd36a61](https://github.com/Vatsim-Scandinavia/controlcenter/commit/dd36a610e1f223422d1cf8d4329c8251a06507cb)), closes [#749](https://github.com/Vatsim-Scandinavia/controlcenter/issues/749)


### Miscellaneous Chores

* **deps:** misc dependency updates ([92e74ee](https://github.com/Vatsim-Scandinavia/controlcenter/commit/92e74ee599258f964040a270ae760568d5ea32cf))
* **deps:** update dependency mockery/mockery to v1.6.7 ([#723](https://github.com/Vatsim-Scandinavia/controlcenter/issues/723)) ([4d5402b](https://github.com/Vatsim-Scandinavia/controlcenter/commit/4d5402bfb702e32185d22b7acf26ffb1b3453ffd))
* **deps:** update dependency phpunit/phpunit to v10.5.3 ([#715](https://github.com/Vatsim-Scandinavia/controlcenter/issues/715)) ([7ec7162](https://github.com/Vatsim-Scandinavia/controlcenter/commit/7ec71624581ea6e67defb05bfa75579f0a384b1c))
* **deps:** update Laravel to v10.38.2 ([0febf14](https://github.com/Vatsim-Scandinavia/controlcenter/commit/0febf14e0d81947843eaa9fced015c85697e3750))
* **deps:** update mlocati/php-extension-installer docker tag to v2.1.75 ([#708](https://github.com/Vatsim-Scandinavia/controlcenter/issues/708)) ([5fb85c9](https://github.com/Vatsim-Scandinavia/controlcenter/commit/5fb85c97638001443b47e1aaa366baadb82b34c3))
* **deps:** update node.js to v21.5.0 ([#728](https://github.com/Vatsim-Scandinavia/controlcenter/issues/728)) ([dc102ec](https://github.com/Vatsim-Scandinavia/controlcenter/commit/dc102ec23df8c4d286a603e858862f6ab9b747dd))
* **deps:** updated node to 21 in templating ([87ba741](https://github.com/Vatsim-Scandinavia/controlcenter/commit/87ba741cd00eba4bce85c97dba68e12e3ee9216f))
* **deps:** upgraded vite to v5 ([#738](https://github.com/Vatsim-Scandinavia/controlcenter/issues/738)) ([68ba97e](https://github.com/Vatsim-Scandinavia/controlcenter/commit/68ba97e9e860a7d4cf9631f0de6a7fce1ac0b67a))

## [4.4.2](https://github.com/Vatsim-Scandinavia/controlcenter/compare/v4.4.1...v4.4.2) (2023-11-19)


### Bug Fixes

* rating upgrade task comment only for vatsim ratings ([2bcb484](https://github.com/Vatsim-Scandinavia/controlcenter/commit/2bcb484788df37f283720d66efb1e3ea0f5e2cdd))

## [4.4.1](https://github.com/Vatsim-Scandinavia/controlcenter/compare/v4.4.0...v4.4.1) (2023-11-19)


### Bug Fixes

* incorrect versioning ([91c1ee9](https://github.com/Vatsim-Scandinavia/controlcenter/commit/91c1ee9967a1736b490d100c310573619ff236a6))

## [4.4.0](https://github.com/Vatsim-Scandinavia/controlcenter/compare/v4.3.3...v4.4.0) (2023-11-19)


### Features

* create upgrade task via exam report ([#704](https://github.com/Vatsim-Scandinavia/controlcenter/issues/704)) ([f8956d1](https://github.com/Vatsim-Scandinavia/controlcenter/commit/f8956d1f8815788bf23edebcaf13728d5e9c3125))
* tasks required checkbox config ([204aa37](https://github.com/Vatsim-Scandinavia/controlcenter/commit/204aa37101a652f2bc8aacc65aa7473fac98f952)), closes [#694](https://github.com/Vatsim-Scandinavia/controlcenter/issues/694)


### Bug Fixes

* **deps:** update dependency @vitejs/plugin-vue to v4.5.0 ([#700](https://github.com/Vatsim-Scandinavia/controlcenter/issues/700)) ([90471c5](https://github.com/Vatsim-Scandinavia/controlcenter/commit/90471c5f60771232b54700112083d388ea9d5153))
* **deps:** update dependency doctrine/dbal to v3.7.2 ([#699](https://github.com/Vatsim-Scandinavia/controlcenter/issues/699)) ([708468b](https://github.com/Vatsim-Scandinavia/controlcenter/commit/708468b5891283d463956a501606618ad5dddf8e))
* **deps:** update dependency laravel/framework to v10.32.1 ([#681](https://github.com/Vatsim-Scandinavia/controlcenter/issues/681)) ([6261ddf](https://github.com/Vatsim-Scandinavia/controlcenter/commit/6261ddff6e8ebbeea2498360753062aeb32205bc))
* **deps:** update dependency sass to v1.69.5 ([#683](https://github.com/Vatsim-Scandinavia/controlcenter/issues/683)) ([d16f3b5](https://github.com/Vatsim-Scandinavia/controlcenter/commit/d16f3b59c614443c37b716754b3b902ee293376f))
* **deps:** update dependency sentry/sentry-laravel to v4 ([#701](https://github.com/Vatsim-Scandinavia/controlcenter/issues/701)) ([100d457](https://github.com/Vatsim-Scandinavia/controlcenter/commit/100d457d60c8d342363f66ab3000f1df9f52d1cd))
* **deps:** update dependency vue to v3.3.8 ([#682](https://github.com/Vatsim-Scandinavia/controlcenter/issues/682)) ([86d3f98](https://github.com/Vatsim-Scandinavia/controlcenter/commit/86d3f983228dd12f99bdf52c7721d9d0c750c11c))
* load routeserviceprovider from correct namespace ([0d3ed81](https://github.com/Vatsim-Scandinavia/controlcenter/commit/0d3ed8140f037386841be3ea0b54a28248d8b0c1))
* rounding error in training application hours ([918ec71](https://github.com/Vatsim-Scandinavia/controlcenter/commit/918ec7113fa9ed52313de7d30de7590f9d6f60ad)), closes [#687](https://github.com/Vatsim-Scandinavia/controlcenter/issues/687)
* show task request only in correct training types ([13c7626](https://github.com/Vatsim-Scandinavia/controlcenter/commit/13c76262bce58a499c673687d730fc1751896e7f)), closes [#696](https://github.com/Vatsim-Scandinavia/controlcenter/issues/696)
* submitter link in feedback report ([9bca71e](https://github.com/Vatsim-Scandinavia/controlcenter/commit/9bca71ef5dcf38991256df410520a64f85485dbc)), closes [#697](https://github.com/Vatsim-Scandinavia/controlcenter/issues/697)
* task table responsiveness on small screens ([34e0614](https://github.com/Vatsim-Scandinavia/controlcenter/commit/34e06145ef697f8f1aac816bd5639e1ef04828c3))


### Miscellaneous Chores

* **deps:** update actions/setup-node action to v4 ([#679](https://github.com/Vatsim-Scandinavia/controlcenter/issues/679)) ([0cb7dbe](https://github.com/Vatsim-Scandinavia/controlcenter/commit/0cb7dbe77cd0461ee35cf25d2eb0ac8d4cd5a083))
* **deps:** update dependency phpunit/phpunit to v10.4.2 ([#684](https://github.com/Vatsim-Scandinavia/controlcenter/issues/684)) ([f198920](https://github.com/Vatsim-Scandinavia/controlcenter/commit/f1989205e8e1560b7ce739bbb59a45b0d1487a45))
* **deps:** update docker.io/library/mysql docker tag to v8.2.0 ([#685](https://github.com/Vatsim-Scandinavia/controlcenter/issues/685)) ([5406647](https://github.com/Vatsim-Scandinavia/controlcenter/commit/5406647f01df6d11b82de1a0cb83819a51b5fe62))
* **deps:** update docker.io/library/php docker tag to v8.2.12 ([#686](https://github.com/Vatsim-Scandinavia/controlcenter/issues/686)) ([2d2cdca](https://github.com/Vatsim-Scandinavia/controlcenter/commit/2d2cdca5cb69fd8189d58707fd03c915d4ca1573))
* **deps:** update mlocati/php-extension-installer docker tag to v2.1.65 ([#698](https://github.com/Vatsim-Scandinavia/controlcenter/issues/698)) ([d7d51dd](https://github.com/Vatsim-Scandinavia/controlcenter/commit/d7d51ddceb9a9884a0323c838616a843dd98965a))
* **deps:** update node.js to v20.9.0 ([#671](https://github.com/Vatsim-Scandinavia/controlcenter/issues/671)) ([ffe4c87](https://github.com/Vatsim-Scandinavia/controlcenter/commit/ffe4c87031c545f1b81c3fa88df6696b86ccfb8e))
* **deps:** update node.js to v21 ([#676](https://github.com/Vatsim-Scandinavia/controlcenter/issues/676)) ([01a45e9](https://github.com/Vatsim-Scandinavia/controlcenter/commit/01a45e952c4c6b6a665ac94e03cae87793ba9541))
* **deps:** updated pint to v1.13.6 ([#703](https://github.com/Vatsim-Scandinavia/controlcenter/issues/703)) ([f0bbbf9](https://github.com/Vatsim-Scandinavia/controlcenter/commit/f0bbbf96f832b3cc76fbd3c47c9f6f5f58bbed46))

## [4.3.3](https://github.com/Vatsim-Scandinavia/controlcenter/compare/v4.3.2...v4.3.3) (2023-10-21)


### Bug Fixes

* **deps:** update dependency sass to v1.69.4 ([#672](https://github.com/Vatsim-Scandinavia/controlcenter/issues/672)) ([5f9f35f](https://github.com/Vatsim-Scandinavia/controlcenter/commit/5f9f35fc326365497d7d7e922a317b927d4c6915))
* **deps:** update dependency vite to v4.5.0 ([#673](https://github.com/Vatsim-Scandinavia/controlcenter/issues/673)) ([b777960](https://github.com/Vatsim-Scandinavia/controlcenter/commit/b7779608a47c79b0854fbcc576a0110f2f28146e))
* **deps:** update dependency vue to v3.3.6 ([#677](https://github.com/Vatsim-Scandinavia/controlcenter/issues/677)) ([498f3e3](https://github.com/Vatsim-Scandinavia/controlcenter/commit/498f3e34c89a9c0505ca70d23ab76da004ec9ed9))
* sort sent/archived tasks with closed first ([1f392da](https://github.com/Vatsim-Scandinavia/controlcenter/commit/1f392da2001a339338f8be5ae90ddf31260874df)), closes [#675](https://github.com/Vatsim-Scandinavia/controlcenter/issues/675)
* update atc hours database error ([0ce09f4](https://github.com/Vatsim-Scandinavia/controlcenter/commit/0ce09f45a1e2681f62d6a11dd96afab20f24569f)), closes [#674](https://github.com/Vatsim-Scandinavia/controlcenter/issues/674)

## [4.3.2](https://github.com/Vatsim-Scandinavia/controlcenter/compare/v4.3.1...v4.3.2) (2023-10-14)


### Bug Fixes

* check controller sending email to all ([fec85e2](https://github.com/Vatsim-Scandinavia/controlcenter/commit/fec85e2096536b1c4e2a6f300da7416bdd6281c9))

## [4.3.1](https://github.com/Vatsim-Scandinavia/controlcenter/compare/v4.3.0...v4.3.1) (2023-10-14)


### Bug Fixes

* added missing training activity comment ([e52b184](https://github.com/Vatsim-Scandinavia/controlcenter/commit/e52b184a20c2004aa94880be397d3438fd04d2b2))
* added optional label on feedback form ([75b8d52](https://github.com/Vatsim-Scandinavia/controlcenter/commit/75b8d5274328ad0102d3511ed180ab518fe53aea))
* check online controllers errors ([fbb735e](https://github.com/Vatsim-Scandinavia/controlcenter/commit/fbb735ebc8c675449218350e19bbc336f9e59e16))
* typo in endorsement emails ([4742a88](https://github.com/Vatsim-Scandinavia/controlcenter/commit/4742a8857eb858ead24824b7005f5801a6d3064e))

## [4.3.0](https://github.com/Vatsim-Scandinavia/controlcenter/compare/v4.2.3...v4.3.0) (2023-10-13)


### Features

* add feedback form ([#511](https://github.com/Vatsim-Scandinavia/controlcenter/issues/511)) ([8a56c3b](https://github.com/Vatsim-Scandinavia/controlcenter/commit/8a56c3b93fca1bafaa2f8ffdf09c4036ceffa947))
* add training request tasks ([#375](https://github.com/Vatsim-Scandinavia/controlcenter/issues/375)) ([24daf8e](https://github.com/Vatsim-Scandinavia/controlcenter/commit/24daf8eaa5d26c7cc46b190ca45c7de531b2fc1a))


### Bug Fixes

* **deps:** update dependency hisorange/browser-detect to v5 ([#662](https://github.com/Vatsim-Scandinavia/controlcenter/issues/662)) ([413fe30](https://github.com/Vatsim-Scandinavia/controlcenter/commit/413fe3017e2040038e21a4c99b95bff250230408))
* **deps:** update dependency laravel-vite-plugin to v0.8.1 ([#645](https://github.com/Vatsim-Scandinavia/controlcenter/issues/645)) ([2ba8a2f](https://github.com/Vatsim-Scandinavia/controlcenter/commit/2ba8a2f54c86956f88da2984ec7362eddeea2a2d))
* **deps:** update dependency laravel/framework to v10.28.0 ([#659](https://github.com/Vatsim-Scandinavia/controlcenter/issues/659)) ([c9b02ec](https://github.com/Vatsim-Scandinavia/controlcenter/commit/c9b02ece260a7e9155f302f91a7d2e3b1423f4f0))
* **deps:** update dependency sass to v1.69.2 ([#650](https://github.com/Vatsim-Scandinavia/controlcenter/issues/650)) ([317c282](https://github.com/Vatsim-Scandinavia/controlcenter/commit/317c28255d53d37de1557f1aea2f7097ba67a8ec))
* **deps:** update dependency sass to v1.69.3 ([#666](https://github.com/Vatsim-Scandinavia/controlcenter/issues/666)) ([b043732](https://github.com/Vatsim-Scandinavia/controlcenter/commit/b043732583335742687d0050f57f385ceb33920b))
* **deps:** update dependency sentry/sentry-laravel to v3.8.2 ([#667](https://github.com/Vatsim-Scandinavia/controlcenter/issues/667)) ([1480a81](https://github.com/Vatsim-Scandinavia/controlcenter/commit/1480a813bcefbf277ec1d05bf1ea85d73c9d00b0))
* **deps:** update dependency vite to v4.4.11 ([#646](https://github.com/Vatsim-Scandinavia/controlcenter/issues/646)) ([129d667](https://github.com/Vatsim-Scandinavia/controlcenter/commit/129d667e61e69eeee40d95f5c28213dcdeaf7ed0))
* null catching in uri for controller check cron ([06c5776](https://github.com/Vatsim-Scandinavia/controlcenter/commit/06c57765adaf9a6a412f771c81866cb326f9ff98)), closes [#644](https://github.com/Vatsim-Scandinavia/controlcenter/issues/644)


### Miscellaneous Chores

* composer update ([7a5bc61](https://github.com/Vatsim-Scandinavia/controlcenter/commit/7a5bc618beb9e6fde3af03fb2d9a62da8dd22551))
* **deps:** update actions/checkout action to v4 ([#651](https://github.com/Vatsim-Scandinavia/controlcenter/issues/651)) ([64fbaec](https://github.com/Vatsim-Scandinavia/controlcenter/commit/64fbaeca0dfd24af770d2f634ebbc3dbff7d353a))
* **deps:** update dependency laravel/pint to v1.13.3 ([#633](https://github.com/Vatsim-Scandinavia/controlcenter/issues/633)) ([bc43db0](https://github.com/Vatsim-Scandinavia/controlcenter/commit/bc43db0b038fc410293f1613d63f6fdde6a8909f))
* **deps:** update dependency nunomaduro/collision to v7.10.0 ([#665](https://github.com/Vatsim-Scandinavia/controlcenter/issues/665)) ([77cec18](https://github.com/Vatsim-Scandinavia/controlcenter/commit/77cec18e2d628beed2cd5a62417d6534c37eb00a))
* **deps:** update dependency phpunit/phpunit to v10.4.1 ([#647](https://github.com/Vatsim-Scandinavia/controlcenter/issues/647)) ([39b5068](https://github.com/Vatsim-Scandinavia/controlcenter/commit/39b50687883d94e24997d0f331f0152510e2e042))
* **deps:** update docker.io/library/mariadb docker tag to v11 ([#652](https://github.com/Vatsim-Scandinavia/controlcenter/issues/652)) ([b802872](https://github.com/Vatsim-Scandinavia/controlcenter/commit/b8028724f57aeb313c957936685ce1340d65b002))
* **deps:** update docker.io/library/php docker tag to v8.2.11 ([#587](https://github.com/Vatsim-Scandinavia/controlcenter/issues/587)) ([40bb717](https://github.com/Vatsim-Scandinavia/controlcenter/commit/40bb717707e79588b2570ee98d974141c5dedc31))
* **deps:** update docker.io/library/redis docker tag to v7 ([#653](https://github.com/Vatsim-Scandinavia/controlcenter/issues/653)) ([d7ff229](https://github.com/Vatsim-Scandinavia/controlcenter/commit/d7ff229ba6ff73b7ae15690eb6cca2537ef72d6c))
* **deps:** update docker/build-push-action action to v5 ([#654](https://github.com/Vatsim-Scandinavia/controlcenter/issues/654)) ([c6504c2](https://github.com/Vatsim-Scandinavia/controlcenter/commit/c6504c2a52aa1ef9570ba5b162125beae2f26d03))
* **deps:** update docker/login-action action to v3 ([#655](https://github.com/Vatsim-Scandinavia/controlcenter/issues/655)) ([4049dc9](https://github.com/Vatsim-Scandinavia/controlcenter/commit/4049dc92f0c8e2a0bed8dce38bede614da5cfcb1))
* **deps:** update docker/metadata-action action to v5 ([#656](https://github.com/Vatsim-Scandinavia/controlcenter/issues/656)) ([57aba6a](https://github.com/Vatsim-Scandinavia/controlcenter/commit/57aba6a5e6c97a37ceedc116cbde971b3663dc99))
* **deps:** update docker/setup-buildx-action action to v3 ([#660](https://github.com/Vatsim-Scandinavia/controlcenter/issues/660)) ([a3933fa](https://github.com/Vatsim-Scandinavia/controlcenter/commit/a3933fadd6ac6f424473b15f591dc5cacc8473a1))
* **deps:** update mlocati/php-extension-installer docker tag to v2.1.55 ([#605](https://github.com/Vatsim-Scandinavia/controlcenter/issues/605)) ([c6afcd8](https://github.com/Vatsim-Scandinavia/controlcenter/commit/c6afcd8e59aad025e1fe9e149175882e959ba11e))
* **deps:** update mlocati/php-extension-installer docker tag to v2.1.58 ([#657](https://github.com/Vatsim-Scandinavia/controlcenter/issues/657)) ([a5b7b60](https://github.com/Vatsim-Scandinavia/controlcenter/commit/a5b7b60a6aea101303f60f79df0a80d0c4ce16b5))
* **deps:** update node.js to v20.8.0 ([#648](https://github.com/Vatsim-Scandinavia/controlcenter/issues/648)) ([2927412](https://github.com/Vatsim-Scandinavia/controlcenter/commit/2927412e4f96ea4014a274a4b6572838f170c9f4))
* npm updates ([cf9cc9e](https://github.com/Vatsim-Scandinavia/controlcenter/commit/cf9cc9e67299c06def8c32f74b5c92d4d1aea5ee))
* upgrade to laravel 10 ([#638](https://github.com/Vatsim-Scandinavia/controlcenter/issues/638)) ([55a14dc](https://github.com/Vatsim-Scandinavia/controlcenter/commit/55a14dc768874e0585b4c0e080d59a2791cf48e3))
* version bump ([1d0a2a1](https://github.com/Vatsim-Scandinavia/controlcenter/commit/1d0a2a180e10d2aec30bfee8ca84cb8eae5423bf))
