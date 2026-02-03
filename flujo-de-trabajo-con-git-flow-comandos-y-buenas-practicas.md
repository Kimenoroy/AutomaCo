##

## Inicialización de Git Flow

Una vez clonado el repositorio, el primer paso es inicializar Git Flow:

```bash
git flow init
```

Durante este proceso, se solicitará configurar los nombres de las ramas principales. Se recomienda aceptar los valores predeterminados:

- **main (o master)**: rama de producción.
- **develop**: rama de desarrollo.
- **feature/**: nuevas funcionalidades.
- **release/**: preparación de versiones.
- **hotfix/**: correcciones urgentes en producción.

Esta configuración establece una base clara y consistente para todo el equipo.

---

## Desarrollo de nuevas funcionalidades (Feature)

Para desarrollar una nueva funcionalidad, se debe crear una rama *feature* a partir de `develop`:

```bash
git flow feature start matriz-excel
```

Este comando:

- Crea la rama `feature/matriz-excel`.
- Parte desde la rama `develop`.
- Cambia automáticamente a la nueva rama.

Una vez en la rama de la funcionalidad, se trabaja con el flujo normal de Git. En entornos empresariales se recomienda seguir un **estándar de commits** para mantener un historial claro y consistente.

### Convención de mensajes de commit

Se utilizará el siguiente formato:

```
<emoji> <tipo>(<alcance>): <verbo en infinitivo> <descripción breve>
```

Donde:

- **emoji**: representa visualmente el tipo de cambio.
- **tipo**: indica la naturaleza del commit (`feat`, `fix`, `docs`, `refactor`, `chore`).
- **alcance** (opcional): módulo o funcionalidad afectada.
- **verbo en infinitivo**: describe la acción realizada (agregar, corregir, actualizar, mejorar, etc.).

### Ejemplos de commits

```bash
✨ feat(matriz-excel): agregar cuadrícula dinámica
🐛 fix(validacion): corregir error en cálculo de filas
📝 docs(readme): actualizar instrucciones de instalación
♻️ refactor(auth): mejorar estructura de validaciones
🚀 chore(deps): actualizar dependencias del proyecto
```

Una vez realizados los cambios, se aplican los comandos habituales de Git utilizando esta convención:

```bash
git add .
git commit -m "✨ feat(matriz-excel): agregar cuadrícula dinámica"
```

### Publicar la feature en el repositorio remoto

Por defecto, la rama *feature* solo existe de forma local. Para compartirla con el equipo, debe publicarse:

```bash
git flow feature publish matriz-excel
```

Esto permite que otros desarrolladores revisen el código o colaboren en la misma funcionalidad.

### Finalizar la feature

Cuando la funcionalidad esté completa y validada, se finaliza la *feature*:

```bash
git flow feature finish matriz-excel
```

Este comando:

- Fusiona la rama `feature/matriz-excel` en `develop`.
- Elimina la rama de la funcionalidad.
- Posiciona al usuario nuevamente en `develop`.

Finalmente, se suben los cambios a remoto:

```bash
git push origin develop
```

---

## Creación de una Release (versión de lanzamiento)

Cuando el proyecto alcanza un estado estable y está listo para ser liberado, se crea una *release*. Estas ramas se utilizan para preparar versiones oficiales (por ejemplo, ajustes finales, documentación o pruebas).

### Iniciar una release

```bash
git flow release start v1.0.0
```

Esto crea la rama `release/v1.0.0` a partir de `develop`. En esta rama se permiten únicamente cambios relacionados con la estabilización de la versión:

```bash
git add .
git commit -m "Preparación de versión v1.0.0"
```

### Finalizar la release

Una vez completadas las pruebas finales, se finaliza la versión:

```bash
git flow release finish v1.0.0
```

Este proceso realiza automáticamente:

- La fusión de `release/v1.0.0` en `main`.
- La creación de una etiqueta (tag) con la versión.
- La fusión de los cambios en `develop`.
- La eliminación de la rama `release/v1.0.0`.

### Publicar los cambios en el repositorio remoto

Para reflejar los cambios finales en el repositorio remoto:

```bash
git push origin main
git push origin develop
```

##
