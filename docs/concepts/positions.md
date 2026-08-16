---
icon: material/map-marker-radius
---

# Positions

The positions management page provides a centralized overview of all ATC positions. It is designed for administrative staff to define the positions that are available for booking by controllers.

!!! note "Current Limitations"
    The current version of the position management interface does not allow for viewing or editing endorsements (or tiers) required for a position. This must be managed directly in the database.

## Access and Permissions

Access is governed by the [roles and permissions system](permissions.md). Two permissions apply to this page:

| Permission | Grants |
| --- | --- |
| `fir.positions.view` | Read-only access to the positions overview. |
| `fir.positions.manage` | Creating, editing, and deleting positions. Also grants access to the overview. |

Both are evaluated **per area**. An area-scoped role assignment only covers positions in that area; an area-less assignment covers the whole division. In practice this means:

- The overview lists only positions in the areas you can access. There is no division-wide view unless you hold the permission globally.
- The **Area** selector when creating or editing a position is limited to the areas you can manage.
- Moving a position from one area to another requires `fir.positions.manage` in **both** areas.

Which roles hold these permissions is defined by the matrix in `config/roles.php`. In the shipped defaults `staff` gets view-only access, `nav-editor` and `moderator` may manage positions in their areas, and `director` and `admin` have full access. See the [Roles and Permissions Reference](../reference/permissions.md) for the complete role list and how to change it.

!!! note
    If you cannot edit a position you believe you should have access to, check that your role assignment covers that position's area, then contact a system administrator.

## The Positions Overview

The main page presents a comprehensive list of all configured positions. The table provides the following information:

- **Callsign**: The official callsign of the position (e.g., `ENBR_TWR`).
- **Name**: A descriptive name for the position (e.g., *Flesland Tower*).
- **Frequency**: The radio frequency used for the position (e.g. `119.100`).
- **FIR**: The Flight Information Region the position belongs to (e.g. *ENOR*).
- **Rating**: The minimum VATSIM controller rating required to staff the position (e.g. *S2*).
- **Area**: The administrative area the position is assigned to within the division (e.g. *Norway*).

The table is equipped with sorting and filtering controls for each column, allowing you to quickly find specific positions.

## Managing Positions

Position management is handled through intuitive modal dialogs for creating, editing, and deleting.

### Creating a Position

To add a new position:

1. Click the **Create Position** button at the top of the page.
2. Fill in the required details in the modal form:
   - **Callsign**: The position's callsign.
   - **Name**: The descriptive name.
   - **Frequency**: The frequency.
   - **FIR**: The four-letter ICAO code for the FIR.
   - **Rating**: Select the minimum required controller rating from the available options.
   - **Area**: Assign the position to an administrative area. Only the areas you can manage are selectable.
3. Click **Create** to save the new position.

### Editing a Position

To modify an existing position:

1. Find the position in the list.
2. Click the **Edit** button in the "Actions" column for that position.
3. The same modal will appear, pre-filled with the position's current data.
4. Make the necessary changes and click **Update**.

### Deleting a Position

To remove a position:

1. Click the **Delete** button in the "Actions" column.
2. A confirmation modal will appear to prevent accidental deletion.
3. Confirm the action by clicking **Delete**. The position will be permanently removed.

!!! warning
    Deleting a position is a permanent action and cannot be undone. All associated data may be lost.
