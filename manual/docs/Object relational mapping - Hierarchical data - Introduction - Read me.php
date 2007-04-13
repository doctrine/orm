

If performing batch tree manipulation tasks, then remember to refresh your records (see record::refresh()), as any transformations of the tree are likely to affect all instances of records that you have in your scope.



You can save an already existing node using record::save() without affecting it's position within the tree. Remember to never set the tree specific record attributes manually.



If you are inserting or moving a node within the tree, you must use the appropriate node method. Note: you do not need to save a record once you have inserted it or moved it within the tree, any other changes to your record will also be saved within these operations. You cannot save a new record without inserting it into the tree.



If you wish to delete a record, you MUST delete the node and not the record, using $record->deleteNode() or $record->getNode()->delete(). Deleting a node, will by default delete all its descendants. if you delete a record without using the node::delete() method you tree is likely to become corrupt (and fall down)!



The difference between descendants and children is that descendants include children of children whereas children are direct descendants of their parent (real children not gran children and great gran children etc etc).