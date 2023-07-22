<?php

namespace samuelsihotang1\LaravelVote;

use samuelsihotang1\LaravelVote\Exceptions\UnexpectValueException;
use Stringable;

final class VoteItems implements Stringable
{
  /**
   * @var string
   */
  protected $value;

  public const UP = 'up_vote';

  public const DOWN = 'down_vote';

  public function __construct(string $value)
  {
    if (!in_array($value, self::getValues(), true)) {
      throw new UnexpectValueException("Unexpect Value: {$value}");
    }
    $this->value = $value;
  }

  /**
   * @return string[]
   */
  public static function getValues(): array
  {
    return [self::UP, self::DOWN];
  }

  /**
   * @return string
   */
  public function __toString()
  {
    return $this->value;
  }
}
