---
icon: material/account-key
---

# Managing user access

Roles decide what a person can do in Control Center. You assign and remove them
from a user's profile, so there is no separate admin screen to hunt for. This page
walks through the everyday tasks: opening someone's access, granting a role,
granting the same role in several places at once, and removing a role again.

For the ideas behind roles, areas, and permissions, see
[Roles and Permissions](permissions.md). For the list of shipped roles and the
permission matrix, see the
[Roles and Permissions Reference](../reference/permissions.md).

## Who can do this

You need the right permissions to see and change access:

- To view a user's access, you need `users.access.view`.
- To grant or remove a role, you need `users.manage` plus the authority for that
  particular role (`roles.<role>.manage`) in the place you are granting it.

If you can open a user's profile but the Access card has no Add role button, your
account can view access but cannot change it. That is expected for view-only staff.

## Open a user's access

1. Go to Users and open the person you want to manage.
2. Find the Access card on their profile.

The card lists the roles the user already holds, grouped by where they apply.
Global roles come first, then one section per area. A role shown in colour is one
you are allowed to manage. A greyed-out role belongs to someone else's authority,
so you can see it but not change it.

## Grant a role

1. On the Access card, select Add role.
2. Under Select a role, pick the role you want to give. Only roles you are actually
   allowed to grant somewhere appear here, each with a short description.
3. Under Select the area(s) of responsibility, choose where the role applies:
    - Tick Global to grant it organisation-wide.
    - Tick one or more areas to grant it only there.
4. Select Grant.

The user gets the role straight away and the Access card updates to show it.

## Grant a role in several places at once

You do not have to repeat the process for each area. In step 3 above, tick every
area you want (and Global too, if it applies). The Grant button shows a count, for
example Grant x3, so you can see how many assignments you are about to create. One
click sets them all up.

## Why an option is greyed out

Inside the Add role dialog, an area or the Global option can appear disabled with a
short reason next to it:

- Already assigned. The user already holds this role there, so there is nothing to
  add.
- Not available for this role. The role's scope does not allow that kind of
  assignment. Some roles are area-only and cannot be granted globally, and some are
  global-only and cannot be pinned to a single area.
- You can't grant this here, or You can't grant this globally. You do not hold the
  authority for that role in that place. Someone with wider authority, such as a
  global director or admin, would need to grant it.

## Remove a role

1. On the Access card, find the role you want to take away.
2. Select the small remove button on that role.
3. Confirm in the dialog that appears.

The role is revoked immediately.

### Removing a mentor role

Mentor assignments are a special case because they are kept in sync with the
Division API. Two things are worth knowing:

- An area mentor role is labelled via Division API on the Access card, because it
  was granted through that integration.
- When you remove the last mentor role a person holds, Control Center detaches the
  trainings they teach in that area. The confirmation dialog spells out how many
  trainings that affects before you commit, so read it before selecting Remove.

## The admin role

You cannot grant or remove the `admin` role from this screen. It is deliberately
kept out of the UI and is managed only from the command line:

```sh
php artisan user:makeadmin
```

There is no command for revoking admin yet, so removing one means deleting the row
from the `role_user` table by hand. See
[Upgrading](../upgrade.md#admin-is-now-cli-only-with-a-new-director-role) for the
exact step.
