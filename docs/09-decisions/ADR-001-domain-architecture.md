# ADR-001 — Use Domain/Application Architecture

**Status:** ACCEPTED  
**Date:** 2026-09-09

## Context

The VMT UAS platform is a compliance-critical aviation system. Future contributors and coding agents must understand why this architectural rule exists.

## Decision

Business and compliance rules must live in domain/application services. Controllers, routes and React/Inertia components remain thin orchestration/presentation layers.

## Consequences

- Future features must preserve this rule.
- Any change that contradicts this decision requires a new ADR.
- The new ADR must explain why the previous decision is being superseded.
