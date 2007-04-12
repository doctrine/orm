
<li>
<p>NOT, !
          </p>
<p>
            Logical NOT. Evaluates to 1 if the
            operand is 0, to 0 if
            the operand is non-zero, and NOT NULL
            returns NULL.
          </p>
<b class='title'>DQL condition :</b> NOT 10<br \>
        -&gt; 0<br \>
<b class='title'>DQL condition :</b> NOT 0<br \>
        -&gt; 1<br \>
<b class='title'>DQL condition :</b> NOT NULL<br \>
        -&gt; NULL<br \>
<b class='title'>DQL condition :</b> ! (1+1)<br \>
        -&gt; 0<br \>
<b class='title'>DQL condition :</b> ! 1+1<br \>
        -&gt; 1<br \>
</pre>
<p>
            The last example produces 1 because the
            expression evaluates the same way as
            (!1)+1.
          </p>
</li>
<li>
<p><a name="function_and"></a>
            <a class="indexterm" name="id2965271"></a>

            <a class="indexterm" name="id2965283"></a>

            AND
          </p>
<p>
            Logical AND. Evaluates to 1 if all
            operands are non-zero and not NULL, to
            0 if one or more operands are
            0, otherwise NULL is
            returned.
          </p>
<b class='title'>DQL condition :</b> 1 AND 1<br \>
        -&gt; 1<br \>
<b class='title'>DQL condition :</b> 1 AND 0<br \>
        -&gt; 0<br \>
<b class='title'>DQL condition :</b> 1 AND NULL<br \>
        -&gt; NULL<br \>
<b class='title'>DQL condition :</b> 0 AND NULL<br \>
        -&gt; 0<br \>
<b class='title'>DQL condition :</b> NULL AND 0<br \>
        -&gt; 0<br \>
</pre>
</li>
<li>


            OR
          </p>
<p>
            Logical OR. When both operands are
            non-NULL, the result is
            1 if any operand is non-zero, and
            0 otherwise. With a
            NULL operand, the result is
            1 if the other operand is non-zero, and
            NULL otherwise. If both operands are
            NULL, the result is
            NULL.
          </p>
<b class='title'>DQL condition :</b> 1 OR 1<br \>
        -&gt; 1<br \>
<b class='title'>DQL condition :</b> 1 OR 0<br \>
        -&gt; 1<br \>
<b class='title'>DQL condition :</b> 0 OR 0<br \>
        -&gt; 0<br \>
<b class='title'>DQL condition :</b> 0 OR NULL<br \>
        -&gt; NULL<br \>
<b class='title'>DQL condition :</b> 1 OR NULL<br \>
        -&gt; 1<br \>
</pre>
</li>
<li>
<p><a name="function_xor"></a>
            <a class="indexterm" name="id2965520"></a>

            XOR
          </p>
<p>
            Logical XOR. Returns NULL if either
            operand is NULL. For
            non-NULL operands, evaluates to
            1 if an odd number of operands is
            non-zero, otherwise 0 is returned.
          </p>
<b class='title'>DQL condition :</b> 1 XOR 1<br \>
        -&gt; 0<br \>
<b class='title'>DQL condition :</b> 1 XOR 0<br \>
        -&gt; 1<br \>
<b class='title'>DQL condition :</b> 1 XOR NULL<br \>
        -&gt; NULL<br \>
<b class='title'>DQL condition :</b> 1 XOR 1 XOR 1<br \>
        -&gt; 1<br \>
</pre>
<p>
            a XOR b is mathematically equal to
            (a AND (NOT b)) OR ((NOT a) and b).
          </p>
</li>
</ul>

