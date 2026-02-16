use std::collections::HashMap;
use std::fs;
use std::path::Path;

use syn::parse::Parser;
use syn::punctuated::Punctuated;
use syn::{BinOp, Block, Expr, ExprLit, ExprMacro, Item, Lit, Pat, Stmt, Token, UnOp};

use crate::error::LkError;

#[derive(Clone, Debug)]
enum Value {
    Int(i64),
    Bool(bool),
    Str(String),
    Unit,
}

impl Value {
    fn as_bool(&self) -> Result<bool, LkError> {
        match self {
            Value::Bool(v) => Ok(*v),
            _ => Err(LkError::Runtime("expected boolean value".to_string())),
        }
    }

    fn as_int(&self) -> Result<i64, LkError> {
        match self {
            Value::Int(v) => Ok(*v),
            _ => Err(LkError::Runtime("expected integer value".to_string())),
        }
    }

    fn to_display(&self) -> String {
        match self {
            Value::Int(v) => v.to_string(),
            Value::Bool(v) => v.to_string(),
            Value::Str(v) => v.clone(),
            Value::Unit => String::new(),
        }
    }
}

pub fn execute_file(path: &Path, args: &[String]) -> Result<(), LkError> {
    if !path.exists() {
        return Err(LkError::MissingSource(path.to_path_buf()));
    }

    let source = fs::read_to_string(path)?;
    execute_source(&source, args)
}

pub fn execute_source(source: &str, args: &[String]) -> Result<(), LkError> {
    let file = syn::parse_file(source).map_err(|e| LkError::Parse(e.to_string()))?;
    let main_block = find_main_block(&file.items)?;

    let mut env = HashMap::<String, Value>::new();
    exec_block(main_block, &mut env, args)?;
    Ok(())
}

fn find_main_block(items: &[Item]) -> Result<&Block, LkError> {
    for item in items {
        if let Item::Fn(item_fn) = item {
            if item_fn.sig.ident == "main" {
                if !item_fn.sig.inputs.is_empty() {
                    return Err(LkError::Runtime(
                        "main con argumentos no está soportado en modo intérprete".to_string(),
                    ));
                }
                return Ok(&item_fn.block);
            }
        }
    }
    Err(LkError::Runtime("no se encontró fn main()".to_string()))
}

fn exec_block(
    block: &Block,
    env: &mut HashMap<String, Value>,
    args: &[String],
) -> Result<(), LkError> {
    for stmt in &block.stmts {
        exec_stmt(stmt, env, args)?;
    }
    Ok(())
}

fn exec_stmt(
    stmt: &Stmt,
    env: &mut HashMap<String, Value>,
    args: &[String],
) -> Result<(), LkError> {
    match stmt {
        Stmt::Local(local) => {
            let name = match &local.pat {
                Pat::Ident(ident) => ident.ident.to_string(),
                _ => {
                    return Err(LkError::Runtime(
                        "solo se soporta let con identificadores simples".to_string(),
                    ));
                }
            };

            let value = match &local.init {
                Some(init) => eval_expr(&init.expr, env, args)?,
                None => Value::Unit,
            };
            env.insert(name, value);
            Ok(())
        }
        Stmt::Expr(expr, _) => {
            eval_expr(expr, env, args)?;
            Ok(())
        }
        Stmt::Macro(stmt_macro) => {
            let expr = Expr::Macro(ExprMacro {
                attrs: vec![],
                mac: stmt_macro.mac.clone(),
            });
            eval_expr(&expr, env, args)?;
            Ok(())
        }
        Stmt::Item(Item::Fn(_)) => Ok(()),
        _ => Err(LkError::Runtime(
            "sentencia no soportada por el intérprete MVP".to_string(),
        )),
    }
}

fn eval_expr(
    expr: &Expr,
    env: &mut HashMap<String, Value>,
    args: &[String],
) -> Result<Value, LkError> {
    match expr {
        Expr::Lit(ExprLit { lit, .. }) => match lit {
            Lit::Int(v) => Ok(Value::Int(
                v.base10_parse::<i64>()
                    .map_err(|e| LkError::Runtime(e.to_string()))?,
            )),
            Lit::Bool(v) => Ok(Value::Bool(v.value())),
            Lit::Str(v) => Ok(Value::Str(v.value())),
            _ => Err(LkError::Runtime("literal no soportado".to_string())),
        },
        Expr::Path(path) => {
            let name = path
                .path
                .get_ident()
                .ok_or_else(|| LkError::Runtime("ruta no soportada".to_string()))?
                .to_string();
            env.get(&name)
                .cloned()
                .ok_or_else(|| LkError::Runtime(format!("variable no definida: {name}")))
        }
        Expr::Paren(paren) => eval_expr(&paren.expr, env, args),
        Expr::Unary(unary) => {
            let value = eval_expr(&unary.expr, env, args)?;
            match unary.op {
                UnOp::Neg(_) => Ok(Value::Int(-value.as_int()?)),
                UnOp::Not(_) => Ok(Value::Bool(!value.as_bool()?)),
                _ => Err(LkError::Runtime("operador unario no soportado".to_string())),
            }
        }
        Expr::Binary(binary) => {
            let left = eval_expr(&binary.left, env, args)?;
            let right = eval_expr(&binary.right, env, args)?;
            eval_binary(binary.op.clone(), left, right)
        }
        Expr::Assign(assign) => {
            let name = match &*assign.left {
                Expr::Path(path) => path
                    .path
                    .get_ident()
                    .ok_or_else(|| LkError::Runtime("asignación inválida".to_string()))?
                    .to_string(),
                _ => return Err(LkError::Runtime("asignación inválida".to_string())),
            };
            let value = eval_expr(&assign.right, env, args)?;
            env.insert(name, value.clone());
            Ok(value)
        }
        Expr::If(expr_if) => {
            let cond = eval_expr(&expr_if.cond, env, args)?.as_bool()?;
            if cond {
                exec_block(&expr_if.then_branch, env, args)?;
            } else if let Some((_, else_expr)) = &expr_if.else_branch {
                eval_expr(else_expr, env, args)?;
            }
            Ok(Value::Unit)
        }
        Expr::While(expr_while) => {
            while eval_expr(&expr_while.cond, env, args)?.as_bool()? {
                exec_block(&expr_while.body, env, args)?;
            }
            Ok(Value::Unit)
        }
        Expr::Block(block) => {
            exec_block(&block.block, env, args)?;
            Ok(Value::Unit)
        }
        Expr::Call(call) => {
            let name = match &*call.func {
                Expr::Path(path) => path
                    .path
                    .get_ident()
                    .ok_or_else(|| LkError::Runtime("llamada no soportada".to_string()))?
                    .to_string(),
                _ => return Err(LkError::Runtime("llamada no soportada".to_string())),
            };

            if name == "arg" {
                if call.args.len() != 2 {
                    return Err(LkError::Runtime(
                        "arg(index, default) requiere 2 parámetros".to_string(),
                    ));
                }
                let idx = eval_expr(call.args.first().unwrap(), env, args)?.as_int()? as usize;
                let fallback = eval_expr(call.args.iter().nth(1).unwrap(), env, args)?.to_display();
                let value = args.get(idx).cloned().unwrap_or(fallback);
                return Ok(Value::Str(value));
            }

            Err(LkError::Runtime(format!("función no soportada: {name}")))
        }
        Expr::Macro(m) => eval_macro(m, env, args),
        _ => Err(LkError::Runtime(
            "expresión no soportada por el intérprete MVP".to_string(),
        )),
    }
}

fn eval_macro(
    m: &ExprMacro,
    env: &mut HashMap<String, Value>,
    args: &[String],
) -> Result<Value, LkError> {
    if !m.mac.path.is_ident("println") {
        return Err(LkError::Runtime("solo se soporta println!".to_string()));
    }

    let parser = Punctuated::<Expr, Token![,]>::parse_terminated;
    let exprs = parser
        .parse2(m.mac.tokens.clone())
        .map_err(|e| LkError::Parse(e.to_string()))?;

    if exprs.is_empty() {
        println!();
        return Ok(Value::Unit);
    }

    let mut iter = exprs.iter();
    let fmt_expr = iter.next().unwrap();
    let fmt_text = eval_expr(fmt_expr, env, args)?.to_display();
    let values = iter
        .map(|e| eval_expr(e, env, args))
        .collect::<Result<Vec<_>, _>>()?;

    let formatted = apply_format(&fmt_text, &values, env);
    println!("{formatted}");
    Ok(Value::Unit)
}

fn apply_format(template: &str, values: &[Value], env: &HashMap<String, Value>) -> String {
    let mut out = template.to_string();
    for value in values {
        if let Some(pos) = out.find("{}") {
            out.replace_range(pos..pos + 2, &value.to_display());
        }
    }

    let mut idx = 0;
    while let Some(start_rel) = out[idx..].find('{') {
        let start = idx + start_rel;
        if let Some(end_rel) = out[start + 1..].find('}') {
            let end = start + 1 + end_rel;
            let key = &out[start + 1..end];
            if key.chars().all(|c| c.is_ascii_alphanumeric() || c == '_') {
                if let Some(value) = env.get(key) {
                    out.replace_range(start..=end, &value.to_display());
                    idx = start + value.to_display().len();
                    continue;
                }
            }
            idx = end + 1;
        } else {
            break;
        }
    }

    out
}

fn eval_binary(op: BinOp, left: Value, right: Value) -> Result<Value, LkError> {
    match op {
        BinOp::Add(_) => match (left, right) {
            (Value::Int(a), Value::Int(b)) => Ok(Value::Int(a + b)),
            (a, b) => Ok(Value::Str(format!("{}{}", a.to_display(), b.to_display()))),
        },
        BinOp::Sub(_) => Ok(Value::Int(left.as_int()? - right.as_int()?)),
        BinOp::Mul(_) => Ok(Value::Int(left.as_int()? * right.as_int()?)),
        BinOp::Div(_) => Ok(Value::Int(left.as_int()? / right.as_int()?)),
        BinOp::Rem(_) => Ok(Value::Int(left.as_int()? % right.as_int()?)),
        BinOp::Eq(_) => Ok(Value::Bool(left.to_display() == right.to_display())),
        BinOp::Ne(_) => Ok(Value::Bool(left.to_display() != right.to_display())),
        BinOp::Lt(_) => Ok(Value::Bool(left.as_int()? < right.as_int()?)),
        BinOp::Le(_) => Ok(Value::Bool(left.as_int()? <= right.as_int()?)),
        BinOp::Gt(_) => Ok(Value::Bool(left.as_int()? > right.as_int()?)),
        BinOp::Ge(_) => Ok(Value::Bool(left.as_int()? >= right.as_int()?)),
        BinOp::And(_) => Ok(Value::Bool(left.as_bool()? && right.as_bool()?)),
        BinOp::Or(_) => Ok(Value::Bool(left.as_bool()? || right.as_bool()?)),
        _ => Err(LkError::Runtime(
            "operador binario no soportado".to_string(),
        )),
    }
}

#[cfg(test)]
mod tests {
    use super::execute_source;

    #[test]
    fn runs_hello_world_program() {
        let source = r#"
fn main() {
    println!("Hello World!");
}
"#;
        assert!(execute_source(source, &[]).is_ok());
    }

    #[test]
    fn supports_while_and_if() {
        let source = r#"
fn main() {
    let i = 0;
    while i < 3 {
        println!("{i}");
        i = i + 1;
    }
    if i == 3 {
        println!("ok");
    }
}
"#;
        assert!(execute_source(source, &[]).is_ok());
    }
}
