use clap::Parser;
use std::path::PathBuf;

#[derive(Debug, Parser)]
#[command(name = "lkengine", about = "Live Rust interpreter (MVP subset)")]
pub struct Cli {
    /// Rust source file to execute
    pub file: PathBuf,

    /// Re-run when file changes
    #[arg(long)]
    pub watch: bool,

    /// Reserved flag for future interpreter optimizations
    #[arg(long)]
    pub release: bool,

    /// Args passed to the interpreted program (available via arg(index, default))
    #[arg(last = true)]
    pub args: Vec<String>,
}
