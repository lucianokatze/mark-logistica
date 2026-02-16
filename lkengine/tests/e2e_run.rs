use assert_cmd::Command;
use predicates::prelude::*;
use std::fs;

#[test]
fn interprets_rust_file_without_compiling() {
    let tmp = tempfile::tempdir().unwrap();
    let source = tmp.path().join("main.rs");
    fs::write(
        &source,
        r#"fn main() {
    let who = arg(0, "none");
    println!("hello-{who}");
}"#,
    )
    .unwrap();

    let mut cmd = Command::cargo_bin("lkengine").unwrap();
    cmd.arg(&source)
        .arg("--")
        .arg("world")
        .assert()
        .success()
        .stdout(predicate::str::contains("hello-world"));
}
