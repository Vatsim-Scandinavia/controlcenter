# Changelog

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
