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
