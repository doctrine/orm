[<b>Note</b>: The term 'Transaction' doesnt refer to database transactions here but to the general meaning of this term]<br />
[<b>Note</b>: This component is in <b>Alpha State</b>]<br />
<br />
Locking is a mechanism to control concurrency. The two most well known locking strategies
are optimistic and pessimistic locking. The following is a short description of these
two strategies from which only pessimistic locking is currently supported by Doctrine.<br />
<br />
<b>Optimistic Locking:</b><br />
The state/version of the object(s) is noted when the transaction begins.
When the transaction finishes the noted state/version of the participating objects is compared
to the current state/version. When the states/versions differ the objects have been modified 
by another transaction and the current transaction should fail.
This approach is called 'optimistic' because it is assumed that it is unlikely that several users
will participate in transactions on the same objects at the same time.<br />
<br />
<b>Pessimistic Locking:</b><br />
The objects that need to participate in the transaction are locked at the moment
the user starts the transaction. No other user can start a transaction that operates on these objects
while the locks are active. This ensures that the user who starts the transaction can be sure that
noone else modifies the same objects until he has finished his work.<br />
<br />
Doctrine's pessimistic offline locking capabilities can be used to control concurrency during actions or procedures
that take several HTTP request and response cycles and/or a lot of time to complete.
