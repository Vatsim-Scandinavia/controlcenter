---
icon: material/shield-account
---

# Roles and Permissions

Control Center grants access through **roles**, **areas**, and a configurable **permission matrix**. This page covers how those pieces fit together; for the default roles, the shipped matrix, and how to customise them, see the [Roles and Permissions Reference](../reference/permissions.md).

## The Three Layers

The system has three layers, intentionally separated so an operator can shift policy without changing code:

1. **Roles** describe what kind of contributor a user is — administrator, moderator, mentor, and so on. A role is an identifier you can assign to a user, optionally inside an area.
2. **Permissions** describe individual capabilities — `manage-positions`, `view-management-reports`, and the like. They are not assigned to users directly; they are defined in a **matrix** that maps each permission to the roles that may exercise it.
3. **Areas** scope a role assignment to part of the division (an ARTCC, a vACC, a sector group). The same user can hold the same role in several areas at once, in a single area, or globally.

Both the role list and the matrix live in `config/roles.php`. Granting a role to a new permission, retiring a role, or introducing a new role is a configuration change, not a code change or migration.

## How a Check Resolves

Authorisation asks two questions when checking a permission:

1. **Does the user hold a role that grants this permission?** The matrix is consulted to find which roles satisfy it.
2. **Does at least one of those role assignments cover the relevant area?** If the action targets an area, the assignment must either be in that area or be a global one. Area-agnostic actions only need the role somewhere.

The matrix is authoritative: a permission that is not listed grants no role — even `admin`. The familiar "administrators can do anything" behaviour is a **per-policy convention**, implemented as a `before` hook in individual policy classes (for example `PositionPolicy::before()` returns `true` when the user holds `admin`). Resources whose policies install that hook let admins through regardless of the matrix; gates and policies without it are bound strictly by it.

### Example

> A user holds `nav-editor` in Area A.
>
> - Editing a position in **Area A** — allowed: `manage-positions` includes `nav-editor`, and the assignment matches the position's area.
> - Editing a position in **Area B** — denied: the role assignment does not cover Area B.
> - Moving a position from **Area A to Area B** — denied unless the user also holds `nav-editor` (or another role granting `manage-positions`) in Area B.

## Next Steps

- The [Roles and Permissions Reference](../reference/permissions.md) lists every shipped role, the full default permission matrix, and the steps to customise them.
- `config/roles.php` is the source of truth in the codebase.
