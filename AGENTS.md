# AGENTS.md

## Purpose
This repository contains work related to Pressbooks development and may also include PB Lab experiments.
Agents and collaborators should use this file as the shared operating guide for understanding the project, making changes safely, and collaborating consistently.

This file is tool-neutral. It is intended to be useful for any AI coding assistant, editor integration, or human collaborator.

## Project Context
Pressbooks is a WordPress Multisite-based publishing platform for creating and distributing books in multiple formats, including web, EPUB, PDF, and XML-based exports.

The ecosystem includes:
- A core plugin: `pressbooks/`
- Multiple extension plugins: `pressbooks-*`
- Themes for the network and for books

Typical workspace characteristics:
- WordPress Multisite architecture
- PHP 8.3
- WordPress 6.8.3 or compatible current project target
- PSR-4 autoloading for namespaced code
- Mix of legacy and modern plugin structure
- Classic Editor usage in core product areas
- Some admin interfaces using Blade templates and service provider patterns

For more details read:
- `docs/engineering/pressbooks-development-context.md`
- the relevant role file in `/agents`
- the relevant PB Lab docs in `docs/pb-lab-google-docs-import/`

## Global Working Principles
- Prefer the smallest viable solution for the requested task.
- Do not expand scope without explicit agreement.
- Surface assumptions and uncertainties clearly.
- Match existing project conventions and structure.
- Favor maintainability over cleverness.
- Make surgical changes only.
- Do not refactor unrelated code unless explicitly requested.
- If a tradeoff exists, state it.
- If requirements are unclear, identify what is unclear instead of silently guessing.

## Collaboration Protocol
Unless instructed otherwise, the expected workflow is:

1. Clarify the task and identify assumptions.
2. Propose the smallest viable approach.
3. Implement one coherent slice at a time.
4. Review for correctness, maintainability, security, and consistency.
5. Test the implemented slice, including edge cases.
6. Document decisions, limitations, and follow-up work.

## Output Naming Convention
Use this naming pattern when practical:

`NN-short-description.md`

Examples:
- `01-initial-architecture-proposal.md`
- `02-slice-1-implementation-handoff.md`
- `03-review-slice-1.md`

If the work is tied to a specific slice, include the slice name in the filename.
If updating a previous output, create a new file instead of overwriting unless explicitly asked.

## PB Lab Rule

For PB Lab work, optimize for:
- shared understanding
- working vertical slices
- explicit tradeoffs
- small demoable increments

Do not attempt to solve the entire feature unless the team explicitly agrees on that scope.