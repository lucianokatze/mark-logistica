use std::path::PathBuf;

use thiserror::Error;

#[derive(Debug, Error)]
pub enum LkError {
    #[error("source file does not exist: {0}")]
    MissingSource(PathBuf),

    #[error("I/O error: {0}")]
    Io(#[from] std::io::Error),

    #[error("parse error: {0}")]
    Parse(String),

    #[error("runtime error: {0}")]
    Runtime(String),

    #[error("watch error: {0}")]
    Watch(String),
}
