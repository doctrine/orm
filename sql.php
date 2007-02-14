// Sqlite cascading deletes

CREATE TRIGGER %s DELETE ON %s
    BEGIN
        DELETE FROM %s WHERE %s = %s;
    END;

// Sqlite AuditLog

CREATE TRIGGER %s UPDATE OF %s ON %s
    BEGIN
        INSERT INTO %s (%s) VALUES (%s) WHERE %s = %s;
    END;
CREATE TRIGGER %s DELETE ON %s
    BEGIN
        INSERT INTO %s (%s) VALUES (%s) WHERE %s = %s;
    END;
