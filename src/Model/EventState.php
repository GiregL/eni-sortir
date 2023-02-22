<?php

namespace App\Model;

/**
 * Event states
 */
class EventState
{
    private $identifier;

    private function __construct(string $identifier)
    {
        $this->identifier = $identifier;
    }

    private static $_creating;
    private static $_open;
    private static $_closed;
    private static $_canceled;
    private static $_ongoing;
    private static $_finished;
    private static $_archived;

    public static function getCreating(): self
    {
        return self::createOrReturn(self::$_creating, "CREATING");
    }

    public static function getOpen(): self
    {
        return self::createOrReturn(self::$_open, "OPEN");
    }

    public static function getClosed(): self
    {
        return self::createOrReturn(self::$_closed, "CLOSED");
    }

    public static function getCanceled(): self
    {
        return self::createOrReturn(self::$_canceled, "CANCELED");
    }

    public static function getOngoing(): self
    {
        return self::createOrReturn(self::$_ongoing, "ONGOING");
    }

    public static function getFinished(): self
    {
        return self::createOrReturn(self::$_finished, "FINISHED");
    }

    public static function getArchived(): self
    {
        return self::createOrReturn(self::$_archived, "ARCHIVED");
    }

    /**
     * Initializes the static instances
     * @param $variable self Variable
     * @param $identifier string Identifier
     * @return self The variable
     */
    private static function createOrReturn(?self $variable, string $identifier): self
    {
        if (!$variable) {
            $variable = new EventState($identifier);
        }
        return $variable;
    }

    /**
     * Published state
     */
    public static function isPublished(self $eventState): bool
    {
        return $eventState == self::getOpen() || $eventState == self::getClosed();
    }

    /**
     * Returns all the possible states.
     */
    public static function values(): array
    {
        return [
            self::getCreating(),
            self::getOpen(),
            self::getClosed(),
            self::getOngoing(),
            self::getCanceled(),
            self::getFinished(),
            self::getArchived()
        ];
    }

    /*
     * -----------------------------------------------------------------------------------------------------------------
     */

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public static function fromValue(string $value): self
    {
        switch ($value) {
            case "CREATING":
                return self::getCreating();
            case "OPEN":
                return self::getOpen();
            case "CLOSED":
                return self::getClosed();
            case "CANCELED":
                return self::getCanceled();
            case "ONGOING":
                return self::getOngoing();
            case "FINISHED":
                return self::getFinished();
            case "ARCHIVED":
                return self::getArchived();
            default:
                throw new \RuntimeException("Valeur introuvable pour l'enum EventState: {$value}");
        }
    }
}