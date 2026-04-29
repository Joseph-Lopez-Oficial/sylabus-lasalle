## [2.0.1](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/compare/v2.0.0...v2.0.1) (2026-04-29)


### Bug Fixes

* **ui:** add labels, empty state in filters, truncate selects, datepicker, and fix select item overf ([55b2fce](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/commit/55b2fce091b9a50e895ed87933751222e4dd99dc)), closes [#35](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/issues/35)

# [2.0.0](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/compare/v1.1.0...v2.0.0) (2026-04-29)


### Bug Fixes

* **admin:** change catalog listing order from newest-first to oldest-first ([f8659c5](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/commit/f8659c507038bbe2c04e563eec37eda15921b5cc)), closes [#30](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/issues/30)
* **admin:** rename to Núcleo Problémico, fix filter label, add progressive cascade filters ([c44ead5](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/commit/c44ead5b72f9c8a5558cffd73777a17d306128cc)), closes [#20](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/issues/20)
* **db:** switch MySQL engine to InnoDB and fix professor nullable FK migration ([23e5acb](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/commit/23e5acb99af2701a9e69a5ce35077f7bb81b91d9)), closes [#18](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/issues/18)
* **grades:** remove redundant graded_at column from grades table ([83397b3](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/commit/83397b3b9578d6d643e2448a4ddb334346d588da)), closes [#28](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/issues/28)
* **sidebar:** assign distinct semantic icons to each admin sidebar item ([2b129ed](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/commit/2b129ed3a626ef93aa46b757b9b282a0b028e1e8)), closes [#33](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/issues/33)
* **ui:** fix select overflow, add labels and show code in select items ([a52fad1](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/commit/a52fad19ad90ddbe53dbe1ae89b505659916404a)), closes [#32](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/issues/32)
* **ui:** fix text overflow in admin tables and format dates in academic periods ([cd55874](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/commit/cd558744e9013fc41fcc236c898155671b3f075f)), closes [#29](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/issues/29)
* **ui:** translate full interface to Spanish, fix admin dashboard metrics, and remove unused links ([93e7c6e](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/commit/93e7c6ee9ea427d7bda1e235d0bf5c3d49d88cc1)), closes [#15](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/issues/15)


### Features

* **admin:** add statistics views for programmings, microcurricular outcomes, and academic spaces ([fff2574](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/commit/fff2574d94d083d483c2eb177337d383f7a58173)), closes [#25](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/issues/25)
* **competencies:** add unique code field per program to competencies ([dca7c7a](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/commit/dca7c7ac14478c939db947fb9db09fcaac43ec7e)), closes [#21](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/issues/21)
* **exports:** add Excel export to all statistics modules and fix professor report ([5b9217a](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/commit/5b9217a51039560c7b36d731618eafdc28340e7e)), closes [#34](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/issues/34)
* **grading:** bind evaluation criteria to outcome types and update grading system ([125632e](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/commit/125632ec000692ee2c1bf99290b30c5171a07667)), closes [#24](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/issues/24)
* **mesocurricular:** add hierarchical cascade filters to mesocurricular outcomes index ([8014f33](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/commit/8014f33ca88f6c91859c6ff0aefdf903e4a2415d)), closes [#31](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/issues/31)
* **mesocurricular:** add unique code field per competency to mesocurricular learning outcomes ([a4c92d1](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/commit/a4c92d14bbe4568699caf2d9b1dc600c20fe2eda)), closes [#27](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/issues/27)
* **microcurricular:** add unique code field per program to microcurricular learning outcomes ([a486711](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/commit/a486711628ada2c6044de577368405ce5f7928d1)), closes [#19](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/issues/19)
* **periods:** replace free-text period field with controlled academic periods catalog ([93e8301](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/commit/93e8301a6e8047de343d90dfddba7d3fb9d184b3)), closes [#23](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/issues/23)
* **seeders:** add activity types and translate all catalog seeders to Spanish ([02451e2](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/commit/02451e281723c6144090ff9eafa4aad241db2e03)), closes [#17](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/issues/17)
* **students:** add Excel import with template for students, and professor enrollment module ([53953c6](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/commit/53953c6f3f7cec23d39e25831bbbd29c201a7748)), closes [#22](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/issues/22)

# [1.1.0](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/compare/v1.0.0...v1.1.0) (2026-04-20)


### Bug Fixes

* **db:** extend name column to text in competencies and problematic_nuclei tables ([35eac32](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/commit/35eac32de4812e31417f1b5b43389d8d1430dcba))


### Features

* **admin:** implement academic structure CRUD pages for admin module ([f3d0799](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/commit/f3d0799c5886b4f8cdd0a16fc574f595c5ad3b57)), closes [#11](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/issues/11)
* **admin:** implement professors, students and programmings management pages ([c518c11](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/commit/c518c11d70f686aa5064b4106a364ba6b258c5bd)), closes [#12](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/issues/12)
* **frontend:** establish frontend base with types, layouts, and reusable components ([05a09d4](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/commit/05a09d4793c511f4d3a7d85cba5284d2604d3462)), closes [#10](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/issues/10)
* **professor:** implement grading import page and full statistics visualization ([93d440f](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/commit/93d440fb1f5697eab0ace87ddd46f58ff410eb93)), closes [#14](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/issues/14)
* **professor:** implement professor dashboard and full grading interface ([a1d37b7](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/commit/a1d37b719d084ed49708f3ed4a9930b91db9e682)), closes [#13](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/issues/13)

# 1.0.0 (2026-04-11)


### Bug Fixes

* **ci:** resolve test runner mismatch and husky pre-commit failure in release workflow ([2bc1918](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/commit/2bc1918dd14f92acdb053e9978c16ba07595021f))


### Features

* **admin:** implement academic structure controllers with validation and tests ([2ba1867](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/commit/2ba1867fd4f98e01ec5cc931a29a729a04e89808)), closes [#5](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/issues/5)
* **admin:** implement people and programmings management with bulk import ([1d8268e](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/commit/1d8268e8f22fae38a947a48de568c3a85a89d875)), closes [#6](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/issues/6)
* **auth:** add role-based middleware and post-login redirect by role ([38ad601](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/commit/38ad60141645e70f4f71f7798da8b9fb840205b2)), closes [#3](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/issues/3)
* **excel:** implement grading template download, grades import, and statistics report export ([4e614d8](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/commit/4e614d80f8cb71cc94939ae561374b63b97b0510)), closes [#9](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/issues/9)
* **grading:** implement professor grading module with completeness tracking ([7289ee4](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/commit/7289ee4c6ffbb6463c5978c269a007cfbafdc328)), closes [#7](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/issues/7)
* **models:** add 17 Eloquent models with relationships, casts, scopes, and factories ([94bd013](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/commit/94bd01372b397966a4c569e8644ddefa58c565a6)), closes [#4](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/issues/4)
* **seeders:** add initial catalog seeders and admin user setup ([9b211f5](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/commit/9b211f5a70859d768b54e787d6c1c5f8d277a786)), closes [#2](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/issues/2)
* **statistics:** implement grading statistics service and professor endpoint ([e148564](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/commit/e148564ecd2b085a8061c8d7f6434a700b890bd9)), closes [#8](https://github.com/Joseph-Lopez-Oficial/sylabus-lasalle/issues/8)
