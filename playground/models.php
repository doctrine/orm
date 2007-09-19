<?php
Doctrine::loadModels('models');

$manager->setAttribute(Doctrine::ATTR_EXPORT, Doctrine::EXPORT_ALL);

$tables =   array('entity',
                  'entityReference',
                  'email',
                  'phonenumber',
                  'groupuser',
                  'album',
                  'song',
                  'element',
                  'error',
                  'description',
                  'address',
                  'account',
                  'task',
                  'resource',
                  'assignment',
                  'resourceType',
                  'resourceReference');

$conn->export->exportClasses($tables);

require_once('data.php');