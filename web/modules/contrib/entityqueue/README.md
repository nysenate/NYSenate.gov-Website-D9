# CONTENTS OF THIS FILE

 - About Entityqueue
 - Entityqueue API

## ABOUT ENTITYQUEUE

The Entityqueue module allows users to create queues of any entity type. For
instance you can create a queue of:

 - Nodes
 - Users
 - Taxonomy terms

Entityqueue provides Views integration, by adding an entity queue relationship
to a view, and adding a sort by entity queue position.

## ENTITYQUEUE API

Entityqueue uses EntityQueueHandler plugins for each queue. When creating a
queue, the user selects the handler to use for that queue. Entityqueue provides
two handlers by default, "Simple queue" and "Multiple subqueues". Other modules
can provide their own handlers to alter the queue behavior.
