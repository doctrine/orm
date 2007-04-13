<code type='php'>
// export everything, table definitions and constraints

$manager->setAttribute(Doctrine::ATTR_EXPORT, Doctrine::EXPORT_ALL);

// export classes without constraints

$manager->setAttribute(Doctrine::ATTR_EXPORT, Doctrine::EXPORT_TABLES ^ 
                                              Doctrine::EXPORT_CONSTRAINTS);

// turn off exporting

$manager->setAttribute(Doctrine::ATTR_EXPORT, Doctrine::EXPORT_NONE);
</code>
