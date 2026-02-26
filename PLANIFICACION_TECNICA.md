# 📋 Planificación Técnica del Proyecto

## Sistema de Gestión de Diseño Pedagógico Universitario

---

## 📌 Información General del Proyecto

| Campo                   | Detalle                                                 |
| ----------------------- | ------------------------------------------------------- |
| **Nombre del Proyecto** | Sistema de Gestión de Diseño Pedagógico Universitario   |
| **Versión**             | 1.0.0                                                   |
| **Fecha de Inicio**     | 2026-02-12                                              |
| **Estado**              | Planificación                                           |
| **Stack Tecnológico**   | Laravel 12 + Inertia.js v2 + React + TypeScript + MySQL |

---

## 🎯 Objetivo del Proyecto

Desarrollar una plataforma web centralizada que permita a las universidad De LaSalle gestionar su estructura académica jerárquica y realizar evaluaciones basadas en competencias, con énfasis en la calificación de resultados de aprendizaje microcurriculares mediante criterios de desempeño configurables.

---

## 👥 Usuarios del Sistema

### 1. Administradores

- **Permisos:** Acceso completo a todas las funcionalidades
- **Responsabilidades:**
    - CRUD completo de toda la estructura académica
    - Gestión de profesores, estudiantes y configuraciones
    - Visualización de todos los resultados y estadísticas
    - Configuración de criterios de evaluación y niveles de desempeño
    - Exportación de reportes globales

### 2. Profesores

- **Permisos:** Acceso limitado a sus espacios académicos asignados
- **Responsabilidades:**
    - Calificar estudiantes en resultados de aprendizaje microcurriculares
    - Importar calificaciones masivas mediante Excel
    - Visualizar estadísticas de sus espacios académicos
    - Exportar reportes de sus estudiantes

---

## 🏗️ Arquitectura del Sistema

### Stack Tecnológico Detallado

#### Backend

- **Framework:** Laravel 12.51.0
- **PHP:** 8.5.3
- **Base de Datos:** MySQL 8.0+
- **Autenticación:** Laravel Fortify 1.x
- **Testing:** Pest 4.x + PHPUnit 12.x
- **Code Style:** Laravel Pint 1.x

#### Frontend

- **Framework:** React 19 (con React Compiler)
- **TypeScript:** 5.x
- **Bundler:** Vite 7.3.1
- **Bridge:** Inertia.js v2
- **Routing Type-Safety:** Laravel Wayfinder 0.1.9
- **Estilos:** Tailwind CSS 4.x
- **Componentes:** shadcn/ui (React)

#### Herramientas de Desarrollo

- **NPM:** Para gestión de dependencias frontend

---

## 📊 Modelo de Datos

### Estructura Jerárquica Principal

```
Universidad
│
├── Faculty (Facultades)
│   └── Program (Programas Académicos)
│       └── ProblematicNucleus (Núcleos Problemáticos)
│           └── Competency (Competencias)
│               ├── MesocurricularLearningOutcome (Resultados Mesocurriculares)
│               │
│               └── AcademicSpace (Espacios Académicos/Cursos)
│                   ├── MicrocurricularLearningOutcome (Resultados Microcurriculares)
│                   │   ├── Type: Knowledge (Conocimiento)
│                   │   ├── Type: Skill (Habilidad)
│                   │   └── Type: Attitude (Actitud)
│                   │
│                   ├── Topic (Temas)
│                   │   └── Activity (Actividades)
│                   │       └── Product (Productos)
│                   │
│                   └── Programming (Programaciones)
│                       ├── Professor (Profesor asignado)
│                       ├── Modality (Virtual/Presencial)
│                       └── Enrollments (Estudiantes inscritos)
```

### Entidades del Sistema de Calificación

```
Student (Estudiante)
│
└── Enrollment (Inscripción a Programming)
    │
    └── Grade (Calificación)
        ├── MicrocurricularLearningOutcome (Qué se evalúa)
        ├── EvaluationCriterion (Criterio: Saber Conocer, Hacer, Ser, Transferir)
        ├── PerformanceLevel (Nivel: Insuficiente, Básico, Competente, Destacado)
        └── GradedBy (Profesor que calificó)
```

### Tablas de la Base de Datos

#### Estructura Académica (12 tablas)

1. `faculty` - Facultades
2. `program` - Programas académicos
3. `problematicnucleus` - Núcleos problemáticos
4. `competency` - Competencias
5. `mesocurricularlearningoutcome` - Resultados mesocurriculares
6. `academicspace` - Espacios académicos
7. `microcurricularlearningoutcome` - Resultados microcurriculares
8. `microcurricularlearningoutcometype` - Tipos (Knowledge, Skill, Attitude)
9. `topic` - Temas
10. `activity` - Actividades
11. `activitytype` - Tipos de actividad
12. `product` - Productos

#### Sistema de Profesores y Programación (3 tablas)

1. `professor` - Profesores
2. `modality` - Modalidades (Virtual/Presencial)
3. `programming` - Programaciones (asignaciones de profesor a espacio académico)

#### Sistema de Usuarios y Estudiantes (3 tablas)

1. `users` - Usuarios del sistema (admins y profesores con login)
2. `students` - Estudiantes
3. `enrollments` - Inscripciones de estudiantes a programaciones

#### Sistema de Evaluación (4 tablas)

1. `evaluation_criteria` - Criterios de evaluación (Saber Conocer, Hacer, Ser, Transferir)
2. `performance_levels` - Niveles de desempeño (Insuficiente, Básico, Competente, Destacado)
3. `grades` - Calificaciones
4. `import_logs` - Logs de importaciones Excel

**Total: 22 tablas**

---

## 🎨 Funcionalidades Principales

### Módulo de Autenticación

- ✅ Login para administradores y profesores
- ✅ Recuperación de contraseña
- ✅ Gestión de perfil de usuario

### Módulo de Administración (Solo Administradores)

#### 1. Gestión de Estructura Académica

- **CRUD de Facultades**
    - Crear, editar, eliminar, activar/desactivar facultades
    - Listado con búsqueda y filtros

- **CRUD de Programas**
    - Asociados a facultades
    - Vista jerárquica tipo árbol

- **CRUD de Núcleos Problemáticos**
    - Asociados a programas
    - Navegación jerárquica

- **CRUD de Competencias**
    - Asociadas a núcleos problemáticos
    - Vista de relaciones

- **CRUD de Resultados Mesocurriculares**
    - Asociados a competencias
    - Gestión de descripciones

- **CRUD de Espacios Académicos**
    - Asociados a competencias
    - Gestión de modalidad

- **CRUD de Resultados Microcurriculares**
    - Asociados a espacios académicos
    - Clasificación por tipo (Knowledge, Skill, Attitude)
    - Vinculación con resultados mesocurriculares

- **CRUD de Topics, Activities y Products**
    - Estructura completa de contenidos

#### 2. Gestión de Profesores

- CRUD de profesores
- Asignación de usuarios (login) a profesores
- Vista de espacios académicos asignados

#### 3. Gestión de Estudiantes

- CRUD de estudiantes
- Código estudiantil único
- Asociación a programas
- Gestión de estado (activo/inactivo)

#### 4. Gestión de Programaciones

- Crear programaciones (asignar profesor a espacio académico + período)
- Definir modalidad (virtual/presencial)
- Inscribir estudiantes a programaciones
- Importación masiva de inscripciones vía Excel

#### 5. Configuración del Sistema de Evaluación

- **CRUD de Criterios de Evaluación**
    - Configurar nombres (Saber Conocer, Hacer, Ser, Transferir)
    - Definir descripciones
    - Establecer orden de presentación

- **CRUD de Niveles de Desempeño**
    - Configurar nombres (Insuficiente, Básico, Competente, Destacado)
    - Definir valores numéricos (0.0 - 5.0)
    - Establecer orden de presentación

#### 6. Reportes y Estadísticas Globales

- Dashboard general con métricas clave
- Reportes por facultad/programa/espacio académico
- Exportación masiva de datos a Excel
- Visualización de resultados de todos los profesores

### Módulo de Profesor

#### 1. Dashboard

- Vista de espacios académicos asignados
- Resumen de estudiantes por espacio
- Estado de calificaciones (completadas/pendientes)

#### 2. Sistema de Calificación

**Flujo de Calificación:**

1. Seleccionar espacio académico asignado
2. Sistema carga automáticamente:
    - Resultados de aprendizaje microcurriculares (agrupados por tipo)
    - Lista de estudiantes inscritos
3. Calificar por resultado de aprendizaje:
    - Vista de tabla: Estudiantes (filas) × Criterios (columnas)
    - Seleccionar nivel de desempeño por cada criterio
    - Guardar por resultado individual o en conjunto
4. Confirmar y generar consolidado

**Opciones de Calificación:**

- ✋ **Manual:** Interfaz de tabla con selects/dropdowns
- 📊 **Importación Excel:**
    - Descargar plantilla predefinida
    - Cargar archivo con calificaciones
    - Validación automática
    - Reporte de errores si hay inconsistencias
    - Log de importación

#### 3. Estadísticas y Consolidados

**Vista por Estudiante Individual:**

- Calificación total por cada resultado de aprendizaje
- Promedio final del estudiante en el espacio académico
- Desglose por criterios

**Vista por Resultado de Aprendizaje:**

- Promedio de todos los estudiantes en ese resultado
- Distribución de niveles de desempeño (gráfica)
- Identificación de resultados con bajo rendimiento

**Vista por Criterio:**

- Promedio general por cada criterio (Saber Conocer, Hacer, Ser, Transferir)
- Comparación entre criterios
- Identificación de áreas de mejora

**Gráficas y Visualizaciones:**

- Gráficos de barras para distribución de niveles
- Gráficos de radar para perfil del estudiante
- Tablas comparativas

#### 4. Exportación de Reportes

- Exportar calificaciones completas a Excel
- Exportar consolidados por estudiante
- Exportar estadísticas generales del espacio académico
- Plantillas predefinidas con formato profesional

---

## 🗂️ Estructura de Directorios del Proyecto

```
sylabus-lasalle/
│
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   │   └── AuthenticatedSessionController.php
│   │   │   ├── Admin/
│   │   │   │   ├── FacultyController.php
│   │   │   │   ├── ProgramController.php
│   │   │   │   ├── ProblematicNucleusController.php
│   │   │   │   ├── CompetencyController.php
│   │   │   │   ├── MesocurricularLearningOutcomeController.php
│   │   │   │   ├── AcademicSpaceController.php
│   │   │   │   ├── MicrocurricularLearningOutcomeController.php
│   │   │   │   ├── TopicController.php
│   │   │   │   ├── ActivityController.php
│   │   │   │   ├── ProductController.php
│   │   │   │   ├── ProfessorController.php
│   │   │   │   ├── StudentController.php
│   │   │   │   ├── ProgrammingController.php
│   │   │   │   ├── EnrollmentController.php
│   │   │   │   ├── EvaluationCriterionController.php
│   │   │   │   ├── PerformanceLevelController.php
│   │   │   │   └── ReportController.php
│   │   │   └── Professor/
│   │   │       ├── DashboardController.php
│   │   │       ├── GradeController.php
│   │   │       ├── ImportController.php
│   │   │       ├── StatisticsController.php
│   │   │       └── ExportController.php
│   │   ├── Middleware/
│   │   │   ├── CheckAdmin.php
│   │   │   └── CheckProfessor.php
│   │   └── Requests/
│   │       ├── Faculty/
│   │       ├── Program/
│   │       ├── Grade/
│   │       └── Import/
│   ├── Models/
│   │   ├── Faculty.php
│   │   ├── Program.php
│   │   ├── ProblematicNucleus.php
│   │   ├── Competency.php
│   │   ├── MesocurricularLearningOutcome.php
│   │   ├── AcademicSpace.php
│   │   ├── MicrocurricularLearningOutcome.php
│   │   ├── MicrocurricularLearningOutcomeType.php
│   │   ├── Topic.php
│   │   ├── Activity.php
│   │   ├── ActivityType.php
│   │   ├── Product.php
│   │   ├── Professor.php
│   │   ├── Modality.php
│   │   ├── Programming.php
│   │   ├── User.php
│   │   ├── Student.php
│   │   ├── Enrollment.php
│   │   ├── EvaluationCriterion.php
│   │   ├── PerformanceLevel.php
│   │   ├── Grade.php
│   │   └── ImportLog.php
│   ├── Services/
│   │   ├── GradeService.php
│   │   ├── StatisticsService.php
│   │   ├── ExcelImportService.php
│   │   └── ExcelExportService.php
│   └── Exports/
│       ├── GradesExport.php
│       ├── StatisticsExport.php
│       └── TemplateExport.php
│
├── database/
│   ├── migrations/
│   │   ├── 2024_01_01_000000_create_faculty_table.php
│   │   ├── 2024_01_02_000000_create_program_table.php
│   │   ├── ... (una por cada tabla)
│   │   └── 2024_01_22_000000_create_import_logs_table.php
│   ├── factories/
│   │   ├── FacultyFactory.php
│   │   ├── StudentFactory.php
│   │   └── ... (factories para testing)
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── EvaluationCriteriaSeeder.php
│       ├── PerformanceLevelsSeeder.php
│       ├── ModalitySeeder.php
│       └── MicrocurricularLearningOutcomeTypeSeeder.php
│
├── resources/
│   ├── js/
│   │   ├── actions/ (Wayfinder generated)
│   │   ├── routes/ (Wayfinder generated)
│   │   ├── components/
│   │   │   ├── ui/ (shadcn/ui components)
│   │   │   ├── layout/
│   │   │   │   ├── AppLayout.tsx
│   │   │   │   ├── AdminLayout.tsx
│   │   │   │   ├── ProfessorLayout.tsx
│   │   │   │   └── Sidebar.tsx
│   │   │   ├── common/
│   │   │   │   ├── DataTable.tsx
│   │   │   │   ├── TreeView.tsx
│   │   │   │   ├── ConfirmDialog.tsx
│   │   │   │   └── LoadingSpinner.tsx
│   │   │   └── domain/
│   │   │       ├── FacultyForm.tsx
│   │   │       ├── GradeTable.tsx
│   │   │       ├── StatisticsCard.tsx
│   │   │       └── ...
│   │   ├── Pages/
│   │   │   ├── Auth/
│   │   │   │   ├── Login.tsx
│   │   │   │   └── ForgotPassword.tsx
│   │   │   ├── Admin/
│   │   │   │   ├── Dashboard.tsx
│   │   │   │   ├── Faculty/
│   │   │   │   │   ├── Index.tsx
│   │   │   │   │   ├── Create.tsx
│   │   │   │   │   └── Edit.tsx
│   │   │   │   ├── Program/
│   │   │   │   ├── ProblematicNucleus/
│   │   │   │   ├── Competency/
│   │   │   │   ├── AcademicSpace/
│   │   │   │   ├── Student/
│   │   │   │   ├── Professor/
│   │   │   │   ├── Programming/
│   │   │   │   ├── Configuration/
│   │   │   │   │   ├── EvaluationCriteria.tsx
│   │   │   │   │   └── PerformanceLevels.tsx
│   │   │   │   └── Reports/
│   │   │   └── Professor/
│   │   │       ├── Dashboard.tsx
│   │   │       ├── AcademicSpace/
│   │   │       │   └── Grade.tsx
│   │   │       ├── Statistics.tsx
│   │   │       └── Import.tsx
│   │   ├── types/
│   │   │   └── index.d.ts
│   │   ├── utils/
│   │   │   ├── calculations.ts
│   │   │   └── formatters.ts
│   │   ├── app.tsx
│   │   └── ssr.tsx
│   └── css/
│       └── app.css
│
├── routes/
│   ├── web.php
│   └── console.php
│
├── tests/
│   ├── Feature/
│   │   ├── Auth/
│   │   ├── Admin/
│   │   └── Professor/
│   └── Unit/
│       ├── Services/
│       └── Models/
│
├── public/
├── storage/
├── bootstrap/
├── config/
│
├── .env.example
├── .editorconfig
├── .gitignore
├── composer.json
├── package.json
├── phpunit.xml
├── pint.json
├── vite.config.ts
├── tsconfig.json
├── tailwind.config.js
├── CLAUDE.md
├── PLANIFICACION_TECNICA.md
└── README.md
```

---

## 📅 Cronograma de Desarrollo

### Fases del Proyecto

```
FASE 1: Configuración Inicial (2-3 días)
FASE 2: Backend - Estructura Base (5-7 días)
FASE 3: Backend - Sistema de Calificación (4-6 días)
FASE 4: Frontend - Configuración y Componentes Base (3-4 días)
FASE 5: Frontend - Módulo de Administración (8-10 días)
FASE 6: Frontend - Módulo de Profesor (6-8 días)
FASE 7: Integración y Testing (4-5 días)
FASE 8: Despliegue (2-3 días)
───────────────────────────────────────────────────────
TOTAL ESTIMADO: 34-46 días (~7-9 semanas)
```

---

### FASE 1: Configuración Inicial y Setup del Proyecto

**Duración Estimada:** 2-3 días

#### Tareas

1. **Configuración del Entorno de Desarrollo** (4 horas)
    - ✅ Verificar instalación de PHP 8.5.3, Composer, Node.js, NPM
    - ✅ Configurar MySQL local o Docker
    - ✅ Clonar/inicializar repositorio Git
    - ✅ Instalar dependencias: `composer install` + `npm install`

2. **Configuración de Base de Datos** (6 horas)
    - Crear base de datos MySQL
    - Configurar `.env` con credenciales de BD
    - Convertir schema SQL a migraciones Laravel
    - Ejecutar migraciones
    - Verificar integridad de relaciones

3. **Seeders Iniciales** (4 horas)
    - Crear seeder para `microcurricularlearningoutcometype` (Knowledge, Skill, Attitude)
    - Crear seeder para `modality` (Virtual, Face-to-Face)
    - Crear seeder para `evaluation_criteria` (Saber Conocer, Hacer, Ser, Transferir)
    - Crear seeder para `performance_levels` (Insuficiente, Básico, Competente, Destacado)
    - Crear seeder de usuario administrador inicial

4. **Configuración de Autenticación** (4 horas)
    - Configurar Laravel Fortify
    - Crear rutas de autenticación
    - Implementar middleware de roles (admin/professor)
    - Configurar redirecciones post-login

5. **Configuración de Herramientas** (2 horas)
    - Configurar Laravel Pint (code style)
    - Configurar Pest para testing
    - Configurar Wayfinder para generación de rutas TypeScript
    - Verificar integración de Vite con Laravel

**Entregables:**

- ✅ Entorno de desarrollo funcional
- ✅ Base de datos creada y migrada
- ✅ Datos iniciales cargados
- ✅ Autenticación básica funcionando
- ✅ Herramientas de desarrollo configuradas

---

### FASE 2: Backend - Estructura Base

**Duración Estimada:** 5-7 días

#### Tareas

1. **Crear Modelos Eloquent** (8 horas)
    - Crear 22 modelos con relaciones:
        - Faculty, Program, ProblematicNucleus, Competency
        - MesocurricularLearningOutcome, AcademicSpace
        - MicrocurricularLearningOutcome, MicrocurricularLearningOutcomeType
        - Topic, Activity, ActivityType, Product
        - Professor, Modality, Programming
        - User, Student, Enrollment
        - EvaluationCriterion, PerformanceLevel, Grade, ImportLog
    - Definir relaciones (hasMany, belongsTo, belongsToMany)
    - Configurar casts (dates, JSON, enums)
    - Agregar scopes útiles (active, byStatus)

2. **Factories para Testing** (4 horas)
    - Crear factories para modelos principales
    - Definir estados (active, inactive)
    - Establecer relaciones entre factories

3. **Controladores de Administración - Estructura Académica** (12 horas)
    - `FacultyController` (CRUD completo)
    - `ProgramController` (CRUD + relación con faculty)
    - `ProblematicNucleusController` (CRUD + relación con program)
    - `CompetencyController` (CRUD + relación con nucleus)
    - `MesocurricularLearningOutcomeController` (CRUD + relación con competency)
    - `AcademicSpaceController` (CRUD + relación con competency)
    - `MicrocurricularLearningOutcomeController` (CRUD + tipo + vinculación mesocurricular)
    - `TopicController`, `ActivityController`, `ProductController`
    - Implementar paginación, búsqueda, filtros

4. **Controladores de Administración - Usuarios** (6 horas)
    - `ProfessorController` (CRUD + asignación de usuario)
    - `StudentController` (CRUD + gestión de código estudiantil)
    - `ProgrammingController` (CRUD + asignación de profesor/modalidad)
    - `EnrollmentController` (Inscripciones + importación Excel)

5. **Controladores de Configuración** (4 horas)
    - `EvaluationCriterionController` (CRUD + ordenamiento)
    - `PerformanceLevelController` (CRUD + valores numéricos + ordenamiento)

6. **Form Requests de Validación** (6 horas)
    - Crear Form Requests para cada controlador
    - Definir reglas de validación
    - Mensajes de error personalizados
    - Validación de relaciones existentes

7. **Rutas de Administración** (3 horas)
    - Agrupar rutas por middleware (auth, admin)
    - Definir nombres de rutas consistentes
    - Configurar resource routes
    - Documentar rutas en comentarios

**Entregables:**

- ✅ 22 modelos Eloquent con relaciones completas
- ✅ Factories para testing
- ✅ Controladores CRUD para administración
- ✅ Form Requests de validación
- ✅ Rutas organizadas y documentadas

---

### FASE 3: Backend - Sistema de Calificación

**Duración Estimada:** 4-6 días

#### Tareas

1. **Controladores de Profesor** (8 horas)
    - `DashboardController` (listado de espacios académicos asignados)
    - `GradeController`:
        - Obtener estudiantes de un espacio académico
        - Obtener resultados microcurriculares agrupados por tipo
        - Guardar calificaciones (una o múltiples)
        - Validar que todas las combinaciones estén calificadas

2. **Servicio de Calificación** (6 horas)
    - `GradeService`:
        - Lógica de negocio para guardar/actualizar calificaciones
        - Validación de duplicados
        - Cálculo de calificaciones totales por resultado
        - Cálculo de promedios por estudiante
        - Verificación de calificaciones completas

3. **Servicio de Estadísticas** (8 horas)
    - `StatisticsService`:
        - Calcular promedio por estudiante
        - Calcular promedio por resultado de aprendizaje
        - Calcular promedio por criterio
        - Calcular distribución de niveles de desempeño
        - Generar datos para gráficas
        - Identificar resultados/estudiantes con bajo rendimiento

4. **Servicio de Importación Excel** (10 horas)
    - `ExcelImportService`:
        - Validar estructura del archivo
        - Mapear columnas a modelos
        - Validar datos (estudiantes existen, criterios válidos, niveles válidos)
        - Importar en transacciones
        - Generar reporte de errores
        - Crear log de importación
    - Crear plantilla Excel descargable

5. **Servicio de Exportación Excel** (8 horas)
    - `ExcelExportService`:
        - Exportar calificaciones detalladas
        - Exportar consolidados por estudiante
        - Exportar estadísticas generales
        - Aplicar formato profesional (colores, bordes, encabezados)
        - Incluir gráficas en Excel (opcional)

6. **Rutas de Profesor** (2 horas)
    - Agrupar rutas por middleware (auth, professor)
    - Rutas de calificación
    - Rutas de importación/exportación
    - Rutas de estadísticas

7. **Testing del Sistema de Calificación** (6 horas)
    - Tests de guardado de calificaciones
    - Tests de cálculos estadísticos
    - Tests de importación Excel (casos exitosos y errores)
    - Tests de exportación Excel
    - Tests de permisos (profesores solo ven sus espacios)

**Entregables:**

- ✅ Controladores de profesor completos
- ✅ Servicios de calificación, estadísticas, importación y exportación
- ✅ Sistema de importación/exportación Excel funcional
- ✅ Tests unitarios y de integración
- ✅ Documentación de APIs

---

### FASE 4: Frontend - Configuración y Componentes Base

**Duración Estimada:** 3-4 días

#### Tareas

1. **Configuración de shadcn/ui** (3 horas)
    - Instalar y configurar shadcn/ui
    - Instalar componentes base:
        - Button, Input, Label, Select, Textarea
        - Table, Dialog, Alert, Card
        - Dropdown, Checkbox, Radio
        - Tabs, Accordion, Sheet
        - Form (react-hook-form integration)
    - Configurar tema de colores
    - Configurar variantes

2. **Layouts del Sistema** (6 horas)
    - `AppLayout.tsx` (layout base con header/footer)
    - `AdminLayout.tsx` (sidebar con menú de admin + breadcrumbs)
    - `ProfessorLayout.tsx` (sidebar con menú de profesor + breadcrumbs)
    - `Sidebar.tsx` (navegación lateral con iconos)
    - Responsive design (mobile, tablet, desktop)

3. **Componentes Comunes Reutilizables** (10 horas)
    - `DataTable.tsx`:
        - Tabla con paginación
        - Ordenamiento por columnas
        - Búsqueda
        - Filtros
        - Acciones por fila (editar, eliminar)
    - `TreeView.tsx`:
        - Vista jerárquica expandible/colapsable
        - Navegación por niveles (Faculty → Program → Nucleus → Competency)
        - Lazy loading de niveles
    - `ConfirmDialog.tsx` (diálogo de confirmación de acciones)
    - `LoadingSpinner.tsx` (indicador de carga)
    - `StatusBadge.tsx` (badge de estado active/inactive)
    - `PageHeader.tsx` (encabezado de página con título y acciones)

4. **Utilidades y Helpers** (4 horas)
    - `calculations.ts`:
        - Funciones de cálculo de promedios
        - Formateo de calificaciones
    - `formatters.ts`:
        - Formateo de fechas
        - Formateo de números
        - Formateo de nombres
    - `types/index.d.ts`:
        - Definir tipos TypeScript para todos los modelos
        - Tipos de respuesta de APIs
        - Props de componentes

5. **Páginas de Autenticación** (4 horas)
    - `Login.tsx`:
        - Formulario de login
        - Validación de campos
        - Manejo de errores
        - Integración con Laravel Fortify
    - `ForgotPassword.tsx`:
        - Formulario de recuperación de contraseña
        - Confirmación de email enviado

**Entregables:**

- ✅ shadcn/ui configurado con componentes instalados
- ✅ Layouts responsive completos
- ✅ Componentes reutilizables (DataTable, TreeView, etc.)
- ✅ Utilidades y tipos TypeScript
- ✅ Páginas de autenticación funcionales

---

### FASE 5: Frontend - Módulo de Administración

**Duración Estimada:** 8-10 días

#### Tareas

1. **Dashboard de Administración** (4 horas)
    - Métricas generales (total facultades, programas, estudiantes, profesores)
    - Gráficas de resumen
    - Accesos rápidos a módulos
    - Actividad reciente

2. **CRUD de Estructura Académica - Facultades y Programas** (8 horas)
    - `Faculty/Index.tsx`:
        - Lista de facultades con DataTable
        - Búsqueda y filtros
        - Acciones (crear, editar, eliminar, cambiar estado)
    - `Faculty/Create.tsx` y `Faculty/Edit.tsx`:
        - Formulario de facultad
        - Validación en frontend
    - `Program/Index.tsx`:
        - Lista de programas filtrada por facultad
        - Vista jerárquica opcional
    - `Program/Create.tsx` y `Program/Edit.tsx`:
        - Formulario de programa con selector de facultad

3. **CRUD de Núcleos Problemáticos y Competencias** (8 horas)
    - `ProblematicNucleus/Index.tsx`:
        - Vista jerárquica: Facultad → Programa → Núcleos
        - TreeView para navegación
    - `ProblematicNucleus/Create.tsx` y `Edit.tsx`
    - `Competency/Index.tsx`:
        - Vista jerárquica completa
        - Filtrado por programa/núcleo
    - `Competency/Create.tsx` y `Edit.tsx`

4. **CRUD de Resultados de Aprendizaje** (10 horas)
    - `MesocurricularLearningOutcome/Index.tsx`:
        - Lista filtrada por competencia
        - Vista de relaciones
    - `MesocurricularLearningOutcome/Create.tsx` y `Edit.tsx`
    - `MicrocurricularLearningOutcome/Index.tsx`:
        - Lista filtrada por espacio académico
        - Agrupación por tipo (Knowledge, Skill, Attitude)
        - Vista de vinculación con mesocurriculares
    - `MicrocurricularLearningOutcome/Create.tsx` y `Edit.tsx`:
        - Selector de tipo
        - Selector de resultado mesocurricular vinculado
        - Preview de jerarquía

5. **CRUD de Espacios Académicos** (8 hours)
    - `AcademicSpace/Index.tsx`:
        - Lista con filtros por facultad/programa/competencia
        - Vista de programaciones asociadas
    - `AcademicSpace/Create.tsx` y `Edit.tsx`:
        - Formulario con selector de competencia
        - Selector de modalidad
    - `AcademicSpace/Show.tsx`:
        - Vista detallada con tabs:
            - Información general
            - Resultados microcurriculares
            - Topics y actividades
            - Programaciones

6. **CRUD de Topics, Activities y Products** (6 horas)
    - Interfaces CRUD estándar para cada entidad
    - Integración en vista de AcademicSpace

7. **Gestión de Profesores y Estudiantes** (8 horas)
    - `Professor/Index.tsx`:
        - Lista de profesores con DataTable
        - Indicador de usuario asignado
    - `Professor/Create.tsx` y `Edit.tsx`:
        - Formulario de profesor
        - Opción de crear usuario de login
        - Gestión de credenciales
    - `Student/Index.tsx`:
        - Lista de estudiantes con búsqueda por código/nombre
        - Filtro por programa
    - `Student/Create.tsx` y `Edit.tsx`:
        - Formulario con código estudiantil
        - Selector de programa
    - `Student/Import.tsx`:
        - Importación masiva vía Excel

8. **Gestión de Programaciones e Inscripciones** (8 horas)
    - `Programming/Index.tsx`:
        - Lista de programaciones
        - Filtros por profesor, espacio académico, período
    - `Programming/Create.tsx` y `Edit.tsx`:
        - Selector de espacio académico
        - Selector de profesor
        - Selector de modalidad
        - Campo de período
    - `Programming/Enrollments.tsx`:
        - Inscribir estudiantes a programación
        - Vista de estudiantes inscritos
        - Importación masiva de inscripciones

9. **Configuración del Sistema** (6 horas)
    - `Configuration/EvaluationCriteria.tsx`:
        - Lista de criterios
        - Ordenamiento drag & drop
        - CRUD inline
    - `Configuration/PerformanceLevels.tsx`:
        - Lista de niveles
        - Configuración de valores numéricos
        - Ordenamiento drag & drop

10. **Reportes de Administración** (6 horas)
    - `Reports/Index.tsx`:
        - Selector de tipo de reporte
        - Filtros (facultad, programa, período)
        - Preview de datos
        - Botón de exportación a Excel
    - Visualización de estadísticas globales
    - Gráficas con Chart.js o Recharts

**Entregables:**

- ✅ Dashboard de administración completo
- ✅ CRUD completo de toda la estructura académica
- ✅ Gestión de profesores y estudiantes
- ✅ Gestión de programaciones e inscripciones
- ✅ Configuración del sistema de evaluación
- ✅ Sistema de reportes

---

### FASE 6: Frontend - Módulo de Profesor

**Duración Estimada:** 6-8 días

#### Tareas

1. **Dashboard de Profesor** (4 horas)
    - `Dashboard.tsx`:
        - Lista de espacios académicos asignados
        - Cards por espacio con:
            - Nombre del espacio
            - Período y modalidad
            - Número de estudiantes
            - Estado de calificaciones (% completado)
            - Botón de acceso rápido a calificar
        - Filtro por período

2. **Interface de Calificación** (16 horas)
    - `AcademicSpace/Grade.tsx`:
        - **Estructura Principal:**
            - Header con info del espacio académico
            - Tabs para agrupar por tipo de resultado:
                - Tab "Conocimiento" (Knowledge)
                - Tab "Habilidad" (Skill)
                - Tab "Actitud" (Attitude)
            - Accordion por resultado de aprendizaje dentro de cada tab

        - **Tabla de Calificación por Resultado:**
            - Filas: Estudiantes
            - Columnas: Criterios de evaluación
            - Celdas: Select con niveles de desempeño
            - Indicador visual de celda sin calificar
            - Auto-save al cambiar valor (con debounce)
            - Botón de "Guardar Resultado" al final

        - **Funcionalidades:**
            - Navegación entre resultados (Anterior/Siguiente)
            - Indicador de progreso (X de Y resultados calificados)
            - Validación: no permitir guardar si faltan celdas
            - Confirmación al salir con cambios sin guardar
            - Loading states durante guardado

        - **Botón Final:**
            - "Confirmar y Generar Consolidado"
            - Validar que todos los resultados estén calificados
            - Generar consolidado y redirigir a estadísticas

3. **Sistema de Importación Excel** (8 hours)
    - `AcademicSpace/Import.tsx`:
        - **Descarga de Plantilla:**
            - Botón "Descargar Plantilla Excel"
            - Generar plantilla con:
                - Hoja 1: Instrucciones
                - Hoja 2: Formato de calificación con dropdowns
                - Hoja 3: Catálogos (criterios, niveles)

        - **Carga de Archivo:**
            - Drag & drop zone
            - Selector de archivo
            - Preview de archivo seleccionado

        - **Procesamiento:**
            - Loading state durante importación
            - Barra de progreso
            - Reporte de resultados:
                - Filas procesadas exitosamente
                - Filas con errores (detalle por fila)
                - Opción de descargar log de errores

        - **Post-Importación:**
            - Botón para revisar calificaciones importadas
            - Opción de re-intentar con archivo corregido

4. **Estadísticas y Consolidados** (12 horas)
    - `AcademicSpace/Statistics.tsx`:
        - **Sección 1: Estadísticas por Estudiante**
            - Selector de estudiante (dropdown o búsqueda)
            - Card con información del estudiante
            - Tabla de resultados:
                - Columnas: Resultado de Aprendizaje, Tipo, Calificación Total, Desglose por Criterio
            - Promedio final del estudiante
            - Gráfica de radar con perfil del estudiante
            - Botón "Exportar Reporte del Estudiante"

        - **Sección 2: Estadísticas por Resultado de Aprendizaje**
            - Selector de resultado
            - Card con descripción del resultado
            - Métricas:
                - Promedio general
                - Calificación más alta/baja
                - Desviación estándar
            - Gráfica de barras: Distribución de niveles de desempeño
            - Tabla de estudiantes ordenada por calificación
            - Identificación de estudiantes con bajo rendimiento
            - Botón "Exportar Reporte del Resultado"

        - **Sección 3: Estadísticas por Criterio**
            - Vista comparativa de los 4 criterios
            - Gráfica de barras comparativa
            - Tabla con promedio por criterio
            - Identificación de criterios débiles
            - Recomendaciones pedagógicas (opcional)

        - **Sección 4: Vista Global del Espacio Académico**
            - Resumen general con métricas clave
            - Distribución general de niveles
            - Top 5 estudiantes
            - Estudiantes que requieren apoyo
            - Heatmap de calificaciones (estudiantes × resultados)
            - Botón "Exportar Reporte Completo"

5. **Sistema de Exportación** (6 horas)
    - Integración con backend para exportar a Excel
    - Opciones de exportación:
        - Calificaciones detalladas (todas las celdas)
        - Consolidado por estudiante
        - Estadísticas generales
        - Reporte personalizado (seleccionar secciones)
    - Loading state durante generación
    - Download automático del archivo
    - Notificación de éxito

6. **Componentes Específicos del Módulo** (6 horas)
    - `GradeTable.tsx`:
        - Componente reutilizable de tabla de calificación
        - Manejo de estado local
        - Validaciones
    - `StatisticsCard.tsx`:
        - Card reutilizable para métricas
        - Variantes de diseño
    - `StudentSelector.tsx`:
        - Selector avanzado con búsqueda
        - Avatar del estudiante
    - `ProgressIndicator.tsx`:
        - Indicador visual de progreso de calificación
        - Porcentaje + barra + mensaje

**Entregables:**

- ✅ Dashboard de profesor funcional
- ✅ Interface completa de calificación
- ✅ Sistema de importación Excel
- ✅ Módulo de estadísticas y consolidados
- ✅ Sistema de exportación de reportes
- ✅ Componentes específicos reutilizables

---

### FASE 7: Integración y Testing

**Duración Estimada:** 4-5 días

#### Tareas

1. **Testing Backend** (12 horas)
    - **Tests Unitarios:**
        - Modelos: relaciones, scopes, casts
        - Servicios: cálculos, lógica de negocio
        - Helpers y utilidades

    - **Tests de Integración (Feature Tests):**
        - Autenticación: login, logout, recuperación de contraseña
        - CRUD de cada entidad (admin)
        - Sistema de calificación completo (profesor)
        - Importación Excel (casos exitosos y errores)
        - Exportación Excel
        - Cálculos estadísticos
        - Permisos y middleware

    - **Cobertura Objetivo:** 80%+

2. **Testing Frontend** (8 horas)
    - **Tests de Componentes:**
        - Componentes comunes (DataTable, TreeView, etc.)
        - Componentes de dominio (GradeTable, etc.)

    - **Tests de Integración:**
        - Flujos completos de administración
        - Flujo completo de calificación
        - Importación/exportación

    - **Herramientas:**
        - Vitest para unit tests
        - React Testing Library para componentes
        - Cypress o Playwright para E2E (opcional)

3. **Corrección de Bugs** (8 horas)
    - Revisar y corregir bugs encontrados en testing
    - Validar edge cases
    - Mejorar mensajes de error
    - Optimizar consultas lentas

4. **Optimización de Performance** (6 horas)
    - **Backend:**
        - Eager loading de relaciones (evitar N+1)
        - Indexación de columnas frecuentes
        - Cacheo de consultas repetitivas
        - Optimización de queries complejas

    - **Frontend:**
        - Lazy loading de componentes pesados
        - Memoization con React.memo
        - Optimización de re-renders
        - Code splitting por rutas
        - Optimización de imágenes

5. **Revisión de UX/UI** (4 horas)
    - Consistencia visual entre páginas
    - Mensajes de feedback claros
    - Loading states apropiados
    - Responsive design en todos los tamaños
    - Accesibilidad (a11y) básica

6. **Documentación** (4 horas)
    - Actualizar README.md con:
        - Instrucciones de instalación
        - Configuración del entorno
        - Comandos disponibles
        - Estructura del proyecto
    - Documentar APIs principales
    - Comentar código complejo
    - Crear guía de usuario básica (opcional)

**Entregables:**

- ✅ Tests completos (backend + frontend)
- ✅ Bugs corregidos
- ✅ Performance optimizado
- ✅ UX/UI consistente
- ✅ Documentación actualizada

---

### FASE 8: Despliegue en Servidor Apache de la Universidad

**Duración Estimada:** 2-3 días

#### Contexto del Servidor

- **Servidor:** Apache + PHP + MySQL (servidor de la universidad)
- **Acceso:** Solo vía FTP (no se tiene acceso SSH/terminal)
- **Limitación:** No se pueden ejecutar comandos Artisan en el servidor
- **Flujo:** Desarrollo local → Build → Transferencia FTP → Configuración manual de BD

#### Tareas

1. **Preparación Local Completa** (6 horas)
    - **Configurar entorno de producción local:**
        - Crear `.env.production` con credenciales del servidor universitario
        - Configurar APP_ENV=production
        - Configurar APP_DEBUG=false
        - Configurar URL del dominio universitario
        - Configurar credenciales de BD MySQL del servidor
    - **Optimizar Laravel:**
        - Ejecutar: `composer install --optimize-autoloader --no-dev`
        - Ejecutar: `php artisan config:cache`
        - Ejecutar: `php artisan route:cache`
        - Ejecutar: `php artisan view:cache`
        - Ejecutar: `php artisan event:cache`
    - **Build del Frontend:**
        - Ejecutar: `npm run build`
        - Verificar que los assets se generen en `public/build`
        - Verificar que el manifest.json se genere correctamente

2. **Preparación de Base de Datos** (4 horas)
    - **Generar SQL de estructura completa:**
        - Exportar todas las migraciones como un solo archivo SQL
        - Incluir todas las 22 tablas con sus relaciones
        - Incluir índices y foreign keys
    - **Generar SQL de datos iniciales:**
        - Exportar datos de seeders como INSERT statements:
            - Tipos de resultados microcurriculares (Knowledge, Skill, Attitude)
            - Modalidades (Virtual, Presencial)
            - Criterios de evaluación (Saber Conocer, Hacer, Ser, Transferir)
            - Niveles de desempeño (Insuficiente, Básico, Competente, Destacado)
            - Usuario administrador inicial
    - **Crear script de importación manual:**
        - Archivo SQL completo que se pueda ejecutar en phpMyAdmin
        - Orden correcto de ejecución (tablas sin FK primero)
        - Comentarios claros para cada sección

3. **Preparar Estructura de Archivos para FTP** (3 horas)
    - **Crear carpeta de despliegue local:**
        - Copiar todo el proyecto Laravel a carpeta limpia
        - Eliminar archivos no necesarios:
            - `node_modules/` (completo)
            - `tests/`
            - `.git/`
            - `README.md` (opcional)
            - Archivos de desarrollo (.editorconfig, etc.)
    - **Ajustar permisos requeridos:**
        - Identificar carpetas que necesitan permisos de escritura:
            - `storage/` (logs, cache, sessions)
            - `bootstrap/cache/`
        - Documentar permisos necesarios (777 para storage en Apache)
    - **Configurar .htaccess para Apache:**
        - Verificar `.htaccess` en `/public`
        - Asegurar redirección correcta a index.php
        - Configurar reglas de rewrite para Laravel

4. **Conexión y Configuración de Base de Datos en Servidor** (3 horas)
    - **Acceder a phpMyAdmin del servidor universitario**
    - **Crear base de datos:**
        - Nombre de BD según convención universitaria
        - Charset: utf8mb4_unicode_ci
    - **Importar estructura:**
        - Ejecutar SQL de estructura de tablas
        - Verificar que todas las 22 tablas se crearon
        - Verificar foreign keys y relaciones
    - **Importar datos iniciales:**
        - Ejecutar SQL de seeders
        - Verificar que los datos se insertaron correctamente
    - **Anotar credenciales:**
        - Host, usuario, contraseña, nombre de BD
        - Actualizar `.env` local con estas credenciales

5. **Transferencia de Archivos vía FTP** (4 horas)
    - **Conectar vía FTP al servidor:**
        - Usar FileZilla o WinSCP
        - Obtener credenciales FTP de administrador universitario
    - **Subir archivos de Laravel:**
        - Subir todo el contenido del proyecto preparado
        - Estructura típica: `/public_html/nombre-proyecto/`
        - Verificar integridad de archivos subidos
        - Tiempo estimado: 2-3 horas (dependiendo de conexión)
    - **Configurar punto de entrada:**
        - Identificar si el dominio apunta a `/public_html/`
        - Dos opciones:
            - **Opción A:** Mover contenido de `/public/` a raíz y ajustar rutas
            - **Opción B:** Configurar subdirectorio y ajustar rutas en index.php
    - **Ajustar permisos en servidor:**
        - `storage/` → 777 (lectura/escritura para logs, cache, sessions)
        - `bootstrap/cache/` → 777

6. **Configuración Final y Ajustes** (3 horas)
    - **Verificar archivo `.env` en servidor:**
        - Asegurar que `.env` tiene credenciales correctas de BD
        - Verificar APP_URL apunta al dominio correcto
        - Verificar APP_KEY está generado
    - **Ajustar rutas de archivos si es necesario:**
        - Si `/public` no es la raíz web, ajustar `index.php`
        - Actualizar referencias a `storage` y `bootstrap/cache`
    - **Probar conectividad de BD:**
        - Crear archivo `test-db.php` temporal para verificar conexión
        - Eliminar después de verificar

7. **Pruebas en Servidor de Producción** (4 horas)
    - **Verificar que el sitio carga:**
        - Acceder al dominio universitario
        - Verificar que no hay errores 500
        - Verificar que assets (CSS/JS) cargan correctamente
    - **Probar autenticación:**
        - Login con usuario administrador inicial
        - Verificar redirecciones
    - **Probar funcionalidades críticas:**
        - Crear una facultad de prueba
        - Crear un programa de prueba
        - Verificar que la BD se actualiza
        - Probar formularios
        - Probar subida de Excel (si aplica)
    - **Probar desde diferentes navegadores:**
        - Chrome, Firefox, Edge
        - Verificar responsive en mobile
    - **Revisar logs de errores:**
        - Verificar `storage/logs/laravel.log`
        - Corregir errores si aparecen

8. **Documentación del Proceso de Despliegue** (2 horas)
    - Crear guía paso a paso para futuros despliegues
    - Documentar credenciales (de forma segura)
    - Documentar estructura de carpetas en servidor
    - Documentar comandos ejecutados
    - Crear checklist de despliegue

**Entregables:**

- ✅ Aplicación funcionando en dominio universitario
- ✅ Base de datos MySQL configurada manualmente
- ✅ Todos los archivos transferidos vía FTP
- ✅ Permisos de carpetas configurados
- ✅ Assets frontend sirviendo correctamente
- ✅ Autenticación funcionando
- ✅ Pruebas básicas exitosas
- ✅ Documentación de despliegue completa

**Notas Importantes:**

- ⚠️ No se puede usar `php artisan migrate` en el servidor
- ⚠️ Toda optimización de Laravel debe hacerse localmente antes de subir
- ⚠️ Los cambios futuros requerirán: modificar local → build → FTP → actualizar BD manualmente
- ⚠️ Mantener un SQL de actualizaciones incrementales para futuros cambios de esquema

---

## ⏱️ Resumen de Estimaciones de Tiempo

| Fase                                            | Duración Estimada | Esfuerzo (horas) |
| ----------------------------------------------- | ----------------- | ---------------- |
| **FASE 1:** Configuración Inicial               | 2-3 días          | 20h              |
| **FASE 2:** Backend - Estructura Base           | 5-7 días          | 43h              |
| **FASE 3:** Backend - Sistema de Calificación   | 4-6 días          | 48h              |
| **FASE 4:** Frontend - Setup y Componentes Base | 3-4 días          | 27h              |
| **FASE 5:** Frontend - Módulo Administración    | 8-10 días         | 72h              |
| **FASE 6:** Frontend - Módulo Profesor          | 6-8 días          | 52h              |
| **FASE 7:** Integración y Testing               | 4-5 días          | 42h              |
| **FASE 8:** Despliegue                          | 2-3 días          | 25h              |
| **TOTAL**                                       | **34-46 días**    | **~329 horas**   |

### Consideraciones

- **Estimación optimista:** 34 días (sin contratiempos)
- **Estimación realista:** 40 días (con tiempo para correcciones)
- **Estimación pesimista:** 46 días (con imprevistos)
- **Ritmo de trabajo:** ~8 horas/día de desarrollo efectivo
- **Duración calendario:** ~7-9 semanas

---

## 🔧 Tecnologías y Paquetes Adicionales

### Backend (Composer)

```json
{
    "require": {
        "php": "^8.2",
        "laravel/framework": "^12.0",
        "inertiajs/inertia-laravel": "^2.0",
        "laravel/fortify": "^1.30",
        "laravel/wayfinder": "^0.1.9",
        "maatwebsite/excel": "^3.1",
        "spatie/laravel-permission": "^6.0" // Para roles y permisos más avanzados (opcional)
    },
    "require-dev": {
        "laravel/boost": "2.0",
        "laravel/pint": "^1.24",
        "pestphp/pest": "^4.3",
        "pestphp/pest-plugin-laravel": "^4.0"
    }
}
```

### Frontend (NPM)

```json
{
    "dependencies": {
        "@inertiajs/react": "^2.0.0",
        "react": "^19.0.0",
        "react-dom": "^19.0.0",
        "@tanstack/react-table": "^8.0.0", // Para DataTable avanzado
        "recharts": "^2.10.0", // Para gráficas
        "react-hook-form": "^7.49.0", // Para formularios
        "zod": "^3.22.0", // Para validaciones
        "lucide-react": "^0.300.0", // Iconos
        "date-fns": "^3.0.0", // Manipulación de fechas
        "clsx": "^2.1.0",
        "tailwind-merge": "^2.2.0"
    },
    "devDependencies": {
        "@vitejs/plugin-react": "^4.2.1",
        "@types/react": "^18.2.0",
        "@types/react-dom": "^18.2.0",
        "typescript": "^5.3.3",
        "vite": "^7.3.1",
        "@tailwindcss/vite": "^4.0.0",
        "vitest": "^1.0.0", // Testing frontend
        "@testing-library/react": "^14.0.0"
    }
}
```

---

## 📐 Patrones de Diseño y Buenas Prácticas

### Backend

1. **Repository Pattern (Opcional)**
    - Abstracción de lógica de acceso a datos
    - Facilita testing con mocks

2. **Service Layer**
    - Lógica de negocio en servicios dedicados
    - Controladores delgados

3. **Form Request Validation**
    - Validaciones separadas de controladores
    - Reutilización de reglas

4. **Resource Transformers**
    - Eloquent API Resources para transformar respuestas
    - Consistencia en formato JSON

5. **Policy-Based Authorization**
    - Políticas para cada modelo
    - Centralización de lógica de permisos

### Frontend

1. **Component Composition**
    - Componentes pequeños y reutilizables
    - Props bien definidas con TypeScript

2. **Custom Hooks**
    - Lógica reutilizable (useGrades, useStatistics)
    - Separación de concerns

3. **Context API**
    - Manejo de estado global (usuario, configuración)
    - Evitar prop drilling

4. **Type Safety**
    - TypeScript estricto
    - Interfaces para todos los datos

5. **Error Boundaries**
    - Manejo de errores en componentes
    - Feedback visual apropiado

---

## 🚨 Riesgos y Mitigaciones

| Riesgo                                         | Probabilidad | Impacto | Mitigación                                                                       |
| ---------------------------------------------- | ------------ | ------- | -------------------------------------------------------------------------------- |
| **Complejidad de cálculos estadísticos**       | Media        | Alto    | Crear tests exhaustivos, validar con datos reales, revisar fórmulas con expertos |
| **Performance en importación Excel**           | Media        | Medio   | Procesar en chunks, usar queues, limitar tamaño de archivo                       |
| **Escalabilidad con muchos estudiantes**       | Baja         | Alto    | Implementar paginación, lazy loading, cacheo de consultas                        |
| **Bugs en sistema de calificación**            | Alta         | Crítico | Testing riguroso, validaciones exhaustivas, logs detallados                      |
| **Problemas de compatibilidad de navegadores** | Baja         | Bajo    | Testing cross-browser, usar polyfills, progressive enhancement                   |
| **Pérdida de datos durante calificación**      | Media        | Crítico | Auto-save frecuente, transacciones DB, backups regulares                         |
| **Confusión de usuarios con UI compleja**      | Alta         | Medio   | Testing con usuarios reales, tutoriales, tooltips, documentación                 |

---

## 📈 Métricas de Éxito

### Técnicas

- ✅ Cobertura de tests > 80%
- ✅ Tiempo de carga de página < 3 segundos
- ✅ Tiempo de respuesta API < 500ms
- ✅ 0 errores críticos en producción (primer mes)
- ✅ Score de Lighthouse > 85

### Funcionales

- ✅ Administradores pueden crear estructura completa en < 1 hora
- ✅ Profesores pueden calificar 30 estudiantes en < 30 minutos (manual)
- ✅ Importación Excel de 100 estudiantes en < 1 minuto
- ✅ Exportación de reportes en < 10 segundos
- ✅ Sistema funciona en mobile, tablet y desktop

### Negocio

- ✅ 100% de profesores asignados pueden acceder al sistema
- ✅ 100% de calificaciones completadas en período académico
- ✅ 0 quejas sobre pérdida de datos
- ✅ Satisfacción de usuarios > 4/5

---

## 🔄 Mantenimiento Post-Lanzamiento

### Tareas Recurrentes

**Diarias:**

- Revisar logs de errores
- Verificar backups automáticos

**Semanales:**

- Revisar métricas de uso
- Responder feedback de usuarios
- Corregir bugs menores

**Mensuales:**

- Actualizar dependencias (security patches)
- Revisar performance
- Optimizar queries lentas
- Backup completo del sistema

**Trimestrales:**

- Actualizar versiones de Laravel/React (si hay breaking changes planificar)
- Revisar y actualizar documentación
- Planificar nuevas funcionalidades

---

## 📚 Recursos y Referencias

### Documentación Oficial

- [Laravel 12 Documentation](https://laravel.com/docs/12.x)
- [Inertia.js v2 Documentation](https://inertiajs.com)
- [React 19 Documentation](https://react.dev)
- [Laravel Wayfinder](https://github.com/laravel/wayfinder)
- [Pest Testing Framework](https://pestphp.com)
- [shadcn/ui](https://ui.shadcn.com)
- [Tailwind CSS](https://tailwindcss.com)

### Paquetes Clave

- [Laravel Excel](https://docs.laravel-excel.com)
- [React Hook Form](https://react-hook-form.com)
- [TanStack Table](https://tanstack.com/table)
- [Recharts](https://recharts.org)

---

## 👨‍💻 Responsable del Proyecto

**Desarrollador:** Joseph López Henao
**Rol:** Estudiante de Ingeniería de Software - 6to Semestre
**Universidad:** Universidad de La Salle

**Responsabilidades:**

- ✅ Análisis y diseño de la solución
- ✅ Implementación completa del backend (Laravel)
- ✅ Implementación completa del frontend (React + TypeScript)
- ✅ Testing y control de calidad
- ✅ Documentación técnica y de usuario
- ✅ Preparación y ejecución del despliegue
- ✅ Coordinación con administradores del servidor universitario

**Nota:** Este es un proyecto individual desarrollado como parte de la formación académica.

---

## 📝 Notas Finales

### Decisiones Técnicas Pendientes

- [x] ~~Decisión sobre hosting~~ → **Resuelto:** Servidor Apache universitario con acceso FTP
- [ ] Coordinar con administrador universitario: credenciales FTP y MySQL
- [ ] Obtener nombre de dominio asignado por la universidad
- [ ] Definir límites de importación Excel (max filas según capacidad del servidor)
- [ ] Decidir estrategia de actualizaciones (¿con qué frecuencia se desplegará?)
- [ ] Definir proceso de backup de BD (¿quién es responsable? ¿manual o automático?)

### Funcionalidades Futuras (Post-MVP)

- Sistema de notificaciones en tiempo real
- Auditoría completa de cambios
- Versionamiento de diseños pedagógicos
- Reportes comparativos entre períodos
- Dashboard analítico avanzado con BI
- Exportación a otros formatos (PDF, CSV)
- Integración con sistemas externos (LMS, ERP)
- App móvil nativa (opcional)

---

## ✅ Checklist de Entrega Final

### Backend

- [ ] Todas las migraciones ejecutadas
- [ ] Seeders iniciales funcionando
- [ ] 22 modelos con relaciones correctas
- [ ] Controladores completos
- [ ] Form Requests de validación
- [ ] Servicios de negocio implementados
- [ ] Tests con >80% cobertura
- [ ] Código formateado con Pint

### Frontend

- [ ] Layouts responsive
- [ ] Todos los CRUDs funcionales
- [ ] Sistema de calificación completo
- [ ] Importación/exportación Excel
- [ ] Estadísticas con gráficas
- [ ] TreeView jerárquico funcional
- [ ] Componentes TypeScript tipados
- [ ] Build de producción optimizado

### Despliegue

- [ ] Archivos transferidos completamente vía FTP
- [ ] Base de datos MySQL creada manualmente en servidor
- [ ] Estructura de tablas importada correctamente (22 tablas)
- [ ] Datos iniciales (seeders) importados
- [ ] Permisos de carpetas configurados (storage/, bootstrap/cache/)
- [ ] Archivo `.env` configurado en servidor con credenciales correctas
- [ ] Assets frontend (CSS/JS) sirviendo correctamente
- [ ] Dominio universitario accesible y funcional
- [ ] Usuario administrador inicial creado y funcional
- [ ] Logs de errores accesibles en `storage/logs/`

### Documentación

- [ ] README.md actualizado
- [ ] Guía de instalación
- [ ] Documentación de APIs
- [ ] Guía de usuario básica
- [ ] Proceso de despliegue documentado

---

**Documento creado:** 2026-02-12
**Última actualización:** 2026-02-12
**Versión:** 1.0.0
**Desarrollador:** Joseph López Henao - Estudiante Ingeniería de Software (6to Semestre)
**Universidad:** Universidad de La Salle
**Estado:** Aprobado para inicio de desarrollo

**Características del Despliegue:**

- **Servidor:** Apache + PHP + MySQL (Universidad de La Salle)
- **Acceso:** FTP únicamente (sin SSH)
- **Proceso:** Desarrollo local → Build → Transferencia FTP → Configuración manual

---

_Este documento es un plan vivo y puede ser actualizado conforme avanza el proyecto._
