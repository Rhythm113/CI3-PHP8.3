#!/usr/bin/env python3
"""
CI3 Auto-Migration Script
=========================
Detects the source CI3 version, migrates application-level files
(controllers, models, helpers, libraries, views, config, core, hooks,
third_party, language) into this project, intelligently merges config
arrays, runs PHP syntax checks, flags PHP 8.x incompatibilities, and
writes a detailed Markdown summary report.

Usage:
    python ci3_migrate.py <source_project_path> [options]

Options:
    --target PATH    Target CI3 project root (default: current directory)
    --php    PATH    Path to php.exe binary (default: auto-detected)
    --dry-run        Simulate migration without copying files
    --overwrite      Overwrite existing target files (default: skip)
    --report PATH    Output report file (default: migration_report.md)
"""

import os
import re
import sys
import shutil
import subprocess
import argparse
import hashlib
from datetime import datetime
from pathlib import Path

# -------------------------------------------------------------------------------
# PHP 8.x Issue Detectors
# Each entry: (label, compiled_regex, severity, description)
# -------------------------------------------------------------------------------
PHP8_CHECKS = [
    (
        "REMOVED: create_function()",
        re.compile(r'\bcreate_function\s*\(', re.IGNORECASE),
        "ERROR",
        "Removed in PHP 8.0 -- replace with anonymous functions (fn() => ...)",
    ),
    (
        "REMOVED: each()",
        re.compile(r'\beach\s*\(', re.IGNORECASE),
        "ERROR",
        "Removed in PHP 8.0 -- use foreach instead",
    ),
    (
        "REMOVED: ereg*()",
        re.compile(r'\bereg(i|_replace|i_replace)?\s*\(', re.IGNORECASE),
        "ERROR",
        "Removed in PHP 7.0 -- replace with preg_*() functions",
    ),
    (
        "REMOVED: mysql_*()",
        re.compile(r'\bmysql_[a-z_]+\s*\(', re.IGNORECASE),
        "ERROR",
        "Removed in PHP 7.0 -- replace with mysqli_*() or PDO",
    ),
    (
        "REMOVED: mcrypt_*()",
        re.compile(r'\bmcrypt_[a-z_]+\s*\(', re.IGNORECASE),
        "ERROR",
        "Removed in PHP 7.2 -- replace with openssl_*() functions",
    ),
    (
        "REMOVED: FILTER_SANITIZE_STRING",
        re.compile(r'\bFILTER_SANITIZE_STRING\b', re.IGNORECASE),
        "ERROR",
        "Removed in PHP 8.1 -- use htmlspecialchars() or strip_tags()",
    ),
    (
        "REMOVED: utf8_encode/utf8_decode()",
        re.compile(r'\butf8_(encode|decode)\s*\(', re.IGNORECASE),
        "ERROR",
        "Removed in PHP 8.2 -- replace with mb_convert_encoding()",
    ),
    (
        "REMOVED: mysqli_driver class",
        re.compile(r'\bnew\s+mysqli_driver\s*\(', re.IGNORECASE),
        "ERROR",
        "Removed in PHP 8.3 -- use mysqli_report() instead",
    ),
    (
        "REMOVED: MCRYPT_* constants",
        re.compile(r'\bMCRYPT_[A-Z_]+\b'),
        "ERROR",
        "Removed in PHP 7.2 -- use OpenSSL constants",
    ),
    (
        "DEPRECATED: get_magic_quotes_gpc()",
        re.compile(r'\bget_magic_quotes_gpc\s*\(', re.IGNORECASE),
        "WARNING",
        "Removed in PHP 8.0 -- always returns FALSE on 8.x; remove the call",
    ),
    (
        "DEPRECATED: Dynamic property usage",
        re.compile(r'^\s*\$this->[a-zA-Z_]\w+\s*=', re.MULTILINE),
        "WARNING",
        "PHP 8.2+ deprecates dynamic properties -- add #[AllowDynamicProperties] to class or declare the property explicitly",
    ),
    (
        "DEPRECATED: mbstring.internal_encoding ini_set",
        re.compile(r'ini_set\s*\(\s*[\'"]mbstring\.internal_encoding[\'"]', re.IGNORECASE),
        "WARNING",
        "Removed in PHP 8.0 -- use mb_internal_encoding() instead",
    ),
    (
        "DEPRECATED: iconv.internal_encoding ini_set",
        re.compile(r'ini_set\s*\(\s*[\'"]iconv\.internal_encoding[\'"]', re.IGNORECASE),
        "WARNING",
        "Removed in PHP 8.0 -- use iconv_set_encoding() or rely on default_charset",
    ),
    (
        "DEPRECATED: session.hash_function ini_set",
        re.compile(r'ini_set\s*\(\s*[\'"]session\.hash_function[\'"]', re.IGNORECASE),
        "WARNING",
        "Removed in PHP 7.1 -- use session.sid_length / session.sid_bits_per_character",
    ),
    (
        "RISKY: Loose comparison (== 0 with string)",
        re.compile(r'==\s*0\b'),
        "INFO",
        "PHP 8.0 changed 0 == 'string' to FALSE -- audit loose == 0 comparisons",
    ),
    (
        "DEPRECATED: dl() function",
        re.compile(r'\bdl\s*\(', re.IGNORECASE),
        "WARNING",
        "dl() is deprecated since PHP 5.3 -- avoid dynamic extension loading",
    ),
    (
        "PHP 8.1: Nullable type without explicit ?",
        re.compile(r'function\s+\w+\s*\([^)]*=\s*NULL[^)]*\)\s*:\s*\w', re.IGNORECASE),
        "INFO",
        "PHP 8.4 will require explicit ?Type for nullable params -- use ?Type instead",
    ),
    (
        "DEPRECATED: mysqli::ping()",
        re.compile(r'->\s*ping\s*\(\s*\)', re.IGNORECASE),
        "INFO",
        "mysqli::ping() is deprecated in PHP 8.4 -- replace with a SELECT 1 query",
    ),
    (
        "REMOVED: ibase_* functions",
        re.compile(r'\bibase_[a-z_]+\s*\(', re.IGNORECASE),
        "ERROR",
        "ibase extension removed in PHP 7.4 -- use pecl/ibase",
    ),
    (
        "REMOVED: mssql_* functions",
        re.compile(r'\bmssql_[a-z_]+\s*\(', re.IGNORECASE),
        "ERROR",
        "mssql extension removed in PHP 7.0 -- use sqlsrv or PDO_SQLSRV",
    ),
]

# Application sub-directories to migrate
APP_DIRS = [
    "controllers",
    "models",
    "helpers",
    "libraries",
    "views",
    "hooks",
    "third_party",
    "language",
    "core",
    "config",
]

# Config files to merge rather than overwrite
MERGEABLE_CONFIGS = {
    "config.php",
    "database.php",
    "routes.php",
    "autoload.php",
    "hooks.php",
    "email.php",
    "upload.php",
    "session.php",
    "pagination.php",
    # CI 3.0.x additional config files
    "foreign_chars.php",
    "mimes.php",
    "smileys.php",
    "doctypes.php",
    "profiler.php",
    "user_agents.php",
}

# -------------------------------------------------------------------------------
# Auto-Patchers: safe regex fixes applied during migration
# Each entry: (label, compiled_regex, replacement)
#   replacement can be a string or a callable(match) -> str
# -------------------------------------------------------------------------------

AUTO_PATCHERS = [
    (
        "get_magic_quotes_gpc() -- add function_exists() guard",
        re.compile(
            r'(\b(?:!\s*is_php\s*\([^)]+\)\s*&&\s*)?)'
            r'get_magic_quotes_gpc\s*\(\s*\)',
            re.IGNORECASE,
        ),
        lambda m: (
            m.group(1) + "function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()"
            if m.group(1)
            else "function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()"
        ),
    ),
    (
        "ini_set mbstring.internal_encoding -- replace with mb_internal_encoding()",
        re.compile(
            r'ini_set\s*\(\s*[\'"]mbstring\.internal_encoding[\'"]\s*,\s*([^)]+)\)',
            re.IGNORECASE,
        ),
        lambda m: f"mb_internal_encoding({m.group(1).strip()})",
    ),
    (
        "ini_set iconv.internal_encoding -- replace with iconv_set_encoding()",
        re.compile(
            r'ini_set\s*\(\s*[\'"]iconv\.internal_encoding[\'"]\s*,\s*([^)]+)\)',
            re.IGNORECASE,
        ),
        lambda m: (
            f"(is_php('8.0') ? null : "
            f"iconv_set_encoding('internal_encoding', {m.group(1).strip()}))"
        ),
    ),
    (
        "ini_set session.hash_function -- remove deprecated ini_set call",
        re.compile(
            r'ini_set\s*\(\s*[\'"]session\.hash_function[\'"][^)]*\)\s*;?',
            re.IGNORECASE,
        ),
        "/* session.hash_function removed in PHP 7.1 -- use session.sid_bits_per_character/sid_length instead */",
    ),
    (
        "utf8_encode() -- replace with mb_convert_encoding()",
        re.compile(r'\butf8_encode\s*\(', re.IGNORECASE),
        "mb_convert_encoding(",
    ),
    (
        "utf8_decode() -- replace with mb_convert_encoding()",
        re.compile(r'\butf8_decode\s*\(([^)]+)\)', re.IGNORECASE),
        lambda m: f"mb_convert_encoding({m.group(1)}, 'ISO-8859-1', 'UTF-8')",
    ),
    (
        "FILTER_SANITIZE_STRING -- replace with FILTER_DEFAULT",
        re.compile(r'\bFILTER_SANITIZE_STRING\b'),
        "FILTER_DEFAULT /* FILTER_SANITIZE_STRING removed in PHP 8.1 */",
    ),
    (
        "new mysqli_driver() -- replace with mysqli_report()",
        re.compile(r'new\s+mysqli_driver\s*\(\s*\)', re.IGNORECASE),
        "mysqli_report(MYSQLI_REPORT_OFF)",
    ),
    (
        "create_function() -- replace with inline closure comment",
        re.compile(r'\bcreate_function\s*\(', re.IGNORECASE),
        "/* TODO: replace create_function (removed PHP 8.0) -- use fn() or function() {} instead */ create_function(",
    ),
    (
        "each() -- add TODO comment",
        re.compile(r'\beach\s*\(', re.IGNORECASE),
        "/* TODO: each() removed in PHP 8.0 -- replace with foreach */ each(",
    ),
    (
        "ereg() legacy regex -- add TODO comment",
        re.compile(r'\bereg(i|_replace|i_replace)?\s*\(', re.IGNORECASE),
        lambda m: (
            f"/* TODO: ereg{m.group(1) or ''}() removed in PHP 7.0 -- "
            f"replace with preg_{('match' if not m.group(1) else 'replace')}() */ "
            f"ereg{m.group(1) or ''}("
        ),
    ),
    (
        "mysql_*() legacy driver -- add TODO comment",
        re.compile(r'\b(mysql_[a-z_]+)\s*\(', re.IGNORECASE),
        lambda m: (
            f"/* TODO: {m.group(1)}() removed in PHP 7.0 -- "
            f"replace with mysqli or PDO */ {m.group(1)}("
        ),
    ),
]


def apply_auto_patches(content: str, path: Path) -> tuple[str, list[str]]:
    """Apply all AUTO_PATCHERS to content, return (patched_content, patch_log)."""
    log = []
    for label, pattern, replacement in AUTO_PATCHERS:
        if callable(replacement):
            new_content, count = pattern.subn(replacement, content)
        else:
            new_content, count = pattern.subn(replacement, content)
        if count:
            log.append(f"  [{count}x] {label}  (in {path.name})")
            content = new_content
    return content, log


# -------------------------------------------------------------------------------
# Utilities
# -------------------------------------------------------------------------------

def file_hash(path: Path) -> str:
    h = hashlib.md5()
    with open(path, "rb") as f:
        h.update(f.read())
    return h.hexdigest()


def find_php_binary() -> str:
    """Auto-detect php.exe / php on PATH or common XAMPP paths."""
    for candidate in ["php", "php.exe"]:
        if shutil.which(candidate):
            return shutil.which(candidate)
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


def detect_ci_version(project_root: Path) -> str:
    """Read CI_VERSION from system/core/CodeIgniter.php or Common.php (CI 3.0.x fallback)."""
    for fname in ["CodeIgniter.php", "Common.php"]:
        ci_file = project_root / "system" / "core" / fname
        if not ci_file.exists():
            continue
        content = ci_file.read_text(encoding="utf-8", errors="replace")
        m = re.search(r"CI_VERSION\s*=\s*['\"]([^'\"]+)['\"]", content)
        if m:
            return m.group(1)
    return "Unknown (CI_VERSION not found)"


def detect_php_min_version(project_root: Path) -> str:
    """Try to read composer.json or .php-version for PHP requirement."""
    for fname in ["composer.json", ".php-version", ".php_version"]:
        f = project_root / fname
        if f.exists():
            content = f.read_text(errors="replace")
            m = re.search(r'"php"\s*:\s*"([^"]+)"', content)
            if m:
                return m.group(1)
            m = re.search(r'(\d+\.\d+)', content)
            if m:
                return m.group(1)
    return "Not specified"


# -------------------------------------------------------------------------------
# PHP Issue Scanner
# -------------------------------------------------------------------------------

def scan_php_issues(path: Path) -> list[dict]:
    """Scan a PHP file for PHP 8.x incompatibility issues."""
    issues = []
    try:
        content = path.read_text(encoding="utf-8", errors="replace")
        lines = content.splitlines()
        for label, pattern, severity, description in PHP8_CHECKS:
            for i, line in enumerate(lines, 1):
                if pattern.search(line):
                    issues.append({
                        "file": str(path),
                        "line": i,
                        "severity": severity,
                        "label": label,
                        "description": description,
                        "snippet": line.strip()[:120],
                    })
    except Exception as e:
        issues.append({
            "file": str(path),
            "line": 0,
            "severity": "ERROR",
            "label": "Read error",
            "description": str(e),
            "snippet": "",
        })
    return issues


def check_dynamic_properties(path: Path) -> list[dict]:
    """Check if a class uses dynamic properties without #[AllowDynamicProperties]."""
    issues = []
    try:
        content = path.read_text(encoding="utf-8", errors="replace")
        # Find class declarations
        class_blocks = re.finditer(
            r'(#\[AllowDynamicProperties\]\s*\n)?\s*(?:abstract\s+|final\s+)?class\s+(\w+)',
            content,
            re.MULTILINE
        )
        for m in class_blocks:
            has_attr = bool(m.group(1))
            class_name = m.group(2)
            # Check for $this->undeclared = anywhere after class start
            # Simple heuristic: does the class body contain dynamic assignments?
            class_pos = m.start()
            block_snippet = content[class_pos:class_pos + 3000]
            # Look for $this->x = where x isn't in declared properties
            declared = set(re.findall(r'(?:public|protected|private)\s+\$(\w+)', block_snippet))
            dynamic = re.findall(r'\$this->([a-zA-Z_]\w*)\s*=', block_snippet)
            undeclared = [d for d in dynamic if d not in declared]
            if undeclared and not has_attr:
                issues.append({
                    "file": str(path),
                    "line": content[:class_pos].count('\n') + 1,
                    "severity": "WARNING",
                    "label": "Dynamic property without #[AllowDynamicProperties]",
                    "description": f"Class `{class_name}` assigns dynamic properties: {', '.join(sorted(set(undeclared))[:5])}",
                    "snippet": f"class {class_name}",
                })
    except Exception:
        pass
    return issues


# -------------------------------------------------------------------------------
# PHP Syntax Checker
# -------------------------------------------------------------------------------

def php_syntax_check(php_bin: str, path: Path) -> dict:
    """Run `php -l <file>` and return result."""
    try:
        result = subprocess.run(
            [php_bin, "-l", str(path)],
            capture_output=True,
            text=True,
            timeout=10,
        )
        ok = result.returncode == 0
        output = (result.stdout + result.stderr).strip()
        return {"ok": ok, "output": output}
    except subprocess.TimeoutExpired:
        return {"ok": False, "output": "Timeout during syntax check"}
    except FileNotFoundError:
        return {"ok": None, "output": "php binary not found"}
    except Exception as e:
        return {"ok": False, "output": str(e)}


# -------------------------------------------------------------------------------
# Config Merger
# -------------------------------------------------------------------------------

def extract_config_keys(content: str) -> dict[str, str]:
    """Extract $config['key'] = value; assignments from CI config file."""
    keys = {}
    for m in re.finditer(
        r'\$config\s*\[\s*[\'"]([^\'"]+)[\'"]\s*\]\s*=\s*(.*?);',
        content,
        re.DOTALL
    ):
        keys[m.group(1)] = m.group(2).strip()
    return keys


def merge_config_file(source: Path, target: Path) -> tuple[bool, list[str]]:
    """
    Merge source config into target config.
    - Keys not in target: append them.
    - Keys already in target: skip and report conflict.
    Returns (modified, conflict_list).
    """
    src_content = source.read_text(encoding="utf-8", errors="replace")
    tgt_content = target.read_text(encoding="utf-8", errors="replace")

    src_keys = extract_config_keys(src_content)
    tgt_keys = extract_config_keys(tgt_content)

    conflicts = []
    new_lines = ["\n\n// --- Merged from: " + source.name + " ---"]
    added = 0

    for key, value in src_keys.items():
        if key in tgt_keys:
            if tgt_keys[key].strip() != value.strip():
                conflicts.append(f"  Conflict: $config['{key}'] -- source={value[:60]} | target={tgt_keys[key][:60]}")
        else:
            new_lines.append(f"$config['{key}'] = {value};")
            added += 1

    if added > 0:
        with open(target, "a", encoding="utf-8") as f:
            f.write("\n".join(new_lines) + "\n")
        return True, conflicts
    return False, conflicts


# -------------------------------------------------------------------------------
# File Migrator
# -------------------------------------------------------------------------------

def migrate_files(
    src_app: Path,
    tgt_app: Path,
    overwrite: bool,
    dry_run: bool,
    php_bin: str,
) -> dict:
    """
    Walk source application/ and copy files to target application/.
    Returns detailed stats dict.
    """
    stats = {
        "copied": [],
        "skipped": [],
        "merged_configs": [],
        "config_conflicts": [],
        "syntax_errors": [],
        "php8_issues": [],
        "patched": [],
        "errors": [],
    }

    all_php_files = []

    for subdir in APP_DIRS:
        src_dir = src_app / subdir
        tgt_dir = tgt_app / subdir

        if not src_dir.exists():
            continue

        for src_file in sorted(src_dir.rglob("*")):
            if src_file.is_dir():
                continue
            if src_file.name in ("index.html", ".htaccess", ".gitkeep"):
                continue

            rel = src_file.relative_to(src_app)
            tgt_file = tgt_app / rel
            is_php = src_file.suffix.lower() == ".php"

            # Scan for PHP 8.x issues regardless of migration
            if is_php:
                issues = scan_php_issues(src_file)
                issues += check_dynamic_properties(src_file)
                for iss in issues:
                    iss["context"] = "source"
                    stats["php8_issues"].append(iss)
                all_php_files.append(src_file)

            # Config merging
            is_mergeable = (
                subdir == "config"
                and src_file.name in MERGEABLE_CONFIGS
                and tgt_file.exists()
            )

            if is_mergeable:
                if not dry_run:
                    try:
                        modified, conflicts = merge_config_file(src_file, tgt_file)
                        if modified:
                            stats["merged_configs"].append(str(rel))
                        stats["config_conflicts"].extend(conflicts)
                    except Exception as e:
                        stats["errors"].append(f"Config merge error {rel}: {e}")
                else:
                    stats["merged_configs"].append(f"[DRY-RUN] {rel}")
                continue

            # Skip or overwrite logic
            if tgt_file.exists() and not overwrite:
                if is_php and file_hash(src_file) != file_hash(tgt_file):
                    stats["skipped"].append(str(rel) + "  [DIFFERS from target]")
                else:
                    stats["skipped"].append(str(rel))
                continue

            # Copy file (apply auto-patches to PHP files during copy)
            if not dry_run:
                try:
                    tgt_file.parent.mkdir(parents=True, exist_ok=True)
                    if is_php:
                        # Read, patch, write
                        content = src_file.read_text(encoding="utf-8", errors="replace")
                        patched_content, patch_log = apply_auto_patches(content, src_file)
                        tgt_file.write_text(patched_content, encoding="utf-8")
                        # Preserve file timestamps
                        shutil.copystat(src_file, tgt_file)
                        if patch_log:
                            stats["patched"].extend(patch_log)
                    else:
                        shutil.copy2(src_file, tgt_file)
                    stats["copied"].append(str(rel))
                except Exception as e:
                    stats["errors"].append(f"Copy error {rel}: {e}")
            else:
                if is_php:
                    # Preview which patches would apply
                    content = src_file.read_text(encoding="utf-8", errors="replace")
                    _, patch_log = apply_auto_patches(content, src_file)
                    if patch_log:
                        stats["patched"].extend([f"[DRY-RUN] {p}" for p in patch_log])
                stats["copied"].append(f"[DRY-RUN] {rel}")

    return stats, all_php_files


# -------------------------------------------------------------------------------
# Syntax Checker (runs on target after migration)
# -------------------------------------------------------------------------------

def run_syntax_checks(php_bin: str, target_root: Path) -> list[dict]:
    results = []
    if not php_bin:
        return results

    php_files = list((target_root / "application").rglob("*.php"))
    php_files += list((target_root / "system" / "core").glob("*.php"))

    print(f"  Running PHP syntax check on {len(php_files)} files...")

    for f in sorted(php_files):
        result = php_syntax_check(php_bin, f)
        if result["ok"] is False:
            rel = f.relative_to(target_root)
            results.append({"file": str(rel), "output": result["output"]})

    return results


# -------------------------------------------------------------------------------
# Report Generator
# -------------------------------------------------------------------------------

SEVERITY_PREFIX = {"ERROR": "[ERROR]", "WARNING": "[WARNING]", "INFO": "[INFO]"}


def generate_report(
    report_path: Path,
    src_root: Path,
    tgt_root: Path,
    src_version: str,
    tgt_version: str,
    src_php_req: str,
    stats: dict,
    syntax_errors: list[dict],
    dry_run: bool,
    php_bin: str,
) -> None:
    ts = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    mode = "DRY-RUN (no files actually changed)" if dry_run else "Live migration"

    # Group PHP 8.x issues by severity
    errors   = [i for i in stats["php8_issues"] if i["severity"] == "ERROR"]
    warnings = [i for i in stats["php8_issues"] if i["severity"] == "WARNING"]
    infos    = [i for i in stats["php8_issues"] if i["severity"] == "INFO"]

    lines = [
        "# CI3 Migration Report",
        "",
        f"> Generated: {ts}  ",
        f"> Mode: {mode}",
        "",
        "---",
        "",
        "## Project Info",
        "",
        f"| Field | Value |",
        f"|---|---|",
        f"| Source project | `{src_root}` |",
        f"| Target project | `{tgt_root}` |",
        f"| Source CI version | {src_version} |",
        f"| Target CI version | {tgt_version} |",
        f"| Source PHP requirement | {src_php_req} |",
        f"| PHP binary used | `{php_bin or 'not found'}` |",
        "",
        "---",
        "",
        "## Migration Summary",
        "",
        f"| Category | Count |",
        f"|---|---|",
        f"| Files copied | {len(stats['copied'])} |",
        f"| Files skipped (already exist) | {len(stats['skipped'])} |",
        f"| Config files merged | {len(stats['merged_configs'])} |",
        f"| Config key conflicts | {len(stats['config_conflicts'])} |",
        f"| Auto-patches applied | {len(stats['patched'])} |",
        f"| PHP syntax errors | {len(syntax_errors)} |",
        f"| PHP 8.x errors (after patch) | {len(errors)} |",
        f"| PHP 8.x warnings | {len(warnings)} |",
        f"| PHP 8.x infos | {len(infos)} |",
        f"| Migration errors | {len(stats['errors'])} |",
        "",
    ]

    # Copied files
    if stats["copied"]:
        lines += ["### Files Copied", "", "```"]
        lines += stats["copied"]
        lines += ["```", ""]

    # Skipped files
    if stats["skipped"]:
        lines += ["### Files Skipped (already exist in target)", "", "```"]
        lines += stats["skipped"]
        lines += ["```", ""]

    # Config merges
    if stats["merged_configs"]:
        lines += ["### Config Files Merged", "", "```"]
        lines += stats["merged_configs"]
        lines += ["```", ""]

    if stats["config_conflicts"]:
        lines += [
            "### Config Key Conflicts",
            "",
            "These keys exist in both source and target with **different values**.",
            "The target value was kept. Review and reconcile manually.",
            "",
        ]
        lines += stats["config_conflicts"]
        lines += [""]

    # Migration errors
    if stats["errors"]:
        lines += ["### Migration Errors", "", "```"]
        lines += stats["errors"]
        lines += ["```", ""]

    # Auto-patches
    if stats["patched"]:
        lines += [
            "### Auto-Patches Applied",
            "",
            "The following deprecated patterns were automatically fixed in the copied files:",
            "",
        ]
        lines += stats["patched"]
        lines += [""]
    else:
        lines += ["### Auto-Patches Applied", "", "> No deprecated patterns requiring auto-patch were found.", ""]

    # Syntax errors
    lines += ["---", "", "## PHP Syntax Check Results", ""]
    if not php_bin:
        lines += ["> WARNING: PHP binary not found -- syntax check skipped.", ""]
    elif not syntax_errors:
        lines += ["> All PHP files passed syntax check (php -l).", ""]
    else:
        lines += [f"> {len(syntax_errors)} file(s) have syntax errors:", ""]
        for se in syntax_errors:
            lines += [
                f"#### `{se['file']}`",
                "```",
                se["output"],
                "```",
                "",
            ]

    # PHP 8.x issues
    lines += ["---", "", "## PHP 8.x Compatibility Issues", ""]

    def issue_section(issue_list: list, heading: str) -> list[str]:
        if not issue_list:
            return []
        out = [heading, ""]
        # Group by file
        by_file: dict[str, list] = {}
        for iss in issue_list:
            by_file.setdefault(iss["file"], []).append(iss)
        for fpath, file_issues in sorted(by_file.items()):
            try:
                rel = str(Path(fpath).relative_to(src_root))
            except ValueError:
                rel = fpath
            out += [f"#### `{rel}`", ""]
            for iss in file_issues:
                prefix = SEVERITY_PREFIX.get(iss["severity"], "")
                out += [
                    f"- {prefix} **Line {iss['line']}** -- {iss['label']}",
                    f"  - *Fix:* {iss['description']}",
                ]
                if iss.get("snippet"):
                    out += [f"  - *Code:* `{iss['snippet']}`"]
            out += [""]
        return out

    lines += issue_section(errors,   "### Errors (will break on PHP 8.x)")
    lines += issue_section(warnings, "### Warnings (deprecated, may break)")
    lines += issue_section(infos,    "### Info (review recommended)")

    if not errors and not warnings and not infos:
        lines += ["> No PHP 8.x compatibility issues found in source files.", ""]

    # Quick-fix reference
    lines += [
        "---",
        "",
        "## Quick-Fix Reference",
        "",
        "| Issue | Fix |",
        "|---|---|",
        "| `create_function()` | Replace with `fn($x) => expr` or `function() {}` |",
        "| `each()` | Replace with `foreach ($arr as $k => $v)` |",
        "| `ereg()` | Replace with `preg_match('/pattern/', ...)` |",
        "| `mysql_*()` | Replace with `mysqli_*()` or PDO |",
        "| `mcrypt_*()` | Replace with `openssl_encrypt/decrypt()` |",
        "| `utf8_encode()` | Replace with `mb_convert_encoding($s, 'UTF-8', 'ISO-8859-1')` |",
        "| `FILTER_SANITIZE_STRING` | Replace with `htmlspecialchars()` or `strip_tags()` |",
        "| `mysqli_driver` class | Replace with `mysqli_report(MYSQLI_REPORT_OFF)` |",
        "| `mbstring.internal_encoding` ini_set | Replace with `mb_internal_encoding('UTF-8')` |",
        "| Dynamic properties | Add `#[AllowDynamicProperties]` or declare properties |",
        "| `get_magic_quotes_gpc()` | Remove call; always returns `false` on PHP 8 |",
        "| `0 == $string` | Change to `0 === $string` or use `intval()` |",
        "",
        "---",
        "",
        "*Report generated by `ci3_migrate.py`*",
    ]

    report_path.write_text("\n".join(lines), encoding="utf-8")
    print(f"\n  Report written to: {report_path}")


# -------------------------------------------------------------------------------
# Main
# -------------------------------------------------------------------------------

def main():
    parser = argparse.ArgumentParser(
        description="Auto-migrate a CI3 project's application files to a target project.",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog=__doc__,
    )
    parser.add_argument("source", help="Path to source CI3 project root")
    parser.add_argument(
        "--target", default=".",
        help="Target CI3 project root (default: current directory)"
    )
    parser.add_argument(
        "--php", default=None,
        help="Path to php binary (default: auto-detect)"
    )
    parser.add_argument(
        "--dry-run", action="store_true",
        help="Simulate without copying files"
    )
    parser.add_argument(
        "--overwrite", action="store_true",
        help="Overwrite existing files in target (default: skip)"
    )
    parser.add_argument(
        "--report", default="migration_report.md",
        help="Output report path (default: migration_report.md)"
    )
    parser.add_argument(
        "--no-syntax-check", action="store_true",
        help="Skip PHP syntax checking"
    )

    args = parser.parse_args()

    src_root = Path(args.source).resolve()
    tgt_root = Path(args.target).resolve()
    report_path = Path(args.report) if os.path.isabs(args.report) else tgt_root / args.report

    # -- Validate paths -------------------------------------------------------
    if not src_root.exists():
        print(f"ERROR: Source path does not exist: {src_root}")
        sys.exit(1)

    src_app = src_root / "application"
    tgt_app = tgt_root / "application"

    if not src_app.exists():
        print(f"ERROR: Source has no 'application/' directory: {src_app}")
        sys.exit(1)
    if not tgt_app.exists():
        print(f"ERROR: Target has no 'application/' directory: {tgt_app}")
        sys.exit(1)

    # -- PHP binary -----------------------------------------------------------
    php_bin = args.php or find_php_binary()
    if php_bin:
        print(f"  PHP binary: {php_bin}")
    else:
        print("  WARNING: PHP binary not found -- syntax check will be skipped")

    # -- Version detection ----------------------------------------------------
    src_version = detect_ci_version(src_root)
    tgt_version = detect_ci_version(tgt_root)
    src_php_req = detect_php_min_version(src_root)

    print(f"\n  Source CI version : {src_version}")
    print(f"  Target CI version : {tgt_version}")
    print(f"  Source PHP req    : {src_php_req}")
    print(f"  Dry-run mode      : {args.dry_run}")
    print(f"  Overwrite mode    : {args.overwrite}")

    # -- Version compatibility advisory ---------------------------------------
    def _parse_ver(v: str):
        """Parse a version string like '3.0.6' into a comparable tuple."""
        try:
            parts = [int(x) for x in re.findall(r'\d+', v)]
            return tuple(parts) if parts else (0,)
        except Exception:
            return (0,)

    src_ver = _parse_ver(src_version)
    tgt_ver = _parse_ver(tgt_version)

    if src_ver < (3, 0, 0):
        print("  WARNING: Source version appears to be CI 2.x or unknown.")
        print("           This script is designed for CI 3.x sources only.")
        print("           Proceed with caution and review all migrated files manually.")
    elif src_ver < (3, 1, 0):
        print(f"  NOTE: Source is CI {src_version} (pre-3.1.0).")
        print("        Layout is compatible. mimes.php / foreign_chars.php will be merged if present.")
        print("        Review config/mimes.php after migration -- MIME types expanded in 3.1.x.")

    if tgt_ver < (3, 1, 0):
        print(f"  WARNING: Target CI version ({tgt_version}) is older than 3.1.0.")
        print("           Consider upgrading the target system/ core to 3.1.13.")
    print()

    # --- Migrate files ---
    print("  Migrating application files...")
    stats, src_php_files = migrate_files(
        src_app, tgt_app,
        overwrite=args.overwrite,
        dry_run=args.dry_run,
        php_bin=php_bin,
    )
    print(f"    Copied   : {len(stats['copied'])}")
    print(f"    Patched  : {len(stats['patched'])} auto-fixes applied")
    print(f"    Skipped  : {len(stats['skipped'])}")
    print(f"    Merged   : {len(stats['merged_configs'])} configs")
    print(f"    Issues   : {len(stats['php8_issues'])} PHP 8.x patterns found in source")

    # --- Syntax check ---
    syntax_errors = []
    if not args.no_syntax_check and php_bin:
        syntax_errors = run_syntax_checks(php_bin, tgt_root)
        if syntax_errors:
            print(f"    [FAIL] {len(syntax_errors)} PHP syntax error(s) found in target!")
        else:
            print("    [OK] All PHP files passed syntax check")

    # -- Generate report ------------------------------------------------------
    print("\n  Generating report...")
    generate_report(
        report_path=report_path,
        src_root=src_root,
        tgt_root=tgt_root,
        src_version=src_version,
        tgt_version=tgt_version,
        src_php_req=src_php_req,
        stats=stats,
        syntax_errors=syntax_errors,
        dry_run=args.dry_run,
        php_bin=php_bin,
    )

    # -- Final summary --------------------------------------------------------
    errors = [i for i in stats["php8_issues"] if i["severity"] == "ERROR"]
    print("\n" + "="*60)
    print("  MIGRATION COMPLETE")
    print("="*60)
    print(f"  Files copied          : {len(stats['copied'])}")
    print(f"  Files skipped         : {len(stats['skipped'])}")
    print(f"  Config files merged   : {len(stats['merged_configs'])}")
    print(f"  Config conflicts      : {len(stats['config_conflicts'])}")
    print(f"  Auto-patches applied  : {len(stats['patched'])}")
    print(f"  PHP syntax errors     : {len(syntax_errors)}")
    print(f"  PHP 8.x errors        : {len(errors)}")
    print(f"  PHP 8.x warnings      : {len([i for i in stats['php8_issues'] if i['severity']=='WARNING'])}")
    print(f"  Report                : {report_path}")
    print("="*60)

    if errors or syntax_errors:
        sys.exit(2)  # Non-zero exit = issues found
    sys.exit(0)


if __name__ == "__main__":
    main()
