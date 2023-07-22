<?php

namespace samuelsihotang1\LaravelVote\Tests;

use Illuminate\Database\Eloquent\Model;
use samuelsihotang1\LaravelVote\Traits\Votable;
use samuelsihotang1\LaravelVote\Traits\Voter;

class User extends Model
{
  use Voter;
  use Votable;

  protected $fillable = ['name'];
}
