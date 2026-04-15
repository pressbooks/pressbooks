# PB Lab Brief: Google Docs Import

<!-- This file must be revised and completed at the beginning of the session with the defined scope. -->

## Problem
Pressbooks currently supports multiple import formats, including EPUB, DOCX/ODT, and XML-based options.
Some existing import flows are not as reliable or pleasant as desired.
There is interest in exploring a Google Docs import workflow so users can bring content from Google Docs directly into a Pressbooks book.

## PB Lab Goal
Use the PB Lab session to design and implement the smallest viable Google Docs import slice that demonstrates real user value and technical feasibility.

This is a timeboxed collaborative lab, not a full production launch.

## Desired Outcome
By the end of the PB Lab, the team should ideally have:
- a shared understanding of the feature scope
- an agreed MVP slice
- a documented architecture direction
- a working implementation of at least one demoable vertical slice
- notes on risks, limitations, and follow-up work

## Candidate User Value
A Pressbooks user should be able to import content from a Google Doc into a book with reasonable structure and formatting preserved.

## Candidate MVP
The MVP should be discussed and finalized during kickoff, but a reasonable candidate is:

- authenticate or otherwise connect to a Google Doc source
- select or identify a document
- import basic content into Pressbooks
- preserve a limited set of structure and formatting, such as:
  - headings
  - paragraphs
  - bold and italic
  - links
  - bulleted and numbered lists

## Likely Out of Scope for the PB Lab
Unless explicitly agreed during kickoff, assume these are out of scope:
- comments
- suggestions / tracked changes
- collaborative sync
- ongoing two-way sync
- advanced tables
- embedded drawings
- complex layout fidelity
- perfect parity with Google Docs formatting
- every document type edge case

## Key Product Questions for Kickoff
The team should align on these early:
1. What is the exact import entry point in the UI?
2. How does the user identify the Google Doc?
3. What content types must be preserved for the lab demo?
4. Where does imported content land in Pressbooks?
5. Is import one-shot only, or is re-import behavior relevant yet?
6. How should unsupported content be communicated to the user?
7. What is the demo definition of success?

## Technical Questions for Kickoff
1. Which integration approach should be used?
   - Google Docs API
   - export as HTML
   - other intermediate format
2. What authentication approach is feasible for the lab?
3. How will document structure map into Pressbooks content structures?
4. What sanitization and normalization rules are required?
5. What test strategy is realistic in the lab timebox?

## Success Criteria for the PB Lab
A successful PB Lab outcome should include:
- one agreed architecture direction
- one implemented vertical slice
- documented assumptions and limitations
- enough clarity to continue the feature after the lab

## Non-Goals
The PB Lab does not need to:
- fully productionize the feature
- solve every unsupported formatting case
- finalize all long-term UX decisions
- build complete observability, scaling, or synchronization features

## Working Principle
Prefer a small, real, reviewable slice over a broad but shallow prototype.