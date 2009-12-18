<?php

namespace Doctrine\ORM;

/**
 * Exception thrown when an ORM query unexpectedly returns more than one result.
 * 
 * @author robo
 * @since 2.0
 */
class NonUniqueResultException extends ORMException {}