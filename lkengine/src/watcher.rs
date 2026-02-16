use std::path::Path;
use std::sync::mpsc::channel;

use notify::{Config, EventKind, RecommendedWatcher, RecursiveMode, Watcher};

use crate::error::LkError;

pub fn watch_file<F>(path: &Path, mut on_change: F) -> Result<(), LkError>
where
    F: FnMut() -> Result<(), LkError>,
{
    let (tx, rx) = channel();
    let mut watcher = RecommendedWatcher::new(tx, Config::default())
        .map_err(|e| LkError::Watch(e.to_string()))?;

    watcher
        .watch(path, RecursiveMode::NonRecursive)
        .map_err(|e| LkError::Watch(e.to_string()))?;

    loop {
        match rx.recv() {
            Ok(Ok(event)) => match event.kind {
                EventKind::Create(_) | EventKind::Modify(_) => {
                    on_change()?;
                }
                _ => {}
            },
            Ok(Err(e)) => return Err(LkError::Watch(e.to_string())),
            Err(e) => return Err(LkError::Watch(e.to_string())),
        }
    }
}
