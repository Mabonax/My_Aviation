# ADR-002 — Derive Compliance State

**Status:** ACCEPTED  
**Date:** 2026-09-09

## Context

The VMT UAS platform is a compliance-critical aviation system. Future contributors and coding agents must understand why this architectural rule exists.

## Decision

Compliance must not be maintained as a manually editable boolean. It is derived from applicable requirements, evidence, validity dates, aircraft/pilot state and versioned regulatory rules.

## Consequences

- Future features must preserve this rule.
- Any change that contradicts this decision requires a new ADR.
- The new ADR must explain why the previous decision is being superseded.
