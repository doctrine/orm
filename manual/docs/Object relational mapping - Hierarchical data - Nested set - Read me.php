<p>The most effective way to traverse a tree from the root node, is to use the tree::fetchTree() method.
It will by default include the root node in the tree and will return an iterator to traverse the tree.</p>

<p>To traverse a tree from a given node, it will normally cost 3 queries, one to fetch the starting node, one to fetch the branch from this node, and one to determine the level of the start node, the traversal algorithm with then determine the level of each subsequent node for you.</p>