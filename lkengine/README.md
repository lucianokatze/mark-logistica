# LKEngine (MVP)

LKEngine ejecuta archivos `.rs` como **intérprete en vivo** sobre un subconjunto de Rust (sin fallback de compilación automática).

```bash
lkengine main.rs
lkengine main.rs --watch
lkengine main.rs -- arg1 arg2
```

## Estado actual

- ✅ Intérprete en vivo para subconjunto MVP: `fn main`, `let`, asignaciones, `if`, `while`, operadores básicos, `println!`, `arg(index, default)`.
- ✅ `--watch` reejecuta en cada cambio.
- ⚠️ Aún no interpreta el 100% de Rust (threads/channels/macros/traits complejos, etc.).

> Nota: `edition = "2024"` es la edición del lenguaje Rust, no el año calendario.

## Instalación Linux (paso a paso)

```bash
curl --proto '=https' --tlsv1.2 -sSf https://sh.rustup.rs | sh
source "$HOME/.cargo/env"
cd /workspace/mark-logistica/lkengine
cargo install --path .
lkengine --help
```

## Ejemplo simple

`main.rs`:

```rust
fn main() {
    let name = arg(0, "World");
    println!("Hello {name}!");
}
```

```bash
lkengine main.rs -- Codex
```

## Limitación actual importante

Si el archivo usa características fuera del subconjunto MVP, LKEngine devolverá error explícito de interpretación. No compila automáticamente por detrás.

## Testing

```bash
cargo fmt -- --check
cargo test
cargo run -- --help
```
