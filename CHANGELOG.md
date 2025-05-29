# Changelog

## [6.27.1](https://github.com/pressbooks/pressbooks/compare/6.27.0...6.27.1) (2025-05-29)


### Bug Fixes

* adjust dashboard layout ([#4068](https://github.com/pressbooks/pressbooks/issues/4068)) ([6464f40](https://github.com/pressbooks/pressbooks/commit/6464f40b691ea5b7498616dd2263b3e5b5c1b91a))


### Chores

* bump aws/aws-sdk-php from 3.343.9 to 3.343.13 in the composer-dependencies group ([#4050](https://github.com/pressbooks/pressbooks/issues/4050)) ([d65be41](https://github.com/pressbooks/pressbooks/commit/d65be4173ad6b46576299d8060cf48a8a7af4832))
* bump aws/aws-sdk-php in the composer-dependencies group ([d65be41](https://github.com/pressbooks/pressbooks/commit/d65be4173ad6b46576299d8060cf48a8a7af4832))
* revert unneccessary changes to table sorting ([#4056](https://github.com/pressbooks/pressbooks/issues/4056)) ([c111d7c](https://github.com/pressbooks/pressbooks/commit/c111d7c918a6eff795c9e5b566ed13e34810a329))

## [6.27.0](https://github.com/pressbooks/pressbooks/compare/6.26.1...6.27.0) (2025-05-28)


### Features

* display recent updates in the dashboard ([#4047](https://github.com/pressbooks/pressbooks/issues/4047)) ([2e4a683](https://github.com/pressbooks/pressbooks/commit/2e4a683adbf9c46d5aac9ff7910fea8928ed846a))
* switch to Coloris colour picker ([#4049](https://github.com/pressbooks/pressbooks/issues/4049)) ([6d72d9c](https://github.com/pressbooks/pressbooks/commit/6d72d9c44ef1aa63daaa3cd52f64040f864cb3ad))


### Bug Fixes

* restore scroll capabilities for my books list ([#4062](https://github.com/pressbooks/pressbooks/issues/4062)) ([f6a3925](https://github.com/pressbooks/pressbooks/commit/f6a3925dcca94653cbac0a960e2dffb8053d8e9e))


### Chores

* bump @pressbooks/multiselect from 2.4.0 to 2.4.1 in the npm-dependencies group (resolves pressbooks/private[#1617](https://github.com/pressbooks/pressbooks/issues/1617)) ([#4057](https://github.com/pressbooks/pressbooks/issues/4057)) ([9bdedb4](https://github.com/pressbooks/pressbooks/commit/9bdedb41a9ed23a1e7419c7696ef6e894603730c))
* bump @pressbooks/multiselect from 2.4.0 to 2.4.2 in the npm-dependencies group ([#4060](https://github.com/pressbooks/pressbooks/issues/4060)) ([f76cd27](https://github.com/pressbooks/pressbooks/commit/f76cd2720845bded1231ff05e42e3873bde31641))

## [6.26.1](https://github.com/pressbooks/pressbooks/compare/6.26.0...6.26.1) (2025-05-20)


### Bug Fixes

* prevent horizontal overflow in admin bar ([#4048](https://github.com/pressbooks/pressbooks/issues/4048)) ([a53bf3d](https://github.com/pressbooks/pressbooks/commit/a53bf3d401ac73288a93b764ea893032d10625c4))

## [6.26.0](https://github.com/pressbooks/pressbooks/compare/6.25.1...6.26.0) (2025-05-13)


### Features

* support edited volumes ([#2657](https://github.com/pressbooks/pressbooks/issues/2657)) ([7a94a48](https://github.com/pressbooks/pressbooks/commit/7a94a48ba1e1c1f8c1cd14d8f06ab251a5afc6e4))

### Bug Fixes

* add book media permissions for contributors ([#4039](https://github.com/pressbooks/pressbooks/issues/4039)) ([3ef96a5](https://github.com/pressbooks/pressbooks/commit/3ef96a5c59b2ca4a0b8a1850db3d6826df882c1d))
* add missing aria-describedby on duet-date picker inputs ([#4016](https://github.com/pressbooks/pressbooks/issues/4016)) ([176d42e](https://github.com/pressbooks/pressbooks/commit/176d42ea8a33d63d3cf80962ff5b44bc892909db))
* change heading level for media attributions ([#4043](https://github.com/pressbooks/pressbooks/issues/4043)) ([cd045e8](https://github.com/pressbooks/pressbooks/commit/cd045e8cb18260101cae36b89be39cdbb9145caa))
* change Touro College to Touro University ([#4023](https://github.com/pressbooks/pressbooks/issues/4023)) ([819c3f5](https://github.com/pressbooks/pressbooks/commit/819c3f58939915fced0505f0bf5676269e4ea1df))
* convert pressbooks table class to trait ([#4003](https://github.com/pressbooks/pressbooks/issues/4003)) ([a2f25d6](https://github.com/pressbooks/pressbooks/commit/a2f25d6e878e7b5ef27783b5c091a3aecfdacf16))
* do not apply new pb table headers to wp tables ([#4026](https://github.com/pressbooks/pressbooks/issues/4026)) ([6a7e20a](https://github.com/pressbooks/pressbooks/commit/6a7e20ac6374d5430e0c74da00e946e57088ecc2))
* edit capabilities for authors ([#4035](https://github.com/pressbooks/pressbooks/issues/4035)) ([b3ffc9e](https://github.com/pressbooks/pressbooks/commit/b3ffc9eb9b2504e7e2061a88e5d035c7d3a01b05))
* garbled charset in 2-level ToC ([#4032](https://github.com/pressbooks/pressbooks/issues/4032)) ([8010c63](https://github.com/pressbooks/pressbooks/commit/8010c6313e221a0ba7898fb533d937171d727cf6))
* global date picker with a11y improvement ([#4025](https://github.com/pressbooks/pressbooks/issues/4025)) ([ed635fa](https://github.com/pressbooks/pressbooks/commit/ed635fab622d75d13c145400377f71cfa8157956))
* pot translations workflow ([#4033](https://github.com/pressbooks/pressbooks/issues/4033)) ([6989cb1](https://github.com/pressbooks/pressbooks/commit/6989cb16e695c4e22d804b21f6f89f4932a8831c))
* replace guides and tutorials link with user guide ([#4030](https://github.com/pressbooks/pressbooks/issues/4030)) ([c1c5d9b](https://github.com/pressbooks/pressbooks/commit/c1c5d9bcdb0bb64cdbbfde67b03afbd3f5f0fc58))


### Chores

* bump aws/aws-sdk-php from 3.342.30 to 3.342.35 in the composer-dependencies group ([#4027](https://github.com/pressbooks/pressbooks/issues/4027)) ([b6b992d](https://github.com/pressbooks/pressbooks/commit/b6b992d576028fd87b4edc91b7af5109156bb99f))
* bump aws/aws-sdk-php in the composer-dependencies group ([b6b992d](https://github.com/pressbooks/pressbooks/commit/b6b992d576028fd87b4edc91b7af5109156bb99f))
* bump http-proxy-middleware from 2.0.7 to 2.0.9 in the npm_and_yarn group ([#4013](https://github.com/pressbooks/pressbooks/issues/4013)) ([2f172cd](https://github.com/pressbooks/pressbooks/commit/2f172cd31a20029005434467bdfa148c1a2f617a))
* bump http-proxy-middleware in the npm_and_yarn group ([2f172cd](https://github.com/pressbooks/pressbooks/commit/2f172cd31a20029005434467bdfa148c1a2f617a))
* bump instantsearch.js from 4.78.2 to 4.78.3 in the npm-dependencies group ([#4045](https://github.com/pressbooks/pressbooks/issues/4045)) ([7cc85d8](https://github.com/pressbooks/pressbooks/commit/7cc85d8b395e74bd6f1bb471ff24de2454b059ef))
* bump instantsearch.js in the npm-dependencies group ([7cc85d8](https://github.com/pressbooks/pressbooks/commit/7cc85d8b395e74bd6f1bb471ff24de2454b059ef))
* bump the npm-dependencies group across 1 directory with 2 updates ([#4034](https://github.com/pressbooks/pressbooks/issues/4034)) ([0a9a4b0](https://github.com/pressbooks/pressbooks/commit/0a9a4b0a44819cfb69e66ac1b975ec542c108cc3))
* **l10n:** Updates for project Pressbooks ([#4020](https://github.com/pressbooks/pressbooks/issues/4020)) ([6d3aee5](https://github.com/pressbooks/pressbooks/commit/6d3aee539c117be95a98d35551c012b6bcbc99cf))
* **l10n:** Updates for project Pressbooks ([#4024](https://github.com/pressbooks/pressbooks/issues/4024)) ([1bd9b48](https://github.com/pressbooks/pressbooks/commit/1bd9b4817797be5412dc2f4b0335c3aad59721bf))
* **l10n:** Updates for project Pressbooks ([#4041](https://github.com/pressbooks/pressbooks/issues/4041)) ([0e994d2](https://github.com/pressbooks/pressbooks/commit/0e994d270f13ce5e3dc1880b657af9ecd9760116))

## [6.25.1](https://github.com/pressbooks/pressbooks/compare/6.25.0...6.25.1) (2025-04-22)


### Bug Fixes

* add part aria-label typo ([#4012](https://github.com/pressbooks/pressbooks/issues/4012)) ([993f1b3](https://github.com/pressbooks/pressbooks/commit/993f1b3acaec33de597156291c4fd3a0c2a70dab))
* media attributions display for subscriber users ([#4005](https://github.com/pressbooks/pressbooks/issues/4005)) ([5ced795](https://github.com/pressbooks/pressbooks/commit/5ced79536221c718ae25a51e3c6448d74107b3c1))
* prevent horizontal overflow of admin top bar ([#4010](https://github.com/pressbooks/pressbooks/issues/4010)) ([568912e](https://github.com/pressbooks/pressbooks/commit/568912ec39f1df715c7ca8da031d636535ee951b))
* remove patterns menu ([#4006](https://github.com/pressbooks/pressbooks/issues/4006)) ([041444b](https://github.com/pressbooks/pressbooks/commit/041444bb9e0e1c835a6f0ca31a0e470f24e9a65f))
* remove unnecessary alt text in the admin dashboard ([#4004](https://github.com/pressbooks/pressbooks/issues/4004)) ([92b4450](https://github.com/pressbooks/pressbooks/commit/92b4450ac47811b3a729c27dc47f554dfebf61f8))
* subscriber attachments ([5ced795](https://github.com/pressbooks/pressbooks/commit/5ced79536221c718ae25a51e3c6448d74107b3c1))


### Chores

* bump eazy-logger from 4.0.1 to 4.1.0 in the npm_and_yarn group ([#3999](https://github.com/pressbooks/pressbooks/issues/3999)) ([160f0f1](https://github.com/pressbooks/pressbooks/commit/160f0f1ee9b194c9086416cf18bbe12b2c9ce6cb))
* bump the composer-dependencies group with 2 updates ([#3972](https://github.com/pressbooks/pressbooks/issues/3972)) ([37cf395](https://github.com/pressbooks/pressbooks/commit/37cf395a8271c46572d154c58454911142db90cd))
* bump the composer-dependencies group with 2 updates ([#4011](https://github.com/pressbooks/pressbooks/issues/4011)) ([bfe903f](https://github.com/pressbooks/pressbooks/commit/bfe903f73a0803fa243d9f0585f5e63ab903f780))
* revert "fix: prevent horizontal overflow of admin top bar" ([#4014](https://github.com/pressbooks/pressbooks/issues/4014)) ([1aba773](https://github.com/pressbooks/pressbooks/commit/1aba7737410bd0d71d069330ee2b97c3ff6e8a32))

## [6.25.0](https://github.com/pressbooks/pressbooks/compare/6.24.0...6.25.0) (2025-04-14)


### Features

* add accessible wp table headers ([#3990](https://github.com/pressbooks/pressbooks/issues/3990)) ([fe31b00](https://github.com/pressbooks/pressbooks/commit/fe31b00cf96b8dba68d86039c78cd5bccc318edf))


### Bug Fixes

* add autocomplete attribute for profile and contributors forms ([#3989](https://github.com/pressbooks/pressbooks/issues/3989)) ([ea2b6bf](https://github.com/pressbooks/pressbooks/commit/ea2b6bf3738dc54ac8d79505e6a7e2c809bfc854))
* default value for latest_files_public option ([#3985](https://github.com/pressbooks/pressbooks/issues/3985)) ([357377d](https://github.com/pressbooks/pressbooks/commit/357377d8357003e1588ec0686a04b7d60045e3d8))


### Chores

* code style improvements ([#4000](https://github.com/pressbooks/pressbooks/issues/4000)) ([13dc3e1](https://github.com/pressbooks/pressbooks/commit/13dc3e1894458a2df607bb73a804a9468d5e3865))
* code style issues ([13dc3e1](https://github.com/pressbooks/pressbooks/commit/13dc3e1894458a2df607bb73a804a9468d5e3865))
* improve coding style standards ([#3988](https://github.com/pressbooks/pressbooks/issues/3988)) ([b3978c0](https://github.com/pressbooks/pressbooks/commit/b3978c053107c0986bd39ee5d101e255c4ac69b7))
* update East Texas A&M University name ([#3997](https://github.com/pressbooks/pressbooks/issues/3997)) ([846a83a](https://github.com/pressbooks/pressbooks/commit/846a83aaafcb54db882e4ac8111d38180c612d3a))

## [6.24.0](https://github.com/pressbooks/pressbooks/compare/6.23.2...6.24.0) (2025-04-07)


### Features

* ensure checkbox groups use fieldset and legend ([#3982](https://github.com/pressbooks/pressbooks/issues/3982)) ([35e9daa](https://github.com/pressbooks/pressbooks/commit/35e9daaf5ecc324021dcf8230ec5afffe1f0999b))


### Bug Fixes

* apply accessible name to custom styles editor ([#3983](https://github.com/pressbooks/pressbooks/issues/3983)) ([e09c781](https://github.com/pressbooks/pressbooks/commit/e09c781842066fd303c265607e98b52bec65b68d))
* display Thema qualifiers in Subjects metaboxes ([#3979](https://github.com/pressbooks/pressbooks/issues/3979)) ([aed7bff](https://github.com/pressbooks/pressbooks/commit/aed7bff7fe23108917f090f57289ef55a9fb51cd))


### Chores

* update to Thema 1.6 terms ([#3981](https://github.com/pressbooks/pressbooks/issues/3981)) ([a7a9953](https://github.com/pressbooks/pressbooks/commit/a7a99538e9edd09f798c69451b68365d64abb02b))

## [6.23.2](https://github.com/pressbooks/pressbooks/compare/6.23.1...6.23.2) (2025-04-01)


### Bug Fixes

* conditionally require physics ([#3973](https://github.com/pressbooks/pressbooks/issues/3973)) ([b9e77a2](https://github.com/pressbooks/pressbooks/commit/b9e77a218c605a865c6fb372bfc1ee10248ea342))


### Chores

* bump instantsearch.js from 4.78.0 to 4.78.1 in the npm-dependencies group ([#3971](https://github.com/pressbooks/pressbooks/issues/3971)) ([8dffa1b](https://github.com/pressbooks/pressbooks/commit/8dffa1bcfe5639541777ea41f997300335a048a8))
* bump instantsearch.js in the npm-dependencies group ([8dffa1b](https://github.com/pressbooks/pressbooks/commit/8dffa1bcfe5639541777ea41f997300335a048a8))

## [6.23.1](https://github.com/pressbooks/pressbooks/compare/6.23.0...6.23.1) (2025-03-26)


### Bug Fixes

* add `th` to row headers in the organize page for better a11y ([#3968](https://github.com/pressbooks/pressbooks/issues/3968)) ([754e6f7](https://github.com/pressbooks/pressbooks/commit/754e6f7bb25d89d9dbe4493f49e6d219255be8c8))
* recognize all supported math delimiters in web book interface ([#3969](https://github.com/pressbooks/pressbooks/issues/3969)) ([89c4869](https://github.com/pressbooks/pressbooks/commit/89c4869be6580b190fdaa2a1356c548b25f5f54f))

## [6.23.0](https://github.com/pressbooks/pressbooks/compare/6.22.4...6.23.0) (2025-03-24)


### Features

* Add PDF preview option to export page (and improve PDF preview on diagnostics page) ([#3939](https://github.com/pressbooks/pressbooks/issues/3939)) ([0344735](https://github.com/pressbooks/pressbooks/commit/034473547467b8e7a94f298e822d7c983726a11c))
* upgrade pb-mathjax, support for new inline delimiters ([#3925](https://github.com/pressbooks/pressbooks/issues/3925)) ([4f80bda](https://github.com/pressbooks/pressbooks/commit/4f80bdae222277ac278c51f97dcaf5dd2d02a3e9))


### Bug Fixes

* add aria-current to theme options page ([#3963](https://github.com/pressbooks/pressbooks/issues/3963)) ([1aba988](https://github.com/pressbooks/pressbooks/commit/1aba9888420cfcb4a0ecf4fba5a20ba17de432a4))
* add role presentation to license renderization image ([#3944](https://github.com/pressbooks/pressbooks/issues/3944)) ([32592ad](https://github.com/pressbooks/pressbooks/commit/32592adb1140603271337bc026602cad00da2bd2))
* contributor image removal ([#3958](https://github.com/pressbooks/pressbooks/issues/3958)) ([14b1bd7](https://github.com/pressbooks/pressbooks/commit/14b1bd78b043f7578ae913e9076c4fe10fcfe8bf))
* **deps:** update @pressbooks/reorderable-multiselect ([#3962](https://github.com/pressbooks/pressbooks/issues/3962)) ([040c080](https://github.com/pressbooks/pressbooks/commit/040c08095ea1121593cf6c7f181e8fa55f5ebab8))
* display math wrapper ([#3960](https://github.com/pressbooks/pressbooks/issues/3960)) ([8a1ef12](https://github.com/pressbooks/pressbooks/commit/8a1ef126b642ca7077777a43ba84377a447236fa))
* ensure that top nav items are accessible in mobile ([#3965](https://github.com/pressbooks/pressbooks/issues/3965)) ([795fa61](https://github.com/pressbooks/pressbooks/commit/795fa6107b81d164e633b1a12bebf9a310462693))
* properly label checkboxes on export page ([#3966](https://github.com/pressbooks/pressbooks/issues/3966)) ([571c82d](https://github.com/pressbooks/pressbooks/commit/571c82d92f00dd3f73aa1a4178a63d49c0854b79))
* remove link to Pressbooks.com from admin top nav-bar ([#3938](https://github.com/pressbooks/pressbooks/issues/3938)) ([41a2c30](https://github.com/pressbooks/pressbooks/commit/41a2c3097b766fcb609cc71fcf71cb03fa144182))
* resolve bug with taxonomy terms that use non-latin characters ([#3961](https://github.com/pressbooks/pressbooks/issues/3961)) ([d74b16d](https://github.com/pressbooks/pressbooks/commit/d74b16d446b654d1b0abf68059685469c6cd20b5))


### Chores

* add institution ([#3956](https://github.com/pressbooks/pressbooks/issues/3956)) ([01032ec](https://github.com/pressbooks/pressbooks/commit/01032ec7472f82b7e0bbc2b5609681352aaedc6e))
* bump @babel/runtime from 7.26.0 to 7.26.10 in the npm_and_yarn group ([#3957](https://github.com/pressbooks/pressbooks/issues/3957)) ([1c22eab](https://github.com/pressbooks/pressbooks/commit/1c22eaba00d223e8926dcb9ef2fdf900f49d56eb))
* bump @babel/runtime in the npm_and_yarn group ([1c22eab](https://github.com/pressbooks/pressbooks/commit/1c22eaba00d223e8926dcb9ef2fdf900f49d56eb))
* bump alpinejs from 3.14.8 to 3.14.9 in the npm-dependencies group ([#3952](https://github.com/pressbooks/pressbooks/issues/3952)) ([9aee8b2](https://github.com/pressbooks/pressbooks/commit/9aee8b227705153b71d17fe319f6a8323b305643))
* bump aws/aws-sdk-php from 3.340.4 to 3.342.2 in the composer-dependencies group ([#3941](https://github.com/pressbooks/pressbooks/issues/3941)) ([4438d7a](https://github.com/pressbooks/pressbooks/commit/4438d7a6f225ef8928fbef9570d7d62d6e948e11))
* bump aws/aws-sdk-php from 3.342.2 to 3.342.6 in the composer-dependencies group ([#3953](https://github.com/pressbooks/pressbooks/issues/3953)) ([22f9441](https://github.com/pressbooks/pressbooks/commit/22f9441d1d7837a822da5b0c2fb43a6fedfdd8d6))
* bump aws/aws-sdk-php from 3.342.6 to 3.342.11 in the composer-dependencies group ([#3967](https://github.com/pressbooks/pressbooks/issues/3967)) ([386586e](https://github.com/pressbooks/pressbooks/commit/386586ef151f27f59b67dcd1f44af15e13b6e1ff))
* bump aws/aws-sdk-php in the composer-dependencies group ([386586e](https://github.com/pressbooks/pressbooks/commit/386586ef151f27f59b67dcd1f44af15e13b6e1ff))
* bump aws/aws-sdk-php in the composer-dependencies group ([22f9441](https://github.com/pressbooks/pressbooks/commit/22f9441d1d7837a822da5b0c2fb43a6fedfdd8d6))
* bump aws/aws-sdk-php in the composer-dependencies group ([4438d7a](https://github.com/pressbooks/pressbooks/commit/4438d7a6f225ef8928fbef9570d7d62d6e948e11))
* bump instantsearch.js from 4.77.3 to 4.78.0 in the npm-dependencies group ([#3940](https://github.com/pressbooks/pressbooks/issues/3940)) ([c241080](https://github.com/pressbooks/pressbooks/commit/c241080e5547d7ff90ec88e8b26402705ba0ba45))
* bump instantsearch.js in the npm-dependencies group ([c241080](https://github.com/pressbooks/pressbooks/commit/c241080e5547d7ff90ec88e8b26402705ba0ba45))

## [6.22.4](https://github.com/pressbooks/pressbooks/compare/6.22.3...6.22.4) (2025-03-04)


### Bug Fixes

* improve conditional before removing H5P menu ([#3937](https://github.com/pressbooks/pressbooks/issues/3937)) ([83b2ab1](https://github.com/pressbooks/pressbooks/commit/83b2ab19b0c7b91477193cebc807521894824a7a))


### Chores

* bump php 8.2 tests matrix ([#3934](https://github.com/pressbooks/pressbooks/issues/3934)) ([ed86ad9](https://github.com/pressbooks/pressbooks/commit/ed86ad93172a245e8df2d58a64946b3b5bac5320))
* bump the composer-dependencies group with 3 updates ([#3933](https://github.com/pressbooks/pressbooks/issues/3933)) ([2fbd7d4](https://github.com/pressbooks/pressbooks/commit/2fbd7d4a75601df387a9a1a9751caed8ba80a39f))

## [6.22.3](https://github.com/pressbooks/pressbooks/compare/6.22.2...6.22.3) (2025-02-24)


### Bug Fixes

* remove cloning stats and h5p permissions for subscribers ([#3927](https://github.com/pressbooks/pressbooks/issues/3927)) ([116b3e0](https://github.com/pressbooks/pressbooks/commit/116b3e030bd0774c1854080985480a90c4624d6f))


### Chores

* bump aws/aws-sdk-php from 3.339.14 to 3.339.19 in the composer-dependencies group ([#3928](https://github.com/pressbooks/pressbooks/issues/3928)) ([1f54cbd](https://github.com/pressbooks/pressbooks/commit/1f54cbd646d01bf5721c1161827acbb218ce5988))
* bump aws/aws-sdk-php from 3.339.9 to 3.339.14 in the composer-dependencies group ([#3922](https://github.com/pressbooks/pressbooks/issues/3922)) ([44b3abd](https://github.com/pressbooks/pressbooks/commit/44b3abdb5188a46237a9b78090513bd2ba752c73))

## [6.22.2](https://github.com/pressbooks/pressbooks/compare/6.22.1...6.22.2) (2025-02-10)


### Chores

* bump instantsearch.js from 4.75.7 to 4.77.3 in the npm-dependencies group across 1 directory ([#3909](https://github.com/pressbooks/pressbooks/issues/3909)) ([21944a1](https://github.com/pressbooks/pressbooks/commit/21944a107c753ee2a369398f56d361bb2df03157))
* bump the composer-dependencies group across 1 directory with 3 updates ([#3916](https://github.com/pressbooks/pressbooks/issues/3916)) ([c52ff87](https://github.com/pressbooks/pressbooks/commit/c52ff87a6fac55e75baff566049a9e0c5f5983e1))
* bump yoast/phpunit-polyfills from 1.1.3 to 1.1.4 in the composer-dev-dependencies group ([#3915](https://github.com/pressbooks/pressbooks/issues/3915)) ([0da5456](https://github.com/pressbooks/pressbooks/commit/0da545687bb0b77eb12c29a0a0b4cd07a49dcf7c))

## [6.22.1](https://github.com/pressbooks/pressbooks/compare/6.22.0...6.22.1) (2025-01-15)


### Bug Fixes

* social media default values when empty ([#3893](https://github.com/pressbooks/pressbooks/issues/3893)) ([eea98e5](https://github.com/pressbooks/pressbooks/commit/eea98e5b93ca71a0bb0284822bb6cb8e8b86d910))


### Chores

* bump aws/aws-sdk-php from 3.336.8 to 3.336.13 in the composer-dependencies group ([#3896](https://github.com/pressbooks/pressbooks/issues/3896)) ([ff0fbf3](https://github.com/pressbooks/pressbooks/commit/ff0fbf332bd181fea9e368eec46f06f2aeea58d6))
* bump instantsearch.js from 4.75.6 to 4.75.7 in the npm-dependencies group ([#3895](https://github.com/pressbooks/pressbooks/issues/3895)) ([433bf68](https://github.com/pressbooks/pressbooks/commit/433bf68f417b034b1a9cc676b4372c830ccce286))
* bump nesbot/carbon from 2.72.5 to 2.72.6 in the composer group ([#3901](https://github.com/pressbooks/pressbooks/issues/3901)) ([387addf](https://github.com/pressbooks/pressbooks/commit/387addf2fb200f2f3b44c7ed8bca608b624bd384))
* bump yoast/phpunit-polyfills from 1.1.2 to 1.1.3 in the composer-dev-dependencies group ([#3897](https://github.com/pressbooks/pressbooks/issues/3897)) ([6300eac](https://github.com/pressbooks/pressbooks/commit/6300eac7deff20bd91bef2d600f8ce26207647e4))
* update matrix ([#3903](https://github.com/pressbooks/pressbooks/issues/3903)) ([971f1a0](https://github.com/pressbooks/pressbooks/commit/971f1a08da4ef13ac288c9a7decb22ff9ed319f7))

## [6.22.0](https://github.com/pressbooks/pressbooks/compare/6.21.3...6.22.0) (2025-01-06)


### Features

* add LinkedIn and Email sharing options, update Twitter, allow users to choose which to display ([#3870](https://github.com/pressbooks/pressbooks/issues/3870)) ([6a4cad9](https://github.com/pressbooks/pressbooks/commit/6a4cad9e4998d718fff60c7e96b9e1352a560cf6))
* remove LTI references ([#3871](https://github.com/pressbooks/pressbooks/issues/3871)) ([5b05891](https://github.com/pressbooks/pressbooks/commit/5b058914113d62f683a1374461216caed7dcd8bc))


### Bug Fixes

* add header_bg to customizer colors array ([#3867](https://github.com/pressbooks/pressbooks/issues/3867)) ([4d79dc0](https://github.com/pressbooks/pressbooks/commit/4d79dc06c6775f16a7d3ea75802183a6601eda64))
* allow webp uploads ([#3888](https://github.com/pressbooks/pressbooks/issues/3888)) ([36a3f33](https://github.com/pressbooks/pressbooks/commit/36a3f33a4624188a68a1ae48dae043063c7ba8de))
* drag and drop menu order ([#3872](https://github.com/pressbooks/pressbooks/issues/3872)) ([1f85f88](https://github.com/pressbooks/pressbooks/commit/1f85f880bc4bff63361833d5a7ef7ed77186ef7d))
* missing restore_current_blog call in getCoverThumbnail ([#3860](https://github.com/pressbooks/pressbooks/issues/3860)) ([20e81ca](https://github.com/pressbooks/pressbooks/commit/20e81ca29b9cae87b27e5ae6e9438b5ba9763b6c))
* remove unneeded filters & functions ([#3891](https://github.com/pressbooks/pressbooks/issues/3891)) ([cc19923](https://github.com/pressbooks/pressbooks/commit/cc19923f492443c8482e99278c62be29c9f2a838))
* replace Twitter references ([#3874](https://github.com/pressbooks/pressbooks/issues/3874)) ([47ce282](https://github.com/pressbooks/pressbooks/commit/47ce282744cbc763c5131cd6e1c16f8719761fc4))
* set media attributions to display by default ([#3875](https://github.com/pressbooks/pressbooks/issues/3875)) ([67c965b](https://github.com/pressbooks/pressbooks/commit/67c965b660b278a6ea1df6c4f1d686e351eecd43))


### Chores

* add requested institutions ([#3890](https://github.com/pressbooks/pressbooks/issues/3890)) ([69dbc49](https://github.com/pressbooks/pressbooks/commit/69dbc495142e8d500763b4bbc278e77c58169848))
* bump alpinejs from 3.14.3 to 3.14.5 in the npm-dependencies group ([#3859](https://github.com/pressbooks/pressbooks/issues/3859)) ([049b686](https://github.com/pressbooks/pressbooks/commit/049b68642686f28035c104dc9e38951885a1cbaf))
* bump alpinejs from 3.14.5 to 3.14.7 in the npm-dependencies group ([#3863](https://github.com/pressbooks/pressbooks/issues/3863)) ([e35e641](https://github.com/pressbooks/pressbooks/commit/e35e6410a9e2c8adccc61ffaf860b27c500e02ae))
* bump alpinejs from 3.14.7 to 3.14.8 in the npm-dependencies group ([#3885](https://github.com/pressbooks/pressbooks/issues/3885)) ([0a8413a](https://github.com/pressbooks/pressbooks/commit/0a8413ae6b68cab8f78a899896aef9dc4ef53987))
* bump aws/aws-sdk-php from 3.332.0 to 3.334.1 in the composer-dependencies group ([#3864](https://github.com/pressbooks/pressbooks/issues/3864)) ([4295475](https://github.com/pressbooks/pressbooks/commit/42954750631c34d42a2381a011ba1e8f82c1754c))
* bump aws/aws-sdk-php from 3.334.1 to 3.334.6 in the composer-dependencies group ([#3869](https://github.com/pressbooks/pressbooks/issues/3869)) ([8e512c4](https://github.com/pressbooks/pressbooks/commit/8e512c4ff0c969b919b37a9e83d20967b4c6bd12))
* bump aws/aws-sdk-php from 3.336.6 to 3.336.8 in the composer-dependencies group ([#3892](https://github.com/pressbooks/pressbooks/issues/3892)) ([dda069f](https://github.com/pressbooks/pressbooks/commit/dda069fa9d9d06792e6b23a8f5dd86722be3a85c))
* bump instantsearch.js from 4.75.5 to 4.75.6 in the npm-dependencies group ([#3868](https://github.com/pressbooks/pressbooks/issues/3868)) ([c946e69](https://github.com/pressbooks/pressbooks/commit/c946e697d55ca270970a3b8e9272a97ae48526d2))
* bump the composer-dependencies group across 1 directory with 2 updates ([#3884](https://github.com/pressbooks/pressbooks/issues/3884)) ([da0e487](https://github.com/pressbooks/pressbooks/commit/da0e487f9c70b60255ffd7ccbe7fcb40fc2dfac6))
* bump the composer-dependencies group with 3 updates ([#3858](https://github.com/pressbooks/pressbooks/issues/3858)) ([8c27049](https://github.com/pressbooks/pressbooks/commit/8c27049f867d2ffde73425e879f1b7992ec1a382))
* reuse objects to csv function ([#3862](https://github.com/pressbooks/pressbooks/issues/3862)) ([06f7615](https://github.com/pressbooks/pressbooks/commit/06f7615a20952832ebb0e71b210bef30ba57d2eb))

## [6.21.3](https://github.com/pressbooks/pressbooks/compare/6.21.2...6.21.3) (2024-11-25)


### Chores

* bump aws/aws-sdk-php from 3.328.0 to 3.330.0 in the composer-dependencies group ([#3854](https://github.com/pressbooks/pressbooks/issues/3854)) ([12a8db5](https://github.com/pressbooks/pressbooks/commit/12a8db583e1df74eb27d6c1877fbbb5edf79c3f1))
* bump test matrix wp version ([#3856](https://github.com/pressbooks/pressbooks/issues/3856)) ([6eaa8f1](https://github.com/pressbooks/pressbooks/commit/6eaa8f1e921419add29b8a0fceaff39837ce5c77))

## [6.21.2](https://github.com/pressbooks/pressbooks/compare/6.21.1...6.21.2) (2024-11-20)


### Chores

* bump codecov/codecov-action from 4 to 5 ([#3850](https://github.com/pressbooks/pressbooks/issues/3850)) ([62ba145](https://github.com/pressbooks/pressbooks/commit/62ba1456e5ceaa7355fc2321a85aa1e5586361a7))
* bump instantsearch.js from 4.75.4 to 4.75.5 in the npm-dependencies group ([#3849](https://github.com/pressbooks/pressbooks/issues/3849)) ([be49048](https://github.com/pressbooks/pressbooks/commit/be49048e41b781c99b8df3ba681c1f65317deee9))
* bump the composer-dependencies group with 4 updates ([#3848](https://github.com/pressbooks/pressbooks/issues/3848)) ([c85e43b](https://github.com/pressbooks/pressbooks/commit/c85e43b18b03def81009580032832409bf239f3c))

## [6.21.1](https://github.com/pressbooks/pressbooks/compare/6.21.0...6.21.1) (2024-11-14)


### Bug Fixes

* copyright attribution ([#3843](https://github.com/pressbooks/pressbooks/issues/3843)) ([c6f5059](https://github.com/pressbooks/pressbooks/commit/c6f505968d955707a312a55c5d1fcdd3560c5117))


### Chores

* remove duplicated prefix ([#3847](https://github.com/pressbooks/pressbooks/issues/3847)) ([51db88b](https://github.com/pressbooks/pressbooks/commit/51db88b2b5009b9bb53d692ef4c5ca4e12af7cb5))
* update heroicons webfont, includes entire solid and outline ([#3844](https://github.com/pressbooks/pressbooks/issues/3844)) ([bf5a8d1](https://github.com/pressbooks/pressbooks/commit/bf5a8d1f4e7f93ce7155fb99a42e8964e2415952))
* update heroicons webfont, includes entire solid and outline iconset ([bf5a8d1](https://github.com/pressbooks/pressbooks/commit/bf5a8d1f4e7f93ce7155fb99a42e8964e2415952))

## [6.21.0](https://github.com/pressbooks/pressbooks/compare/6.20.6...6.21.0) (2024-11-12)


### Features

* update post_modified fields when menu_order is updated ([7bf9b7d](https://github.com/pressbooks/pressbooks/commit/7bf9b7da1eccf8ceab099732946c896ba02b02ba))


### Bug Fixes

* fix: prevent subscriber role when not needed ([edd81b3](https://github.com/pressbooks/pressbooks/commit/edd81b39cebd3a4a6c365d2f2ae440e224947fa8))

### Chores

* chore: bump instantsearch.js from 4.75.3 to 4.75.4 in the npm-dependencies group ([f467c8f](https://github.com/pressbooks/pressbooks/commit/f467c8fa05f8c2dcada37d46067a915a84a3ff5f))

## [6.20.6](https://github.com/pressbooks/pressbooks/compare/6.20.5...6.20.6) (2024-11-07)


### Chores

* add requested Colorado institution ([#3822](https://github.com/pressbooks/pressbooks/issues/3822)) ([9c4a841](https://github.com/pressbooks/pressbooks/commit/9c4a841c488210a9dee72e608f108e65fca0425d))
* bump elliptic from 6.5.7 to 6.6.0 in the npm_and_yarn group ([#3821](https://github.com/pressbooks/pressbooks/issues/3821)) ([1638917](https://github.com/pressbooks/pressbooks/commit/163891728c102c4038abd2ea0292d81e1bcb339b))
* bump instantsearch.js from 4.75.1 to 4.75.3 in the npm-dependencies group ([#3820](https://github.com/pressbooks/pressbooks/issues/3820)) ([4f86310](https://github.com/pressbooks/pressbooks/commit/4f86310205b5ee1b6b9c45a600116960bcb49368))
* bump symfony/http-foundation from 5.4.42 to 5.4.46 in the composer group ([#3838](https://github.com/pressbooks/pressbooks/issues/3838)) ([013e292](https://github.com/pressbooks/pressbooks/commit/013e29289b6a037e9f521bd17ee5ad809d8352ca))
* bump symfony/process from 6.4.13 to 6.4.14 in the composer group ([#3836](https://github.com/pressbooks/pressbooks/issues/3836)) ([c14562a](https://github.com/pressbooks/pressbooks/commit/c14562a46b62ec07f7bbc52376c99a470622f8c5))
* bump the composer-dependencies group with 2 updates ([#3824](https://github.com/pressbooks/pressbooks/issues/3824)) ([20a1116](https://github.com/pressbooks/pressbooks/commit/20a1116025d175d44f7f86252cf879145ed8f1b9))
* revert previous commit, put Colorado Anschutz Medical at the bottom of the list ([#3823](https://github.com/pressbooks/pressbooks/issues/3823)) ([5c51166](https://github.com/pressbooks/pressbooks/commit/5c51166d969b6fad0ff5a83f4cf139e9e900fd00))
* update Lemoore College name  ([#3834](https://github.com/pressbooks/pressbooks/issues/3834)) ([4588fc5](https://github.com/pressbooks/pressbooks/commit/4588fc59282b065e5f58c7fd87cebf2a4affbdb6))

## [6.20.5](https://github.com/pressbooks/pressbooks/compare/6.20.4...6.20.5) (2024-10-23)


### Bug Fixes

* avoid null return in getSubmenuBySlug method ([#3812](https://github.com/pressbooks/pressbooks/issues/3812)) ([64ad8ab](https://github.com/pressbooks/pressbooks/commit/64ad8ab18afe181875543faeec3e48642a07ec94))

## [6.20.4](https://github.com/pressbooks/pressbooks/compare/6.20.3...6.20.4) (2024-10-16)


### Bug Fixes

* default book theme needs to be accessible ([#3809](https://github.com/pressbooks/pressbooks/issues/3809)) ([92ecf71](https://github.com/pressbooks/pressbooks/commit/92ecf71924cebe94c18d1fb7bd52c83643bf3dfb))

## [6.20.3](https://github.com/pressbooks/pressbooks/compare/6.20.2...6.20.3) (2024-09-26)


### Bug Fixes

* apply the_content filter before rendering footnotes for EPUB and PDF export routines ([#3788](https://github.com/pressbooks/pressbooks/issues/3788)) ([c65187c](https://github.com/pressbooks/pressbooks/commit/c65187c4747faa45c73b9d64965b16cc2db83fe5))
* avoid applying shortcodes during mathjax content analysis ([#3795](https://github.com/pressbooks/pressbooks/issues/3795)) ([05d9d21](https://github.com/pressbooks/pressbooks/commit/05d9d21024794bc3c56a3efa36332ce8142a08da))
* do not apply shortcodes during mathjax content analysis ([05d9d21](https://github.com/pressbooks/pressbooks/commit/05d9d21024794bc3c56a3efa36332ce8142a08da))
* render mathjax formulas within glossary terms & tablepress tables ([#3793](https://github.com/pressbooks/pressbooks/issues/3793)) ([6b1862e](https://github.com/pressbooks/pressbooks/commit/6b1862e7289f180811b054b5ebeae5d186a27c50))

## [6.20.2](https://github.com/pressbooks/pressbooks/compare/6.20.1...6.20.2) (2024-09-05)


### Bug Fixes

* hide full plugin menu in books for network managers ([#3777](https://github.com/pressbooks/pressbooks/issues/3777)) ([c5197a9](https://github.com/pressbooks/pressbooks/commit/c5197a98f2492e9c2c68a95e9a92d5ca69b6815e))
* Update Yukon University name ([#3776](https://github.com/pressbooks/pressbooks/issues/3776)) ([922cef4](https://github.com/pressbooks/pressbooks/commit/922cef4727bee662e102a568db05987ffd45addd))

## [6.20.1](https://github.com/pressbooks/pressbooks/compare/6.20.0...6.20.1) (2024-08-29)


### Bug Fixes

* append hooks in noon admin context ([#3773](https://github.com/pressbooks/pressbooks/issues/3773)) ([346a51b](https://github.com/pressbooks/pressbooks/commit/346a51b5fbfa8054983222f56f6b83169f03bd01))

## [6.20.0](https://github.com/pressbooks/pressbooks/compare/6.19.2...6.20.0) (2024-08-29)


### Features

* add default value for permissive_private_content option ([#3767](https://github.com/pressbooks/pressbooks/issues/3767)) ([96f7a26](https://github.com/pressbooks/pressbooks/commit/96f7a263a55a4a6df625ed847edbf93c5bf5fa6e))


### Bug Fixes

* remove user from pb_network_managers on super admin revoke or de… ([#3771](https://github.com/pressbooks/pressbooks/issues/3771)) ([d32705b](https://github.com/pressbooks/pressbooks/commit/d32705b6c901dfb1f10df01f8ec66844349d98f3))

## [6.19.2](https://github.com/pressbooks/pressbooks/compare/6.19.1...6.19.2) (2024-08-05)


### Bug Fixes

* user menu priority ([#3747](https://github.com/pressbooks/pressbooks/issues/3747)) ([9c5c1ea](https://github.com/pressbooks/pressbooks/commit/9c5c1ea42abfc413757d22f6c8cc346b46a8820c))

## [6.19.1](https://github.com/pressbooks/pressbooks/compare/6.19.0...6.19.1) (2024-07-18)


### Bug Fixes

* remove patterns submenu item ([#3742](https://github.com/pressbooks/pressbooks/issues/3742)) ([c4578c6](https://github.com/pressbooks/pressbooks/commit/c4578c6f1c0af0d97fc35e121ca25e58329f8fdd))

## [6.19.0](https://github.com/pressbooks/pressbooks/compare/6.18.2...6.19.0) (2024-06-20)


### Features

* add filters for pb_sanitize_webbook_content config and spec ([#3609](https://github.com/pressbooks/pressbooks/issues/3609)) ([4a968c7](https://github.com/pressbooks/pressbooks/commit/4a968c77fa786f97af5b9894cd8d47bd28b049cd))

## [6.18.2](https://github.com/pressbooks/pressbooks/compare/6.18.1...6.18.2) (2024-06-19)


### Bug Fixes

* dependabot and ci updates ([#3709](https://github.com/pressbooks/pressbooks/issues/3709)) ([7f05263](https://github.com/pressbooks/pressbooks/commit/7f05263b63f33a26a73501255916eca5d0390bcb))
* replace outdated link ([#3704](https://github.com/pressbooks/pressbooks/issues/3704)) ([3a3285f](https://github.com/pressbooks/pressbooks/commit/3a3285f118df55bb6ca3e1c232218ab632131dd8))
* typo ([#3699](https://github.com/pressbooks/pressbooks/issues/3699)) ([12fbe55](https://github.com/pressbooks/pressbooks/commit/12fbe558efd06cf1cb14fd347af222b4d101a246))
