# Translations

## Supported languages

`language/en-GB.ini` is the canonical file. It is translated into el-GR, fr-FR, de-DE, es-ES, it-IT, pt-PT
and sv-SE (`language/<lang>.ini`). Every translation keeps the en-GB key order, comments and blank lines, and
translates only the values. A translator's credit header above the copied en-GB header is allowed (sv-SE has one). `DATE_FORMAT_*` values are PHP `date()` format strings; the machine translations
leave them as in en-GB.

**Why:** AWF is used by Akeeba Solo and Akeeba Backup for WordPress, which ship in these languages. The
framework strings must be available in the same set of languages as the application.

**How to apply:** Keep the language list the same as the application's. When a language is added to the
application, add the AWF translation too.

## Terminology follows the Akeeba Solo glossaries

Wording and register follow the per-language glossaries in the sibling Akeeba Solo checkout,
`../solo/build/glossaries/<lang>.md` (relative to this repository's parent directory), and the application
translations in `../solo/app/languages/akeebabackup/<lang>/<lang>.ini`. Several `AWF_*` keys also exist in the
application's language files. Where their English text is the same, reuse the application's translation.

**Why:** Users see framework and application strings side by side. Different terms for the same thing (for
example "Save", "Cancel" or "Error") or a different register (de-DE uses formal "Sie") look inconsistent.

**How to apply:** Before translating, read the glossary for that language and look the term up in the
application's language file.

## Quote rules

In the translation files every value is wrapped in double quotes. (en-GB is mostly unquoted, so the default
`parse_ini_file()` fails on it at the `fopen()` line; read its keys with `INI_SCANNER_RAW`.) Inside a value, a double quote is always written `\"`: never a bare
`"`, and never `"_QQ_"`. An apostrophe is always a plain `'`: never `''`, and never `\'`.

**Why:** The loader ([`src/Text/Language.php`](../../src/Text/Language.php)) turns `\"` (and the legacy `"_QQ_"`) into `"` before it
parses the file. Standard INI tools do not read a bare `"` correctly: PHP's `parse_ini_file()` silently
drops it, so the validation below no longer sees the real text. `''` and `\'` are not unescaped by the
loader, so the user sees them literally. Without the wrapping quotes, `parse_ini_file()` rejects values
containing `(` or `)`, and turns bare `No`/`Off` into empty strings.

**How to apply:** After any change, run `grep -nF -e "''" -e "\\'" language/*.ini`; it must print nothing.
Also check that `php -r '$r=@parse_ini_file("language/xx-XX.ini"); echo $r===false?"FAIL":count($r),"\n";'`
prints the en-GB key count.

## An en-GB key change is translated in the same piece of work

When an en-GB key is added, removed or reworded, update every translation in the same piece of work. Every
file must have exactly the en-GB keys, and the same placeholders (`%s`, `%d`, `%1$s`) and HTML on every key.

**Why:** A missing key shows the raw key name, or English text, inside an otherwise translated interface.
A placeholder mismatch can break `sprintf()` at runtime.

**How to apply:** Diff each file's keys and per-key placeholders against en-GB before you finish.
