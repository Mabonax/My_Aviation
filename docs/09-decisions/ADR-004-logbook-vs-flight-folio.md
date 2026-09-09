# ADR-004 — Separate Pilot Logbook and Aircraft Flight Folio

**Status:** ACCEPTED  
**Date:** 2026-09-09

## Context

The VMT UAS platform is a compliance-critical aviation system. Future contributors and coding agents must understand why this architectural rule exists.

## Decision

A flight may feed both records, but the pilot logbook and aircraft flight folio are separate regulated records with different purposes, ownership and histories.

## Consequences

- Future features must preserve this rule.
- Any change that contradicts this decision requires a new ADR.
- The new ADR must explain why the previous decision is being superseded.
