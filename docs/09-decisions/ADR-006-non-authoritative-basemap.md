# ADR-006 — Do Not Treat Consumer Mapping Basemaps as Authoritative Airspace Data

**Status:** ACCEPTED  
**Date:** 2026-09-09

## Context

The VMT UAS platform is a compliance-critical aviation system. Future contributors and coding agents must understand why this architectural rule exists.

## Decision

Google Maps or similar services may be used for mapping context and user interaction, but authoritative airspace and regulatory restriction decisions require an appropriate authoritative data source.

## Consequences

- Future features must preserve this rule.
- Any change that contradicts this decision requires a new ADR.
- The new ADR must explain why the previous decision is being superseded.
