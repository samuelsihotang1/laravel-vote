<?php

namespace samuelsihotang1\LaravelVote\Tests;

use Illuminate\Database\Eloquent\Model;
use samuelsihotang1\LaravelVote\Traits\Votable;

class Post extends Model
{
  use Votable;

  protected $fillable = ['title'];
}
