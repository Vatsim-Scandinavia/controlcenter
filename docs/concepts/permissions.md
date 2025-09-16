---
icon: material/shield-account
---

# Permissions and Groups

Control Center uses a flexible system of groups and areas to manage user permissions. This allows for global and granular control over different parts.

## Overview

Permissions are not assigned directly to users. Instead, users are assigned to one or more **groups**, and those groups are what grant permissions. For many administrative roles, these permissions are further scoped to a specific **area**.

## Key Concepts

### Groups

Groups are the primary way to define a set of permissions. The default groups in Control Center include:

- **Administrator**: Has unrestricted access to all features and all areas.
- **Moderator**: Has administrative permissions, but they are typically restricted to one or more specific areas.
- **Mentor**: Can manage training sessions, view student progress, and create tasks. Their permissions are also often scoped to an area.
- **Member**: The default group for all users, granting basic access to user-facing features like booking ATC slots.

### Areas

Areas are used to represent organizational units within your division, such as an ARTCC, a vACC, or a specific region. By assigning moderators or mentors to a specific area, you limit their administrative powers to only the users and resources (like positions) within that area.

## How it Works

When a user attempts to perform an action, the system checks two things:

1. Is the user in a group that has permission for this action?
2. If the action is area-specific (like editing a position), does the user's group membership apply to that area?

For example, a user who is a **Moderator** for "Area A" can edit positions in "Area A", but cannot edit positions in "Area B" unless they are also a moderator for "Area B".

System administrators are a special case, as their permissions bypass any area-specific checks.
