# ADR-005 — SACAA Remains the Authoritative Regulator

**Status:** ACCEPTED  
**Date:** 2026-09-09

## Context

The VMT UAS platform is a compliance-critical aviation system. Future contributors and coding agents must understand why this architectural rule exists.

## Decision

The VMT platform prepares, records, monitors and verifies operational compliance evidence. It does not issue SACAA certificates or represent itself as the regulator.

## Consequences

- Future features must preserve this rule.
- Any change that contradicts this decision requires a new ADR.
- The new ADR must explain why the previous decision is being superseded.
