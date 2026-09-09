# ADR-003 — Store Regulatory Rules as Versioned Data

**Status:** ACCEPTED  
**Date:** 2026-09-09

## Context

The VMT UAS platform is a compliance-critical aviation system. Future contributors and coding agents must understand why this architectural rule exists.

## Decision

Compliance-critical rules must retain source, clause/reference, source version, effective date, applicability and status. Historical rules and fees must not be overwritten.

## Consequences

- Future features must preserve this rule.
- Any change that contradicts this decision requires a new ADR.
- The new ADR must explain why the previous decision is being superseded.
