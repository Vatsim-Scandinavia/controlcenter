---
#icon: fontawesome/solid/book
#icon: material/book-open-page-variant
icon: material/book-open-page-variant
---

# Training

The training functionality in Control Center is designed to cater to the needs of different VATSIM users, providing a comprehensive view and management tools for training sessions.
This guide details the functionalities available to all ATC members, mentors, and Training Directors.

## For All ATC Members

### Training requests

As a member of the VATSIM division using Control Center, you can apply for ATC training [from the dashboard][dashboard].

### Training details

Open one of your trainings to see everything recorded about it:

- The current status, such as *In Queue*, *In Progress*, or *Completed*.
- The training type: Standard, Refresh, or Transfer.
- The ratings the training covers under **Level**, each with the date it was signed off once your mentor gets there.
- The scheduled training period.
- Your training reports, once a mentor has written them.

### Minimum activity levels

Members with an active training request *In Queue* or in *Pre-Training* receive an email to confirm their continued interest in receiving ATC training every month, with a follow-up reminder after a week.

!!! warning
    Failing to confirm your interest within two weeks will result in training requests being closed.
    How expired training requests are handled is up to each individual VATSIM division.
    If you have questions regarding a training request of your own, you must contact your division.

## For Mentors

Mentors can do everything an ATC member can, and can also keep the record of a training they mentor up to date.

### Comment on a training

Add a comment to the training timeline to explain where the training stands, such as a temporary postponement or a student's absence.

### Write reports

Write a training report for a session, and an exam report for an examination attached to the training.

## For Training Staff

Training Directors complete and close trainings.
Training staff and area moderators can do the same within their own area.
Mentors cannot, even for a training they mentor.
See [Roles and Permissions][permissions].

### Sign off part of a training

A training can cover more than one rating, such as S1 + S2.
Because a student is examined and awarded one rating at a time, you can sign off each rating as the student earns it and leave the training open for the rest.

The **Complete training** menu appears on the training page when the training is *In Progress* and covers more than one rating.
It holds **Complete partial training** whenever signing off a rating would still leave something outstanding.

1. Open the training and select **Complete training**, then **Complete partial training**.
2. Check the rating shown. Control Center offers the lowest rating still outstanding, because a student earns S1 before S2.
3. Select **Complete S1**, or whichever rating is named.

Control Center then:

- Marks that rating complete under **Level**, with the date you signed it off.
- Marks the student active for the training's area and starts a fresh [activity grace period][activity]. This follows the same rules as closing a training, so visitors and members outside your division are not affected.
- Adds an entry to the training timeline naming the rating and you as the author. Unlike a comment, the student sees this one on their own training, and it cannot be edited afterwards.

The training stays *In Progress* for the remaining ratings, and the menu offers the next one the next time you open the page.

!!! note "Older trainings show no per-rating dates"
    Trainings closed before per-rating sign-off existed show nothing under **Level** but the ratings themselves.
    Only the training's own closing date was recorded then.

### Complete a whole training

**Mark training as completed** is the menu's other entry, and it is always offered.
Selecting it closes the training, completes every rating still outstanding, grants the endorsements they carry, and emails the student a confirmation.

Use it when the student has finished everything the training covers, and there is no reason to sign the remaining ratings off one at a time.
Facility and tier ratings are never signed off part by part, because their endorsement is granted through the [Division API][vateud] when the training is completed as a whole.
A training whose outstanding ratings are all facility or tier ones therefore offers this entry alone.

When one rating is left, signing it off would finish the training anyway, so **Complete partial training** drops out of the menu and this entry names the rating it completes.

A training that covers a single rating has no completion menu.
Close it by setting the training status to *Completed*, and its rating still gets a completion date under **Level**.

However you get there, a training is closed and the student emailed exactly once.

### Close a training that was not finished

Set the training status to *Closed by staff*, *Closed by student*, or *Completed* as the case requires.
Every closing status emails the student, but only *Completed* grants the endorsements the ratings carry.

### Rating upgrades and the roster

When you sign off a rating, Control Center warns you if it finds no completed [rating upgrade task][tasks] for it.
**Mark training as completed** carries a matching warning, naming the VATSIM ratings it signs off with the training rather than part by part.
The warning is advisory and does not block you:

- Completing the upgrade task first is the normal order, since that is what adds the student to the Division roster.
- The warning can also appear for a rating that was upgraded, when the task predates Control Center recording which rating a request targeted. Sign off anyway if you know the upgrade was granted, inside or outside Control Center.

The student does not appear on Control Center roster views until VATSIM actually grants the rating.
This is deliberate, since they cannot control the position until they hold it.
Their place on the Division roster is protected while they wait, as described under [Roster Sync][roster-sync].

  [dashboard]: ./index.md
  [permissions]: ./permissions.md
  [tasks]: ./tasks.md
  [activity]: ../activity.md
  [vateud]: ../integrations/vateud.md
  [roster-sync]: ../integrations/vateud.md#roster-sync
