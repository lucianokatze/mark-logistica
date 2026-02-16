use assert_cmd::Command;
use predicates::prelude::*;

#[test]
fn fails_without_input_file() {
    let mut cmd = Command::cargo_bin("lkengine").unwrap();
    cmd.assert()
        .failure()
        .stderr(predicate::str::contains("Usage:"));
}

#[test]
fn shows_help() {
    let mut cmd = Command::cargo_bin("lkengine").unwrap();
    cmd.arg("--help")
        .assert()
        .success()
        .stdout(predicate::str::contains("Live Rust interpreter"));
}
