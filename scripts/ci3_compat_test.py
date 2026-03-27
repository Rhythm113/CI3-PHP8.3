#!/usr/bin/env python3
"""
CI3 PHP 8.x Automated Compatibility Test
=========================================
Scans the CI3 project (system/ and application/) for PHP 8.x breaking changes,
runs php -l syntax checks on all PHP files, and prints a pass/fail summary.

Usage:
    python scripts/ci3_compat_test.py
    python scripts/ci3_compat_test.py --root D:\\NSU\\CI3
    python scripts/ci3_compat_test.py --php E:\\xampp\\php\\php.exe
    python scripts/ci3_compat_test.py --app-only
    python scripts/ci3_compat_test.py --report compat_report.md

Exit code:
    0  All checks passed
    1  One or more errors or syntax failures found
"""

import os
import re
import sys
import shutil
import subprocess
import argparse
from pathlib import Path
from datetime import datetime

# -------------------------------------------------------------------------------
# PHP 8.x checks (label, regex, severity, fix hint)
# -------------------------------------------------------------------------------

CHECKS = [
    (
        "create_function() removed",
        re.compile(r'\bcreate_function\s*\(', re.IGNORECASE),
        "ERROR",
        "Removed PHP 8.0 -- use fn() or anonymous function() {}",
    ),
    (
        "each() removed",
        re.compile(r'\beach\s*\(', re.IGNORECASE),
        "ERROR",
        "Removed PHP 8.0 -- replace with foreach",
    ),
    (
        "ereg*() removed",
        re.compile(r'\bereg(i|_replace|i_replace)?\s*\(', re.IGNORECASE),
        "ERROR",
        "Removed PHP 7.0 -- replace with preg_*()",
    ),
    (
        "mysql_*() removed",
        re.compile(r'\bmysql_[a-z_]+\s*\(', re.IGNORECASE),
        "ERROR",
        "Removed PHP 7.0 -- replace with mysqli or PDO",
    ),
    (
        "mcrypt_*() removed",
        re.compile(r'\bmcrypt_[a-z_]+\s*\(', re.IGNORECASE),
        "ERROR",
        "Removed PHP 7.2 -- use openssl_*()",
    ),
    (
        "FILTER_SANITIZE_STRING removed",
        re.compile(r'\bFILTER_SANITIZE_STRING\b'),
        "ERROR",
        "Removed PHP 8.1 -- use FILTER_DEFAULT or htmlspecialchars()",
    ),
    (
        "utf8_encode/utf8_decode() removed",
        re.compile(r'\butf8_(encode|decode)\s*\(', re.IGNORECASE),
        "ERROR",
        "Removed PHP 8.2 -- use mb_convert_encoding()",
    ),
    (
        "new mysqli_driver() removed",
        re.compile(r'\bnew\s+mysqli_driver\s*\(', re.IGNORECASE),
        "ERROR",
        "Removed PHP 8.3 -- use mysqli_report(MYSQLI_REPORT_OFF)",
    ),
    (
        "get_magic_quotes_gpc() removed",
        re.compile(r'(?<!function_exists\([\'"])get_magic_quotes_gpc\s*\(', re.IGNORECASE),
        "WARNING",
        "Removed PHP 8.0 -- guard with function_exists() or remove",
    ),
    (
        "ini_set mbstring.internal_encoding removed",
        re.compile(r'ini_set\s*\(\s*[\'"]mbstring\.internal_encoding[\'"]', re.IGNORECASE),
        "WARNING",
        "Removed PHP 8.0 -- use mb_internal_encoding()",
    ),
    (
        "ini_set iconv.internal_encoding removed",
        re.compile(r'ini_set\s*\(\s*[\'"]iconv\.internal_encoding[\'"]', re.IGNORECASE),
        "WARNING",
        "Removed PHP 8.0 -- use iconv_set_encoding()",
    ),
    (
        "ini_set session.hash_function removed",
        re.compile(r'ini_set\s*\(\s*[\'"]session\.hash_function[\'"]', re.IGNORECASE),
        "WARNING",
        "Removed PHP 7.1 -- use session.sid_length instead",
    ),
    (
        "mysqli::ping() deprecated (PHP 8.4)",
        re.compile(r'->\s*ping\s*\(\s*\)', re.IGNORECASE),
        "INFO",
        "Deprecated PHP 8.4 -- replace with SELECT 1 query",
    ),
    (
        "Loose == 0 comparison changed in PHP 8.0",
        re.compile(r'==\s*0\b'),
        "INFO",
        "PHP 8.0: '0 == string' now returns FALSE -- audit these comparisons",
    ),
]


def find_php_binary(explicit: str = None) -> str:
    if explicit and os.path.isfile(explicit):
        return explicit
    for candidate in ["php", "php.exe"]:
        found = shutil.which(candidate)
        if found:
            return found
    common = [
        r"E:\xampp\php\php.exe",
        r"C:\xampp\php\php.exe",
        r"D:\xampp\php\php.exe",
        "/usr/bin/php",
        "/usr/local/bin/php",
    ]
    for p in common:
        if os.path.isfile(p):
            return p
    return None


def syntax_check(php_bin: str, path: Path) -> tuple[bool, str]:
    try:
        r = subprocess.run(
            [php_bin, "-l", str(path)],
            capture_output=True, text=True, timeout=10,
        )
        ok = r.returncode == 0
        out = (r.stdout + r.stderr).strip()
        return ok, out
    except subprocess.TimeoutExpired:
        return False, "Timeout"
    except Exception as e:
        return False, str(e)


def scan_file(path: Path) -> list[dict]:
    results = []
    try:
        lines = path.read_text(encoding="utf-8", errors="replace").splitlines()
        for label, pattern, severity, fix in CHECKS:
            for i, line in enumerate(lines, 1):
                if pattern.search(line):
                    results.append({
                        "file": str(path),
                        "line": i,
                        "severity": severity,
                        "label": label,
                        "fix": fix,
                        "snippet": line.strip()[:100],
                    })
    except Exception as e:
        results.append({
            "file": str(path), "line": 0,
            "severity": "ERROR", "label": "Read error",
            "fix": str(e), "snippet": "",
        })
    return results


def collect_php_files(root: Path, app_only: bool) -> list[Path]:
    files = list((root / "application").rglob("*.php"))
    if not app_only:
        files += list((root / "system").rglob("*.php"))
    return sorted(files)


def run(root: Path, php_bin: str, app_only: bool, report_path: Path = None):
    ts = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    scope = "application/ only" if app_only else "system/ + application/"

    print("=" * 60)
    print("  CI3 PHP 8.x Compatibility Test")
    print(f"  {ts}")
    print(f"  Root   : {root}")
    print(f"  Scope  : {scope}")
    print(f"  PHP    : {php_bin or 'not found -- syntax check skipped'}")
    print("=" * 60)

    php_files = collect_php_files(root, app_only)
    print(f"\n  Scanning {len(php_files)} PHP files...\n")

    all_issues = []
    for f in php_files:
        all_issues.extend(scan_file(f))

    errors   = [i for i in all_issues if i["severity"] == "ERROR"]
    warnings = [i for i in all_issues if i["severity"] == "WARNING"]
    infos    = [i for i in all_issues if i["severity"] == "INFO"]

    # Syntax check
    syntax_fails = []
    if php_bin:
        print(f"  Running php -l on {len(php_files)} files...")
        for f in php_files:
            ok, out = syntax_check(php_bin, f)
            if not ok:
                try:
                    rel = f.relative_to(root)
                except ValueError:
                    rel = f
                syntax_fails.append({"file": str(rel), "output": out})
        if syntax_fails:
            print(f"  [FAIL] {len(syntax_fails)} syntax error(s) found")
        else:
            print("  [PASS] All files passed php -l")
    else:
        print("  [SKIP] PHP binary not found -- syntax check skipped")

    # Print summary
    print(f"\n  PHP 8.x scan:")
    print(f"    Errors   : {len(errors)}")
    print(f"    Warnings : {len(warnings)}")
    print(f"    Infos    : {len(infos)}")

    # Print errors and warnings
    def print_issues(issue_list, prefix):
        for iss in issue_list[:20]:  # cap at 20 per category in console
            try:
                rel = Path(iss["file"]).relative_to(root)
            except ValueError:
                rel = iss["file"]
            print(f"    {prefix} {rel}:{iss['line']} -- {iss['label']}")
            print(f"           Fix: {iss['fix']}")
            if iss["snippet"]:
                print(f"           Code: {iss['snippet'][:80]}")

    if errors:
        print("\n  -- Errors (will break on PHP 8.x) --")
        print_issues(errors, "[ERROR]")
    if warnings:
        print("\n  -- Warnings --")
        print_issues(warnings, "[WARN]")
    if infos and not errors and not warnings:
        print("\n  -- Info --")
        print_issues(infos, "[INFO]")

    # Write report
    if report_path:
        write_report(report_path, root, ts, scope, php_bin,
                     php_files, all_issues, syntax_fails)
        print(f"\n  Report written: {report_path}")

    # Final result
    print("\n" + "=" * 60)
    failed = bool(errors or syntax_fails)
    if failed:
        print("  RESULT: FAIL -- fix errors before deploying to PHP 8.3")
    else:
        print("  RESULT: PASS -- no blocking PHP 8.x issues found")
    print("=" * 60)
    return 0 if not failed else 1


def write_report(path: Path, root: Path, ts: str, scope: str,
                 php_bin: str, php_files: list, issues: list, syntax_fails: list):
    errors   = [i for i in issues if i["severity"] == "ERROR"]
    warnings = [i for i in issues if i["severity"] == "WARNING"]
    infos    = [i for i in issues if i["severity"] == "INFO"]

    lines = [
        "# CI3 PHP 8.x Compatibility Report",
        "",
        f"> Generated: {ts}",
        f"> Project: `{root}`",
        f"> Scope: {scope}",
        f"> PHP binary: `{php_bin or 'not found'}`",
        "",
        "---",
        "",
        "## Summary",
        "",
        f"| Check | Result |",
        f"|---|---|",
        f"| PHP files scanned | {len(php_files)} |",
        f"| PHP syntax errors | {len(syntax_fails)} |",
        f"| PHP 8.x errors    | {len(errors)} |",
        f"| PHP 8.x warnings  | {len(warnings)} |",
        f"| PHP 8.x info      | {len(infos)} |",
        f"| Overall result    | {'FAIL' if errors or syntax_fails else 'PASS'} |",
        "",
        "---",
        "",
    ]

    def section(issue_list, heading):
        if not issue_list:
            return []
        out = [heading, ""]
        by_file = {}
        for i in issue_list:
            by_file.setdefault(i["file"], []).append(i)
        for fpath, file_issues in sorted(by_file.items()):
            try:
                rel = str(Path(fpath).relative_to(root))
            except ValueError:
                rel = fpath
            out += [f"### `{rel}`", ""]
            for i in file_issues:
                out += [
                    f"- **[{i['severity']}] Line {i['line']}** -- {i['label']}",
                    f"  - Fix: {i['fix']}",
                ]
                if i["snippet"]:
                    out += [f"  - Code: `{i['snippet']}`"]
            out += [""]
        return out

    if syntax_fails:
        lines += ["## PHP Syntax Errors", ""]
        for sf in syntax_fails:
            lines += [f"### `{sf['file']}`", "```", sf["output"], "```", ""]

    lines += section(errors,   "## Errors (blocking)")
    lines += section(warnings, "## Warnings (deprecated)")
    lines += section(infos,    "## Info (review)")

    if not errors and not warnings and not infos and not syntax_fails:
        lines += ["> No PHP 8.x compatibility issues found.", ""]

    path.write_text("\n".join(lines), encoding="utf-8")


def main():
    parser = argparse.ArgumentParser(
        description="CI3 PHP 8.x automated compatibility test"
    )
    parser.add_argument(
        "--root", default=".",
        help="Path to CI3 project root (default: current directory)"
    )
    parser.add_argument(
        "--php", default=None,
        help="Path to PHP binary (auto-detected if not specified)"
    )
    parser.add_argument(
        "--app-only", action="store_true",
        help="Scan application/ only, skip system/ (faster)"
    )
    parser.add_argument(
        "--report", default=None,
        help="Write Markdown report to this file (default: no report)"
    )
    args = parser.parse_args()

    root = Path(args.root).resolve()
    if not root.exists():
        print(f"ERROR: Root path does not exist: {root}")
        sys.exit(1)

    php_bin = find_php_binary(args.php)
    report_path = Path(args.report) if args.report else None

    code = run(
        root=root,
        php_bin=php_bin,
        app_only=args.app_only,
        report_path=report_path,
    )
    sys.exit(code)


if __name__ == "__main__":
    main()
