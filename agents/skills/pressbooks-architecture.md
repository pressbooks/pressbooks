# Pressbooks Architecture Skill

Use this skill when planning, implementing, or reviewing work in a Pressbooks repository.

## Focus
- WordPress Multisite realities
- service-oriented logic
- repository consistency
- existing Pressbooks patterns
- maintainable plugin and theme structure

## Rules
- inspect the current repo before proposing structure
- reuse existing patterns when practical
- keep hooks thin
- keep templates presentational
- prefer clear responsibilities
- avoid speculative abstractions
- respect network vs site-level boundaries

## Important Reminders
- books are individual sites in multisite
- some repositories use service providers and container patterns
- import/export work should inspect existing adjacent implementations first
- repository conventions matter more than idealized architecture

## Anti-Patterns
Avoid:
- introducing large new frameworks without strong reason
- mixing business logic into templates
- heavy refactors unrelated to the task
- designing for future flexibility that is not needed now