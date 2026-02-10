---
icon: material/map-marker-radius
---

# Positions

The positions management page provides a centralized overview of all ATC positions. It is designed for administrative staff to define the positions that are available for booking by controllers.

!!! note "Current Limitations"
    The current version of the position management interface does not allow for viewing or editing endorsements (or tiers) required for a position. This must be managed directly in the database.

    The current version does not allow for viewing or editing the endorsements themselves.

## Access and Permissions

Access to the positions overview is restricted to authorized staff members using the [system for Permissions and Groups](permissions.md).
The permissions for this page are structured as follows:

- **Administrators**: Have full access to view, create, edit, and delete all positions across all areas.
- **Area Moderators**: Can view all positions in the division. However, they can only create, edit, and delete positions that belong to the specific areas they manage.

This granular control ensures that position management is delegated effectively while maintaining administrative oversight.

!!! note
    If you are an Area Moderator and cannot edit a position you believe you should have access to, please contact a system administrator.

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
   - **Area**: Assign the position to an administrative area. As a moderator you can only assign the areas you manage.
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
