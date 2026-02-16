# LKEngine — Backlog operativo (bloques ~1 hora)

## Contexto y alcance
LKEngine busca ofrecer una UX estilo Node.js para Rust:

```bash
lkengine main.rs
lkengine main.rs --watch
lkengine main.rs --release
```

Sin interpretar Rust: se compila de forma real (`rustc`/`cargo`), se cachea por hash y se ejecuta transparentemente.

---

## Reglas de ejecución para cada bloque
Cada bloque debe incluir:
- **Archivos objetivo**
- **Comandos a ejecutar**
- **Tests asociados**
- **Definición de hecho (DoD)**
- **Riesgos**

---

## Sprint 0 — Base del proyecto

### Bloque 0.1 (1h) — Bootstrap del CLI
**Objetivo:** crear esqueleto mínimo ejecutable.

**Archivos objetivo**
- `Cargo.toml`
- `src/main.rs`
- `src/compiler.rs`
- `src/cache.rs`
- `src/runner.rs`
- `src/deps.rs`
- `tests/smoke_cli.rs`

**Comandos**
- `cargo check`
- `cargo run -- --help`

**Tests**
- `cargo test smoke_cli`

**DoD**
- Compila sin errores.
- Existen módulos base conectados desde `main.rs`.

**Riesgos**
- Acoplar demasiado pronto la lógica de orquestación.

---

### Bloque 0.2 (1h) — Parsing de argumentos
**Objetivo:** soportar `archivo.rs`, `--watch`, `--release`, `-- <args>`.

**Archivos objetivo**
- `Cargo.toml` (dependencia `clap`)
- `src/main.rs` o `src/cli.rs`
- `tests/cli_args.rs`

**Comandos**
- `cargo test cli_args`
- `cargo run -- main.rs --watch --release -- arg1`

**Tests**
- Parsing de flags válidos.
- Error cuando falta archivo.

**DoD**
- Los argumentos quedan mapeados a una estructura usable por el runtime.

**Riesgos**
- Ambigüedad en args del usuario vs args del programa ejecutado.

---

## Sprint 1 — Cache y compilación básica

### Bloque 1.1 (1h) — Hash SHA-256 de fuente
**Objetivo:** hash estable por contenido.

**Archivos objetivo**
- `src/cache.rs`
- `tests/cache_hash.rs`

**Comandos**
- `cargo test cache_hash`

**Tests**
- Mismo contenido => mismo hash.
- Contenido diferente => hash diferente.

**DoD**
- Función determinista `compute_hash(path) -> String`.

**Riesgos**
- Diferencias por normalización de saltos de línea.

---

### Bloque 1.2 (1h) — Layout de cache
**Objetivo:** materializar `~/.lkengine/cache/<hash>/`.

**Archivos objetivo**
- `src/cache.rs`
- `tests/cache_layout.rs`

**Comandos**
- `cargo test cache_layout`

**Tests**
- Crea estructura de directorios en cache.
- Reutiliza carpetas existentes sin fallar.

**DoD**
- API de paths de cache desacoplada y testeada.

**Riesgos**
- Compatibilidad de rutas entre SO.

---

### Bloque 1.3 (1h) — Cache hit/miss
**Objetivo:** evitar recompilar cuando existe binario válido.

**Archivos objetivo**
- `src/cache.rs`
- `src/main.rs`
- `tests/cache_hit.rs`

**Comandos**
- `cargo test cache_hit`

**Tests**
- Hit: no dispara compilación.
- Miss: continúa a compilación.

**DoD**
- Comportamiento explícito para modo debug/release.

**Riesgos**
- Criterio de invalidez incompleto (ej. flags no contemplados).

---

### Bloque 1.4 (1h) — Compilación con rustc
**Objetivo:** compilar `.rs` simple hacia binario en cache.

**Archivos objetivo**
- `src/compiler.rs`
- `tests/compiler_simple.rs`

**Comandos**
- `cargo test compiler_simple`

**Tests**
- Programa mínimo compila y deja binario ejecutable.

**DoD**
- Invocación de `rustc` con manejo de salida y código de error.

**Riesgos**
- Ruta de output no portable.

---

### Bloque 1.5 (1h) — Soporte `--release`
**Objetivo:** activar optimización `-O` y cache diferenciada.

**Archivos objetivo**
- `src/compiler.rs`
- `src/main.rs`
- `tests/compiler_release.rs`

**Comandos**
- `cargo test compiler_release`

**Tests**
- El comando incorpora `-O` en modo release.

**DoD**
- Modo release funcional y separado de debug.

**Riesgos**
- Mezclar artefactos de build por no separar rutas.

---

## Sprint 2 — Ejecución y errores

### Bloque 2.1 (1h) — Runner de binarios
**Objetivo:** ejecutar binario cacheado y pasar argumentos.

**Archivos objetivo**
- `src/runner.rs`
- `src/main.rs`
- `tests/runner_integration.rs`

**Comandos**
- `cargo test runner_integration`

**Tests**
- Reenvío correcto de args al programa.
- Propagación de exit code.

**DoD**
- Salida del programa visible para el usuario.

**Riesgos**
- Diferencias de quoting en argumentos.

---

### Bloque 2.2 (1h) — Superficie de errores
**Objetivo:** mensajes claros para compilación/ejecución.

**Archivos objetivo**
- `src/main.rs`
- `src/compiler.rs`
- `src/runner.rs`
- `tests/error_paths.rs`

**Comandos**
- `cargo test error_paths`

**Tests**
- Fallo de compilación: mensaje legible.
- Binario ausente/no ejecutable: error contextual.

**DoD**
- El usuario entiende qué falló y en qué etapa.

**Riesgos**
- Pérdida de contexto al encapsular errores.

---

## Sprint 3 — Dependencias automáticas

### Bloque 3.1 (1h) — Detección de crates externos
**Objetivo:** extraer crates desde `use ...`.

**Archivos objetivo**
- `src/deps.rs`
- `tests/deps_detect.rs`

**Comandos**
- `cargo test deps_detect`

**Tests**
- Excluir `std`, `core`, `crate`, `self`, `super`.
- Deduplicación de crates.

**DoD**
- Lista de crates externos confiable para MVP.

**Riesgos**
- Falsos positivos por parseo heurístico.

---

### Bloque 3.2 (1h) — Generación de Cargo.toml temporal
**Objetivo:** crear proyecto cargo efímero en cache.

**Archivos objetivo**
- `src/deps.rs`
- `src/compiler.rs`
- `tests/cargo_toml_gen.rs`

**Comandos**
- `cargo test cargo_toml_gen`

**Tests**
- Manifest válido con dependencias detectadas.

**DoD**
- Layout temporal reproducible y limpio.

**Riesgos**
- Versionado de crates no definido (usar estrategia mínima documentada).

---

### Bloque 3.3 (1h) — Compilación con cargo cuando haya deps
**Objetivo:** fallback automático de `rustc` a `cargo`.

**Archivos objetivo**
- `src/compiler.rs`
- `src/main.rs`
- `tests/compiler_with_deps.rs`

**Comandos**
- `cargo test compiler_with_deps`

**Tests**
- Fuente con crate externo compila y corre.

**DoD**
- Selector de backend de build funcionando por contexto.

**Riesgos**
- Tiempo de build alto en primer run.

---

## Sprint 4 — Watch mode

### Bloque 4.1 (1h) — Watcher y recompilación
**Objetivo:** recompilar y re-ejecutar al detectar cambios.

**Archivos objetivo**
- `Cargo.toml` (`notify`)
- `src/watcher.rs`
- `src/main.rs`
- `tests/watch_smoke.rs`

**Comandos**
- `cargo test watch_smoke`

**Tests**
- Cambio de archivo dispara build+run.

**DoD**
- `lkengine main.rs --watch` responde a ediciones.

**Riesgos**
- Rebotes de eventos (necesario debounce).

---

## Sprint 5 — Calidad, DX y documentación

### Bloque 5.1 (1h) — Integración E2E
**Objetivo:** asegurar flujo completo.

**Archivos objetivo**
- `tests/e2e_simple.rs`
- `tests/e2e_cache.rs`
- `tests/e2e_deps.rs`
- `tests/fixtures/*`

**Comandos**
- `cargo test`

**Tests**
- Primer run compila+ejecuta.
- Segundo run usa cache.
- Caso con dependencias externas.

**DoD**
- Flujos críticos cubiertos en CI local.

**Riesgos**
- Tests lentos/no deterministas por IO externo.

---

### Bloque 5.2 (1h) — README y docs técnicas
**Objetivo:** facilitar adopción y mantenimiento.

**Archivos objetivo**
- `README.md`
- `docs/architecture.md`
- `docs/how-it-works.md`

**Comandos**
- Validación manual de comandos documentados.

**Tests**
- N/A (documentación), pero comandos de ejemplo deben funcionar.

**DoD**
- Un usuario nuevo puede instalar, ejecutar y entender la arquitectura.

**Riesgos**
- Documentación desincronizada con implementación.

---

## Definición de “MVP completado”
Se considera MVP cuando se cumpla:
1. `lkengine main.rs` compila, cachea y ejecuta.
2. Re-ejecución sin cambios evita recompilar.
3. `--release` funcional.
4. Soporte de dependencias externas mediante proyecto Cargo temporal.
5. `--watch` recompila automáticamente.
6. Suite de tests principal en verde.

---

## Recomendación de ejecución inmediata
Orden sugerido para comenzar mañana:
1. Bloque 0.1
2. Bloque 0.2
3. Bloque 1.1
4. Bloque 1.2
5. Bloque 1.3
6. Bloque 1.4
7. Bloque 2.1

Con esto ya tendrás un flujo usable sin dependencias externas, suficiente para una primera demo funcional.
