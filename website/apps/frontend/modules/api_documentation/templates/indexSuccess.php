<?php
use_helper('ApiDocumentation');

$documentation = get_documentation($sf_request->getParameter('q'));

echo $documentation;