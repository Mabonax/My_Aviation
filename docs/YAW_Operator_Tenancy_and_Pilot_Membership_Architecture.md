# YAW Operator Tenancy & Pilot Membership Architecture

## Purpose

This document defines how the YAW web application and mobile application
should relate, how operator tenancy should work, and how pilots, users,
operators, aircraft, missions, compliance records, and permissions
should be associated.

The recommended architecture is a **multi-tenant SaaS platform centred
on operators, while allowing pilots to maintain independent YAW
identities and professional profiles**.

The web application and mobile application should not be separate
deployments. They should be two interfaces into the same YAW backend,
API, database, identity system, and compliance engine.

------------------------------------------------------------------------

## 1. Core Architecture

``` text
                         YAW PLATFORM
                 Multi-tenant Laravel Backend
                    + API + Shared Database
                             |
              +--------------+--------------+
              |                             |
         YAW WEB APP                   YAW MOBILE APP
       Management / Admin              Field Operations
              |                             |
      Operator managers                 Pilots / Crew
      Compliance staff                  Mission activity
      Fleet managers                    Checklists
      Accountable persons               Defects
      YAW platform admin                Flight records
              |                             |
              +--------------+--------------+
                             |
                        YAW Identity
                             |
                    john@example.com
                             |
                         Pilot Profile
                             |
                 +-----------+-----------+
                 |                       |
             Operator A              Operator B
          Pilot / Captain         Contract Pilot
```

The key architectural principle is that **User**, **Pilot Profile**, and
**Operator Membership** are separate concepts.

-   **User** = authentication/login identity.
-   **Pilot Profile** = aviation/professional identity.
-   **Operator Membership** = relationship defining what a user/pilot
    may do within a specific operator.
-   **Role/Permission** = authority granted within that operator
    context.

A pilot profile should not permanently belong to a single operator.

------------------------------------------------------------------------

## 2. Recommended Product Model

YAW should operate as **one multi-tenant platform**, not a separate
Laravel installation for every operator.

Example:

``` text
YAW
|
+-- Drone Company Alpha
|   +-- 15 aircraft
|   +-- 8 pilots
|   +-- 2 compliance officers
|   +-- 1 operations manager
|   +-- 350 missions
|
+-- Survey Aviation (Pty) Ltd
|   +-- 6 aircraft
|   +-- 4 pilots
|   +-- 90 missions
|
+-- Delivery Drone SA
    +-- 120 aircraft
    +-- 30 pilots
    +-- 8,000 missions
```

All operators use the same YAW platform and application code, while
tenant isolation prevents one operator from accessing another operator's
operational information.

``` text
yaw.co.za
api.yaw.co.za

        |
        v

 Tenant Resolution

 Operator Alpha
 Operator Bravo
 Operator Charlie

        |
        v

 Same YAW Platform
```

------------------------------------------------------------------------

## 3. Pilot Registration Without an Operator

A pilot should be able to download YAW and register without already
belonging to an operator.

Registration should create:

``` text
User
 |
 v
Pilot Profile
```

The pilot can then maintain personal/professional information such as:

-   Pilot profile
-   RPC and ratings
-   Medical information and validity
-   Competency information
-   Credential expiry dates
-   Personal documents
-   Personal flight/logbook information
-   Notifications and expiry reminders

However, registration alone must **not** provide access to
operator-controlled operational resources.

An unassociated pilot should not automatically be able to:

-   View operator aircraft
-   Create missions under an operator
-   Release aircraft
-   View operator compliance records
-   View company-controlled documentation
-   Access another operator's fleet
-   Perform operational activities under an operator's approvals

The mobile application can represent this state as:

``` text
YAW Account
John Mabona

Pilot profile:     Active
RPC:               Valid
Operator:          Not linked
Operational role:  None

[ Join an Operator ]

You can maintain your pilot profile,
credentials and personal logbook.

Operator missions and aircraft become
available when you join an operator.
```

------------------------------------------------------------------------

## 4. Current Behaviour and Required Remediation

The current test flow demonstrates:

1.  User registers in Flutter.
2.  User appears in Laravel.
3.  Platform administrator activates the user.
4.  Flutter refreshes the authenticated session.
5.  Permissions become available.
6.  Aircraft become visible.

The effective access model currently resembles:

``` text
User
 |
 v
Role / Permission
 |
 v
Aircraft
```

This is acceptable for authentication/API integration testing, but it is
insufficient for production multi-tenancy.

The target model should be:

``` text
User
 |
 v
Pilot Profile
 |
 v
Operator Membership
 |
 v
Role within Operator
 |
 v
Operator-owned Resources
```

Operator-scoped resources include, where applicable:

``` text
Aircraft
Missions
Batteries
Defects
Maintenance
Compliance
Documents
Operations
GIS Projects
Flight Records
```

Activating a user account should therefore **not automatically make an
operator's aircraft visible**.

The backend must determine:

> Does this user have an active membership in the operator associated
> with this resource, and does the user's role within that operator
> permit the requested action?

Only when both conditions are satisfied should access be granted.

------------------------------------------------------------------------

## 5. Operator as the Primary Tenant

The **Operator** should be the principal operational tenant in YAW.

An operator tenant owns or controls its operational workspace,
including:

-   Organisational profile
-   Staff memberships
-   Pilot memberships
-   Fleet associations
-   Missions
-   Operational records
-   Compliance records
-   Defects
-   Maintenance workflows
-   Batteries
-   GIS projects
-   Reports
-   Internal documentation
-   Audit evidence

The operator tenant must remain logically isolated from every other
operator tenant.

------------------------------------------------------------------------

## 6. Operator Membership

YAW should introduce an explicit membership layer.

Conceptually:

``` text
operator_memberships
    id
    operator_id
    user_id
    pilot_id
    role
    status
    joined_at
    suspended_at
```

Possible statuses could include:

``` text
invited
pending
active
suspended
revoked
expired
```

Possible membership roles could include:

``` text
operator_admin
accountable_manager
operations_manager
compliance_officer
chief_pilot
pilot
contract_pilot
observer
maintenance
gis_specialist
```

The exact permission model can remain more granular than these roles.

------------------------------------------------------------------------

## 7. How a Pilot Joins an Operator

YAW should support multiple onboarding mechanisms that all create the
same underlying operator membership.

### 7.1 Operator Invitation

This should be the primary business onboarding workflow.

The operator administrator uses:

``` text
People -> Pilots -> Invite Pilot
```

The administrator enters an email address, YAW Pilot ID, or other
supported identifier.

The pilot receives an invitation such as:

``` text
Drone Company Alpha has invited you
to join its YAW organisation as Pilot.

[ Accept ]
[ Decline ]
```

Acceptance creates or activates:

``` text
User
 |
 v
Pilot
 |
 v
Operator Membership
    operator_id = 14
    pilot_id = 27
    role = pilot
    status = active
```

The pilot can then access resources belonging to Operator 14 according
to assigned permissions.

### 7.2 Pilot Requests to Join

The mobile application should also support:

``` text
Join Operator

Search operator
      OR
Enter organisation code
      OR
Scan QR code
```

Example:

``` text
Operator Code

YAW-4K8P2

Drone Company Alpha
Pretoria, Gauteng

[ Request to Join ]
```

The operator receives a membership request:

``` text
Membership Request

John Mabona
RPC: 123456
Requested role: Pilot

[ Review ]
[ Approve ]
[ Reject ]
```

Approval establishes the operator membership.

### 7.3 Existing Pilot Lookup

A pilot should not need a new YAW identity every time they work with a
different operator.

An operator should be able to locate an existing pilot using a stable
YAW identifier:

``` text
Pilot ID: YAW-P-000124
```

The operator can invite that existing pilot.

This allows:

``` text
John Mabona
Pilot Profile
      |
      +-- Operator Alpha
      |      Role: Pilot
      |
      +-- Operator Bravo
             Role: Contract Pilot
```

The pilot's professional profile remains canonical while each operator's
operational information remains isolated.

------------------------------------------------------------------------

## 8. Pilot Identity vs Operator Data

YAW should distinguish **pilot-owned professional information** from
**operator-owned operational information**.

### Pilot-level information

Examples:

-   Name and profile
-   YAW Pilot ID
-   RPC
-   Ratings
-   Medical validity
-   Competency records
-   Personal credential expiry information
-   Personal logbook
-   Career/professional history

### Operator-level information

Examples:

-   Operator role
-   Operator authorisations
-   Internal competency approval
-   Aircraft assignments
-   Operator missions
-   Mission release
-   Internal compliance
-   Defects
-   Maintenance records
-   Company documents
-   Operational reports
-   Internal audit evidence

A pilot leaving Operator Alpha should not lose their YAW identity or
personal professional history, but they should lose access to Alpha's
protected operational environment when the membership ends.

------------------------------------------------------------------------

## 9. Multiple Operator Memberships

YAW should support a pilot being associated with multiple operators.

Example:

``` text
Pilot
 |
 +-- Operator Alpha
 |      role = pilot
 |      status = active
 |
 +-- Operator Bravo
        role = contract_pilot
        status = active
```

The mobile and web applications should therefore maintain an **active
operator context**.

Example:

``` text
John Mabona

Operating as:
+---------------------------+
| Drone Company Alpha    v  |
+---------------------------+
```

Switching operator context changes the accessible operational dataset.

``` text
Active Operator
      |
      v
Operator Bravo
      |
      v
API Context
      |
      +-- Bravo Aircraft
      +-- Bravo Missions
      +-- Bravo Defects
      +-- Bravo Compliance
```

There must be no leakage of Operator Alpha's protected data into
Operator Bravo's context.

------------------------------------------------------------------------

## 10. Aircraft Ownership vs Operation

YAW should avoid assuming that the operator always owns an aircraft.

An aircraft could be:

``` text
Owned by:       Aircraft Leasing Company
Operated by:    Operator Alpha
Assigned to:    Pretoria Base
Mission:        Survey 2026-001
Pilot:          John Mabona
```

A relationship model is therefore preferable.

Conceptually:

``` text
operator_aircraft
    id
    operator_id
    aircraft_id
    relationship_type
    active_from
    active_until
```

Possible relationship types may include:

``` text
owned
leased
managed
contracted
temporarily_assigned
```

This allows YAW to evolve toward larger aviation and fleet-management
use cases.

------------------------------------------------------------------------

## 11. Web Application vs Mobile Application

The web and mobile applications are not separate operational systems.

Both should consume the same YAW API and backend services.

``` text
                    YAW API
                       |
          +------------+------------+
          |                         |
          v                         v
       Web App                  Mobile App
```

The applications provide different operational surfaces.

  Capability                    Web       Mobile
  ----------------------------- --------- ----------------
  Organisation administration   Primary   Limited
  Invite/manage pilots          Primary   Limited
  Fleet administration          Primary   View
  Regulatory compliance         Primary   View/status
  Mission planning              Yes       Yes
  Mission approval/release      Yes       Role dependent
  Pre-flight checklist          Yes       Primary
  Flight operations             Monitor   Primary
  Defect reporting              Yes       Primary
  Post-flight workflow          Yes       Primary
  Pilot credentials             Yes       Yes
  GIS project management        Primary   Field
  Reports/audit                 Primary   Limited

Actions performed on mobile become part of the same operator environment
visible on the web.

------------------------------------------------------------------------

## 12. Platform Administrator vs Operator Administrator

YAW requires a clear separation between **YAW Platform Administration**
and **Operator Administration**.

``` text
YAW Platform Administrator
        |
        +-- Tenant management
        +-- Subscriptions
        +-- Regulatory catalogue
        +-- Platform configuration
        +-- Support
        |
        v
Operator Administrator
        |
        +-- Manage pilots
        +-- Manage aircraft
        +-- Manage staff
        +-- Company compliance
        +-- Operations
        |
        v
Pilot / Crew
```

A YAW platform administrator may have cross-tenant administrative
capabilities for legitimate platform administration and support.

An Operator Administrator should only administer their own tenant.

In production, YAW/VMT should not need to manually activate every pilot
for every operator. Authorised operator administrators should manage
their own organisational memberships.

------------------------------------------------------------------------

## 13. API Tenancy Requirements

Current endpoints such as:

``` http
GET /api/v1/aircraft
```

must become tenant-aware.

The request should conceptually mean:

> Return aircraft accessible to the authenticated user within the user's
> currently selected operator context.

The API should not rely only on a global user permission.

A request should be evaluated against:

``` text
Authenticated User
        |
        v
Active Operator Context
        |
        v
Active Operator Membership
        |
        v
Role / Permission
        |
        v
Resource Tenant/Association
        |
        v
Authorise / Deny
```

This principle should apply to all tenant-controlled API resources.

------------------------------------------------------------------------

## 14. Suggested Authentication and Context Model

Authentication remains global to YAW:

``` text
POST /api/v1/auth/login
```

The authenticated identity can then expose available memberships:

``` text
GET /api/v1/me
GET /api/v1/me/pilot
GET /api/v1/me/operators
```

The client selects an active operator.

A tenancy mechanism should then ensure subsequent requests are evaluated
within that operator context.

The implementation could use an explicit operator identifier in an API
header, token/session context, or another carefully designed tenancy
mechanism.

Whatever implementation is selected, the backend must independently
verify that the authenticated user has an active membership in the
requested operator. Client-supplied operator identifiers must never be
trusted by themselves.

------------------------------------------------------------------------

## 15. Revised Mobile Onboarding Journey

The existing journey resembles:

``` text
Register
   |
   v
Activation
   |
   v
Home
```

The target journey should become:

``` text
Register
   |
   v
Verify / Activate Identity
   |
   v
Create or Link Pilot Profile
   |
   v
Pilot Workspace
   |
   +------------------------------+
   |                              |
No Operator                  Operator Invitation
   |                              |
   v                              v
Join/Search Operator          Accept Invitation
   |                              |
   +--------------+---------------+
                  |
                  v
          Operator Membership
                  |
                  v
             Role Assignment
                  |
                  v
        Operational Workspace
```

A pilot without an operator still has a useful YAW account, but
operator-controlled operational functionality remains unavailable until
membership is established.

------------------------------------------------------------------------

## 16. Commercial Model

This architecture allows YAW to serve both individual pilots and
organisations.

``` text
                 YAW PILOT
                  Free / Basic
                      |
          Profile / Credentials
          Expiries / Logbook
                      |
                      v
             Joins an Operator
                      |
                      v
             YAW OPERATIONS
               Paid Tenant
                      |
       Fleet / Missions / Compliance
       Maintenance / GIS / Reporting
                      |
                      v
              YAW ENTERPRISE
```

This creates two complementary acquisition paths:

1.  Operators procure YAW and onboard their pilots.
2.  Individual pilots already using YAW can later join participating
    operators.

Operators remain the primary commercial customers for operational
capabilities, while pilot identities can exist independently.

------------------------------------------------------------------------

## 17. Recommended Data Relationships

A simplified target relationship model:

``` text
User
 |
 +-- Pilot Profile
 |
 +-- Operator Memberships
        |
        +-- Operator A
        |      +-- Role
        |      +-- Permissions
        |
        +-- Operator B
               +-- Role
               +-- Permissions

Operator
 |
 +-- Memberships
 +-- Aircraft Associations
 +-- Missions
 +-- Compliance
 +-- Defects
 +-- Maintenance
 +-- Batteries
 +-- Documents
 +-- GIS Projects
 +-- Reports
```

The actual database implementation should preserve the modular monolith
architecture and domain boundaries already used by YAW.

------------------------------------------------------------------------

## 18. Tenant Isolation Requirements

Multi-tenancy must be enforced server-side.

At minimum:

1.  Every operator-controlled resource must be associated with an
    operator or have a resolvable operator relationship.
2.  API queries must be scoped by active operator context.
3.  Policies must check membership and tenant ownership/association.
4.  Route model binding must not permit cross-tenant resource access.
5.  Background jobs must retain tenant context.
6.  Exports must be tenant-scoped.
7.  Search results must be tenant-scoped.
8.  Audit events must record relevant operator context.
9.  Notifications must respect operator membership.
10. Tests must explicitly attempt cross-tenant access and verify denial.

The goal is:

``` text
Operator Alpha User
        X
        |
        +---- cannot access ----> Operator Bravo Resource
```

even when the user knows the database ID or URL of the resource.

------------------------------------------------------------------------

## 19. Recommended Next Remediation Phase

Before additional operational functionality becomes deeply coupled to
the existing global permission model, YAW should implement:

# Operator Tenancy & Membership

Recommended scope:

-   Operator tenant context
-   Operator membership domain
-   Operator invitation workflow
-   Pilot join-request workflow
-   Membership approval/rejection
-   Membership suspension/revocation
-   Multi-operator membership support
-   Active operator selection
-   Tenant-aware API middleware/context
-   Tenant-scoped policies
-   Tenant-scoped aircraft
-   Tenant-scoped missions
-   Tenant-scoped defects
-   Tenant-scoped compliance
-   Tenant-scoped GIS projects
-   Tenant-aware audit events
-   Web operator switcher
-   Flutter operator switcher
-   Flutter unassociated-pilot state
-   Cross-tenant security tests
-   Platform Admin vs Operator Admin separation

------------------------------------------------------------------------

## 20. Target Principle

The target architecture can be summarised as:

> **YAW is one multi-tenant aviation platform where pilots own their YAW
> identity and professional profile, operators own their operational
> environments, and an explicit operator membership connects the two.**

Registration creates the identity.

Pilot onboarding establishes the professional profile.

Operator membership grants access to an organisational environment.

Roles and permissions determine what the user can do inside that
operator.

Tenant isolation determines which organisation's data the user can
access.

This architecture allows YAW to support individual pilots, small
operators, large fleet operators, contract pilots, multiple
organisational relationships, and future enterprise aviation use cases
without requiring separate application deployments for each customer.
