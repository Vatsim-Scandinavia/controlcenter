---
icon: material/comment-text-outline
---

# Feedback

Control Center includes an internal feedback channel for members to report on ATC sessions, plus a staff report where moderators and above can review — and, when permitted, correct — how feedback is correlated to controllers and positions.

This is separate from the **feedback URL** on an [area](../setup/division.md) row, which is an external link (for example a Google Form) included in training completion emails. The features on this page all live inside Control Center.

## Enabling feedback

Feedback is controlled under **Administration → Settings → Feedback**:

| Setting | Purpose |
| --- | --- |
| **Enable feedback functionality** | When off, members cannot open the submit form and are redirected away. |
| **Forward feedback to e-mail** | Optional. Sends a copy of each new submission to the configured address. |

Both settings are stored in the application settings store (`feedbackEnabled`, `feedbackForwardEmail`).

## Submitting feedback

Any signed-in member can submit feedback when the feature is enabled. The form is available from the main navigation and collects:

- **Controller** (optional) — VATSIM CID of the controller the feedback refers to
- **Position** (optional) — callsign of the position controlled
- **Feedback text** (required)

Submissions are stored with the submitter, optional controller/position references, and a timestamp. The feedback body itself is not editable after submission; staff may only change how the entry is **correlated** (controller and position fields).

## Viewing feedback

Staff with the right permissions can open **Reports → Feedback** (`/reports/feedback`). The table lists received entries with submitter, controller, position, area (derived from the position), and the feedback text.

Entries fall into two categories:

| Type | Meaning |
| --- | --- |
| **Correlated** | Linked to a position (`reference_position_id` is set). Area is taken from that position. |
| **Uncorrelated** | No position linked. Shown with area **N/A**. |

Which rows a user sees depends on **view** permissions and area scope (see below).

## Editing feedback

Moderators and other roles with `feedback.update` can correct the **controller** and **position** on an existing entry — for example when a member picked the wrong CID or callsign. The submitter, timestamp, and feedback text remain read-only.

### How editing works in the UI

On the feedback report, each row the user is allowed to update shows an **Edit** button. This opens a modal where staff can change:

- Controller (optional VATSIM CID)
- Position (optional callsign)

Saving runs server-side validation (controller must exist, position callsign must exist) and writes an activity-log entry describing what changed.

### What is logged

When controller or position values change, Control Center records an activity-log entry with category `feedback`, for example:

> Updated feedback 42 ― Controller: Jane Doe (123456) → N/A, Position: ESGG_APP → N/A

## Permissions

Feedback uses the [roles and permissions](permissions.md) system. Three permissions apply:

| Permission | Purpose |
| --- | --- |
| `feedback.correlated.view` | View feedback linked to a position. Scoped to the position's area unless the assignment is global. |
| `feedback.uncorrelated.view` | View feedback with no position linked. |
| `feedback.update` | Edit the controller and position fields on an entry. |

These permissions are defined in the catalogue in `config/roles.php`. They are granted to roles through the **matrix**, not assigned to users directly.

### Default role access

| Role | View correlated | View uncorrelated | Update |
| --- | --- | --- | --- |
| `admin` | Yes (all areas) | Yes | Yes (all entries) |
| `director` | Yes (all areas) | Yes | Yes (all entries) |
| `moderator` | Yes, in assigned area(s) | Yes | Yes, in assigned area(s) + uncorrelated |
| `mentor` | No | No | No |
| `buddy` | No | No | No |
| `nav-editor` | No | No | No |

Moderators receive all three permissions through the `feedback.**` pattern in the default matrix. Directors and administrators receive them through `**` (with the usual system-level exclusions for directors).

### How update checks resolve

Authorisation is enforced in `FeedbackPolicy`, the `UpdateFeedbackRequest` form request, and the feedback report view (`@can('update', …)`).

1. **Correlated feedback** (position is set)  
   The user must hold `feedback.update` for the **area of the linked position**. An area moderator in Area A can edit feedback tied to positions in Area A, but not Area B.

2. **Uncorrelated feedback** (no position)  
   The user must hold `feedback.update` **and** have access to `feedback.uncorrelated.view` (any area assignment that grants it is enough).

3. **Global assignments**  
   A global `admin`, `director`, or `moderator` assignment can update correlated feedback in any area and uncorrelated feedback.

Mentors, buddies, and members without a staff role cannot update feedback even if they can submit it.

### Customising access

To change who may view or edit feedback, edit `config/roles.php`:

1. Ensure the permission exists in the `permissions` catalogue.
2. Grant it to roles via patterns in the `matrix` block (for example add `feedback.update` to a custom role, or remove `feedback.**` from `moderator`).
3. Run `php artisan optimize:clear` so the new mapping is loaded.

See the [Roles and Permissions Reference](../reference/permissions.md) for the full matrix and customisation steps.

## Development and testing

After `migrate:fresh --seed` in a [development environment](../contribute.md), these seeded users can update feedback:

| User | CID | Role | Can update |
| --- | --- | --- | --- |
| Team Web | `10000010` | Admin (global) | All entries |
| Web Nine | `10000009` | Director (area 1) | All entries |
| Web Eight | `10000008` | Moderator (area 1) | Area 1 + uncorrelated |
| Web Six / Seven | `10000006` / `10000007` | Mentor | No |

Log in as one of the staff users above and open **Reports → Feedback** to exercise the edit flow.
