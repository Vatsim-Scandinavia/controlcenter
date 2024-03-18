---
icon: material/clock-check
---

# Bookings

The booking functionality in Control Center is designed to streamline the ATC booking process, providing a transparent and efficient system for all members.
This feature is an integral part of the training, examination and event experience for the member controllers.

## General Information

- **No Time Restrictions**: Control Center does not enforce time limits on bookings; these are managed at the division or subdivision level.
- **Mutually Exclusive Tags**: The booking overview help ensure clarity and purpose in bookings with mutually exclusive tags.
- **Integration with VATSIM**: Our system seamlessly integrates with the [VATSIM ATC Bookings API](../integrations/vatsim.md), enhancing interoperability with other websites and ATC roster overviews.
- **Programmatic Bookings**: In addition to manual bookings, our system [supports automated bookings](../api.md), such as those created for event staffing through Discord bots.

## For All ATC Members

The booking feature in Control Center is accessible to all ATC members, offering flexibility and ease of use. Whether you are a beginner or an experienced controller, this system caters to your needs, allowing for effective management of your ATC sessions.

!!! tip "Using the booking system"
    Every ATC member of the division, local and visiting, is welcome and *encouraged* to use the booking system.

### Creating Bookings

Bookings can be created for any ATC position from the *Bookings* page.

- If booking for a position beyond your rating, it will be tagged as :material-book-open-variant: *Training*.
- Bookings within your rating are automatically tagged as :material-radio-tower: *Normal*.

### Viewing Bookings

You can view all bookings in the subdivision, with various filters available for a customized overview.
Bookings can be filtered by member, position, date and FIR.

### Booking Restrictions

- Members are restricted from making overlapping bookings for the same position.
- The choice of positions for booking is set by administrators and not customizable by general members.

### Special Bookings

- An :material-calendar: *Event* tag is available for bookings related to specific, announced events.
- All booking tags, such as *Training*, *Event* and *Exam* are visible to all members, ensuring transparency.

### Removing Bookings

As a member, you can delete your own bookings. Staff also have deletion privileges for all bookings.

## For Mentors and Staff

Mentors and staff play a pivotal role in managing and overseeing the booking system. They have enhanced capabilities that support the training and examination processes within our community.

- **Special Tag Creation**: The :fontawesome-solid-graduation-cap: *Exam* tag is a unique feature available for mentors and staff to denote examination sessions.
- **Management of Bookings**: Staff have comprehensive control over bookings, ensuring smooth operation and adherence to standards.
