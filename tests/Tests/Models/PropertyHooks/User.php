<?php
// phpcs:ignoreFile
namespace Doctrine\Tests\Models\PropertyHooks;

use DateTime;

class User
{
    public string $first {
        set {
            if (strlen($value) === 0) {
                throw new ValueError("Name must be non-empty");
            }
            $this->first = $value;
        }
    }

    public string $last {
        set {
            if (strlen($value) === 0) {
                throw new ValueError("Name must be non-empty");
            }
            $this->last = $value;
        }
    }

    public string $fullName {
        // Override the "read" action with arbitrary logic.
        get => $this->first . " " . $this->last;

        // Override the "write" action with arbitrary logic.
        set {
            [$this->first, $this->last] = explode(' ', $value, 2);
        }
    }

    public string $language = 'de' {
        // Override the "read" action with arbitrary logic.
        get => strtoupper($this->language);

        // Override the "write" action with arbitrary logic.
        set {
            $this->language = strtolower($value);
        }
    }
}
