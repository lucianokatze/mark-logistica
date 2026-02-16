use clap::Parser;
use lkengine::cli::Cli;
use lkengine::error::LkError;
use lkengine::interpreter::execute_file;
use lkengine::watcher::watch_file;

fn execute_once(cli: &Cli) -> Result<(), LkError> {
    execute_file(&cli.file, &cli.args)
}

fn run(cli: Cli) -> Result<(), LkError> {
    execute_once(&cli)?;

    if cli.watch {
        println!("watching {}", cli.file.display());
        watch_file(&cli.file, || {
            println!("change detected. re-running...");
            execute_once(&cli)
        })?;
    }

    Ok(())
}

fn main() {
    let cli = Cli::parse();
    if let Err(err) = run(cli) {
        eprintln!("lkengine error: {err}");
        std::process::exit(1);
    }
}
