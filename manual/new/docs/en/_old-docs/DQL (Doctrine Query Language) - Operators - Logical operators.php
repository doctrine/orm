
* 


NOT, !
          



            Logical NOT. Evaluates to 1 if the
            operand is 0, to 0 if
            the operand is non-zero, and NOT NULL
            returns NULL.
          
<b class='title'>DQL condition :** NOT 10

        -&gt; 0

<b class='title'>DQL condition :** NOT 0

        -&gt; 1

<b class='title'>DQL condition :** NOT NULL

        -&gt; NULL

<b class='title'>DQL condition :** ! (1+1)

        -&gt; 0

<b class='title'>DQL condition :** ! 1+1

        -&gt; 1

</pre>



            The last example produces 1 because the
            expression evaluates the same way as
            (!1)+1.
          

* 


<a name="function_and"></a>
            <a class="indexterm" name="id2965271"></a>

            <a class="indexterm" name="id2965283"></a>

            AND
          



            Logical AND. Evaluates to 1 if all
            operands are non-zero and not NULL, to
            0 if one or more operands are
            0, otherwise NULL is
            returned.
          
<b class='title'>DQL condition :** 1 AND 1

        -&gt; 1

<b class='title'>DQL condition :** 1 AND 0

        -&gt; 0

<b class='title'>DQL condition :** 1 AND NULL

        -&gt; NULL

<b class='title'>DQL condition :** 0 AND NULL

        -&gt; 0

<b class='title'>DQL condition :** NULL AND 0

        -&gt; 0

</pre>

* 


            OR
          



            Logical OR. When both operands are
            non-NULL, the result is
            1 if any operand is non-zero, and
            0 otherwise. With a
            NULL operand, the result is
            1 if the other operand is non-zero, and
            NULL otherwise. If both operands are
            NULL, the result is
            NULL.
          
<b class='title'>DQL condition :** 1 OR 1

        -&gt; 1

<b class='title'>DQL condition :** 1 OR 0

        -&gt; 1

<b class='title'>DQL condition :** 0 OR 0

        -&gt; 0

<b class='title'>DQL condition :** 0 OR NULL

        -&gt; NULL

<b class='title'>DQL condition :** 1 OR NULL

        -&gt; 1

</pre>

* 


<a name="function_xor"></a>
            <a class="indexterm" name="id2965520"></a>

            XOR
          



            Logical XOR. Returns NULL if either
            operand is NULL. For
            non-NULL operands, evaluates to
            1 if an odd number of operands is
            non-zero, otherwise 0 is returned.
          
<b class='title'>DQL condition :** 1 XOR 1

        -&gt; 0

<b class='title'>DQL condition :** 1 XOR 0

        -&gt; 1

<b class='title'>DQL condition :** 1 XOR NULL

        -&gt; NULL

<b class='title'>DQL condition :** 1 XOR 1 XOR 1

        -&gt; 1

</pre>



            a XOR b is mathematically equal to
            (a AND (NOT b)) OR ((NOT a) and b).
          



