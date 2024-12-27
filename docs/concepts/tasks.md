---
icon: material/format-list-bulleted-square
---

# Tasks

Tasks is a communication method which organises messages across different mentors and training staff, instead of relying on emails or other forms of communication.

## How to use

Tasks are tied the the training, and can be created by the "Request" button in the training view. You then choose the task type and who to send it to. The recipient will receive an email notification, and sender will receive a confirmation of the task being completed or rejected.

You may only send a task to one person.

### Type of tasks

- **Rating upgrade**: Request rating upgrade for a student
- **Solo Endorsement**: Request granting of solo endorsement
- **Theory Exam Access**: Request access to theory exam
- **Custom Request:** Free text

The true power of tasks is when it's also integrated with Division API's such as [VATEUD](..//integrations/vateud.md), where completing a task also forwards the needed API calls to process the request in Division and CERT.

When running tasks without an API, the completion of a task will simply mark it as completed, notify the sender and archive the task.

!!! tip "Quick add"
    When creating a task, there's up to three quick add buttons identified by a lightning bolt and the recipient's name. The quick feature automatically creates the suggestions based on who has a mentor or moderator role in the current area of training, and also historically who has received most of the tasks.