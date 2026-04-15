# Import Workflows Skill

Use this skill when planning, implementing, reviewing, or testing content import features.

## Core Principles
- imported content is often messy
- unsupported content should fail gracefully
- structure matters more than visual fidelity in early slices
- mapping rules should be explicit
- user feedback should be clear when import is partial or limited

## Planning Focus
When designing an import workflow, clarify:
- source format or API
- supported content types
- unsupported content types
- destination mapping
- sanitization and normalization rules
- user-facing feedback
- re-import expectations, if any

## Implementation Focus
Prefer a pipeline mindset, even if lightweight:
1. retrieve source
2. normalize content
3. map into destination structures
4. report unsupported or partial outcomes

Do not overengineer this pipeline during PB Lab work.

## Review Focus
Check:
- correctness of mapping
- safe handling of imported content
- behavior for unsupported elements
- clarity of user feedback
- maintainability of mapping logic

## Testing Focus
For import work, consider:
- empty source
- supported formatting
- mixed formatting
- unsupported elements
- malformed content
- larger documents
- partial success cases
- user-visible status and error states