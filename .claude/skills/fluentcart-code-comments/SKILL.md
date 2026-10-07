---
name: fluentcart-code-comments
description: Keep code comments short across FluentCart core, FluentCart Pro and every FluentCart addon (Elementor, Divi, Bricks and others). Use it whenever you write or edit PHP, JS/JSX, Vue or CSS in any fluent-cart* repository, so new code doesn't get essay-length comments, and whenever the user asks to clean up, shorten, trim or review comments, reduce comment noise, or says comments are too long, too many or too AI-sounding.
---

# Code comments in FluentCart repos

Avoid unnecessarily large comments. Much of this code was written with AI agents, which leave essays: the history of a past bug, a restatement of the next line, the same reason in the class docblock, the method docblock and again inline. Every later session reads them, spends context on them and copies the style. Verbose comments also hide the few that matter, and unchecked comments can drift until they contradict the code.

Write comments like a senior engineer: short, plain, and only where they say something the code can't. Optimize for understanding, not a comment count or percentage reduction.

Applies to `fluent-cart`, `fluent-cart-pro` and every `fluent-cart-*` addon. Repo rules (a `CLAUDE.md`, the `translators:` hook) still apply on top.

## Rules

**Cut**
- History and narrative: "used to", "previously", "this fixes the bug where…", "the first version of this…". That belongs in the commit message.
- Comments that restate what the code plainly does.
- The same reason in several places. Say it once, as close to the code it explains as possible.
- Storytelling about worst cases. Keep the reason, drop the drama.
- Shouted emphasis in capitals, unless it states a real contract.
- Docblocks that add nothing over the signature. Keep `@param`/`@return` lines that carry type information.
- Task narration ("added for #163", "per the review"). Keep issue or documentation links when they explain an ongoing compatibility constraint, workaround, or non-obvious decision.

**Keep word for word**
- `translators:` comments, on the line directly above the call.
- Tool directives: `phpcs:ignore`, `phpcs:disable`, `eslint-disable`, `@codeCoverageIgnore`, `@noinspection` and similar.
- PHPUnit annotations: `@test`, `@dataProvider`, `@covers`, `@group`. PHPUnit reads them from docblocks.
- `@since`, `@param`, `@return`, `@var`, `@throws`, `@deprecated`.
- License/copyright notices, WordPress plugin headers, and build-significant annotations such as `/* @__PURE__ */` and bundler magic comments.
- Preserve the attachment and effective scope of protected comments, not just their text. A directive moved to another line or an annotation attached to another function can change behavior.

Keep hook docblocks above `apply_filters` / `do_action`; their prose may be tightened, but their tags and attachment stay.

**Keep, usually in 1–3 lines**
- A non-obvious *why*: a race condition, a security reason, a WordPress, PHP or builder quirk, why the obvious alternative doesn't work, an invariant the code relies on.
- When shortening a why, keep the consequence: what breaks without this code. "Mirror the JS: WordPress drops attributes missing here" beats "for consistency".
- Allow more detail when a security, concurrency, or financial invariant would otherwise lose essential meaning. Length alone is not a reason to remove a useful comment.

**Check accuracy before rewriting**
- Read the implementation and relevant callers. Confirm defaults, units, return values, side effects, and edge cases; don't preserve a claim just because it was already in a comment.
- If the intended behavior is unclear, flag the uncertainty rather than inventing an explanation or treating a possible code bug as the intended contract.
- Keep code examples consistent with the current API. Label illustrative or abbreviated snippets explicitly; don't present omitted text as a complete source quotation.

**Never touch**
- String literals, including user-facing messages and tool/schema descriptions. Those are code.
- HTML comments inside PHP view templates. They are page output.

**Style**
- Plain English, short sentences. No em-dash chains, no "Deliberately", "Notably", "Crucially", "Note that".
- Use the syntax of the language and section being edited: `//` for short PHP/JS/TS notes, `/** */` for their docblocks, `/* … */` in plain CSS, and `<!-- … -->` in Vue templates. In Vue script/style sections, follow the declared language. Match the file's indentation and preserve protected or output-bearing comments.
- Prefer a few useful lines. Put extended background in an existing relevant document when appropriate, while keeping the essential invariant beside the code.
- Explain why, not what. During authorized code work, prefer clear names and simpler structure over comments explaining confusing code. During a comment-only cleanup, flag those refactoring opportunities without changing code.

## Illustrative example (adapted from this codebase)

An abbreviated long comment inspired by `fluent-cart-divi-modules/resources/js/common/review-stars.js`; this is a style illustration, not an exact current source quotation:

```js
/**
 * The star icon and the five-star layout — shared because Product Rating and
 * the shop grid (Shop App) both build their stars in JS from raw figures
 * rather than fetching core's rendered HTML (Product Card, Carousel and
 * Related Products get that for free; a REST-built grid and a figures-only
 * preview endpoint do not). Extracted per the identical-code rule (CLAUDE.md
 * → Editor JS Architecture) after the same path and the same half-star
 * stacking turned up in both places.
 * … 14 more lines …
 */
```

The essential reasons can be expressed in three prose lines; verify the technical claims against the current implementation before using them:

```js
/**
 * Star icon and five-star row, shared by Product Rating and Shop App (both build stars in JS).
 * Same path as core's ReviewThreadMarkup::starSvg(); keep the fct-star-svg class, colours and clipping rely on it.
 * A half star is two stacked icons: .fct-star-half-empty under .fct-star-half-fill.
 */
```

## When writing or editing code

Apply the rules as you write. Before finishing a change, reread the comments you added or touched: each should explain something useful and accurate, usually in one to three lines. Omit comments that add no information; retain essential explanations even when longer. Don't rewrite comments in code you weren't asked to change; that's a cleanup pass (below), not a side effect.

## When asked to clean up comments

Change comments only, never code. Verify unchanged non-comment source and preserved protected comments. The current helper compares normalized output and cannot by itself prove a comment-only edit: TypeScript type changes, for example, can disappear during transpilation. Treat its result as supplementary evidence, and report verification gaps rather than claiming byte-identical code.

1. **Start clean.** A clean working tree on a new branch (e.g. `chore/comment-cleanup`), or an isolated git worktree if the checkout is busy.
2. **Survey.** Run `php <skill>/scripts/comment-survey.php <repo-root>` to list comment lines per directory and the 20 heaviest files. Inspect the candidates to exclude generated files, copied libraries, and parser/linter fixtures beyond the script's directory exclusions. Normally skip files with sparse comments (fewer than about 12 comment lines, or under 8% of the file). These are prioritization hints, not deletion quotas; retain useful comments regardless of length or density.
3. **Pilot, then wait.** Clean the one or two heaviest files. Show the user a few before/after examples and the diff stat, then stop until they approve the tone.
4. **Roll out.** Split the remaining files into batches of similar comment volume, keeping related directories together. For a large repo, give each batch to a parallel subagent with its exact file list (no file in two batches), these rules, the pilot file as the tone reference, the verification script to run on its own files before reporting, and an instruction not to run git commands that change state (they share one working tree).
5. **Flag, don't fix.** Reading this closely turns things up:
   - A comment that contradicts the code: rewrite it to match the code, and list it.
   - A misplaced prose-only docblock: move it to the code it explains, and list it. If it contains protected tags, functional annotations, or tool directives, preserve its attachment and flag it for a separate fix; do not move the whole docblock during cleanup.
   - A bug in the code: don't fix it in this pass. List it with `file:line` and the evidence; the user decides.
6. **Verify.** Run `bash <skill>/scripts/verify-comments.sh` with every changed file supplied as a separate quoted argument, including staged and unstaged changes. Compare the complete diff against the clean starting revision. Check protected comments' exact text, values, attachment, and effective scope; matching tag counts alone is insufficient. The helper's `OK` means normalized output matched, not that all source and metadata were preserved. New or unsupported files require separate verification; a skipped file or successful exit alone is not a pass. Then run the repo's test suite.
7. **Report.** Comment lines before → after per area as descriptive metrics (`php <skill>/scripts/comment-survey.php <repo-root> --compare <base-branch>`), the comments and docblocks corrected, any bugs found, and verification evidence or gaps. Don't commit until the user has reviewed. When committing, keep the comment cleanup and any bug fixes in separate commits so their scope can be reviewed independently.

The verification script covers PHP and JS/JSX/TS. For Vue, Svelte and CSS it asks for a manual check.
