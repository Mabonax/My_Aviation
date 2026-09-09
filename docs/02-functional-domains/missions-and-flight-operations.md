# Missions, Compliance Gate & Flight Preparation

# 13. Mission & Flight Operations

## FR-MIS-001 — Mission Record

Store:

```text
Mission purpose
Client/project
Location
Coordinates
Mission polygon
Operation category
Aircraft
Pilot
Observers/crew
Date/time
Maximum altitude
Planned distance
VLOS/EVLOS/BVLOS
Day/night
Weather
Airspace assessment
Approvals
Risk assessment
Emergency arrangements
```

## FR-MIS-002 — Mission Lifecycle

Suggested lifecycle:

```text
Draft
Planning
Compliance Review
Awaiting Approval
Approved
Ready for Flight
In Progress
Completed
Post-flight Review
Closed
Cancelled
```

---

# 14. Pre-Flight Compliance Gate

Before operational release, evaluate applicable requirements such as:

```text
Pilot RPC
Required ratings/privileges
Medical requirement
Aircraft serviceability
Certificate of Registration
UASLA
UASOC
OpsSpec
Operations Manual
Aircraft/RPS manuals
Maintenance status
Insurance
Security status
Mission approvals
Risk assessment
Airspace requirements
```

Results:

```text
GREEN — Ready for operational approval
AMBER — Attention/review required
RED — Flight must not be released under configured rules
```

The system must distinguish a **regulatory prohibition** from an **internal organisational policy control**.

---

# 15. GIS & Airspace Mapping

## FR-GEO-001 — Mission Map

Support:

- mission polygon;
- take-off point;
- landing point;
- flight route;
- flight radius;
- location search;
- coordinates.

## FR-GEO-002 — Aviation Overlays

Where authoritative data is available, support:

- aerodromes;
- controlled airspace;
- restricted airspace;
- prohibited airspace;
- strategic areas;
- approved operating zones.

Google Maps or similar services may provide basemap and geolocation functionality but shall not automatically be treated as an authoritative aviation airspace source.

## FR-GEO-003 — Rule Evaluation

The compliance engine shall evaluate mission geometry against configured regulatory spatial rules and flag conditions requiring review or authorisation.

---

# 16. Pre-Flight and Post-Flight Checklists

Checklists shall be configurable and versioned.

Possible items:

```text
Aircraft condition
Propellers
Motors
Battery
RPS/controller
GNSS
C2 link
Firmware/configuration
Payload
Weather
Site security
Emergency landing area
Crew briefing
Third-party/public exposure
Permissions
```

Store performer, timestamp, checklist version, results and exceptions.

---
