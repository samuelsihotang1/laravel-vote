<?php

namespace samuelsihotang1\LaravelVote\Tests;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use samuelsihotang1\LaravelVote\Events\Voted;
use samuelsihotang1\LaravelVote\Exceptions\UnexpectValueException;
use samuelsihotang1\LaravelVote\VoteItems;

class FeatureTest extends TestCase
{
  public function setUp(): void
  {
    parent::setUp();

    Event::fake();

    config(['auth.providers.users.model' => User::class]);
  }

  public function test_voteItems()
  {
    self::assertEquals('up_vote', (string)(new VoteItems('up_vote')));
    self::assertEquals('down_vote', (string)(new VoteItems('down_vote')));

    $invalidValue = 'foobar';
    $this->expectException(UnexpectValueException::class);
    $this->expectDeprecationMessage("Unexpect Value: {$invalidValue}");
    new VoteItems($invalidValue);
  }

  public function test_basic_features()
  {
    /** @var User $user */
    $user = User::create(['name' => 'samuelsihotang1']);
    /** @var Post $post */
    $post = Post::create(['title' => 'Hello world!']);

    $user->vote($post, VoteItems::UP);

    Event::assertDispatched(Voted::class, function ($event) use ($user, $post) {
      $vote = $event->vote;
      self::assertTrue($vote->isUp());
      self::assertFalse($vote->isDown());
      return $vote->votable instanceof Post
        && $vote->user instanceof User
        && $vote->user->id === $user->id
        && $vote->votable->id === $post->id;
    });

    self::assertTrue($user->hasVoted($post));
    self::assertTrue($user->hasUpVoted($post));
    self::assertFalse($user->hasDownVoted($post));

    self::assertTrue($post->isVotedBy($user));
    self::assertTrue($post->isUpVotedBy($user));
    self::assertFalse($post->isDownVotedBy($user));

    self::assertTrue($user->cancelVote($post));

    Event::fake();

    $user->vote($post, VoteItems::DOWN);
    Event::assertDispatched(Voted::class, function ($event) use ($user, $post) {
      $vote = $event->vote;
      self::assertFalse($vote->isUp());
      self::assertTrue($vote->isDown());
      return $vote->votable instanceof Post
        && $vote->user instanceof User
        && $vote->user->id === $user->id
        && $vote->votable->id === $post->id;
    });

    self::assertTrue($user->hasVoted($post));
    self::assertFalse($user->hasUpVoted($post));
    self::assertTrue($user->hasDownVoted($post));

    self::assertTrue($post->isVotedBy($user));
    self::assertFalse($post->isUpVotedBy($user));
    self::assertTrue($post->isDownVotedBy($user));

    /** @var User $user */
    $user = User::create(['name' => 'samuelsihotang1']);
    /** @var Post $post */
    $post = Post::create(['title' => 'Hello world!']);
    $user->vote($post, VoteItems::UP);
    Event::fake();
    $user->vote($post, VoteItems::DOWN);
    Event::assertDispatched(Voted::class, function ($event) use ($user, $post) {
      $vote = $event->vote;
      self::assertFalse($vote->isUp());
      self::assertTrue($vote->isDown());
      return $vote->votable instanceof Post
        && $vote->user instanceof User
        && $vote->user->id === $user->id
        && $vote->votable->id === $post->id;
    });
  }

  public function test_cancelVote_features()
  {
    $user1 = User::create(['name' => 'samuelsihotang1']);
    $user2 = User::create(['name' => 'allen']);

    $post = Post::create(['title' => 'Hello world!']);

    $user1->vote($post, VoteItems::DOWN);
    $user2->vote($post, VoteItems::UP);

    $user1->cancelVote($post);
    $user2->cancelVote($post);

    self::assertFalse($user1->hasVoted($post));
    self::assertFalse($user1->hasDownVoted($post));
    self::assertFalse($user1->hasUpVoted($post));

    self::assertFalse($user1->hasVoted($post));
    self::assertFalse($user2->hasUpVoted($post));
    self::assertFalse($user2->hasDownVoted($post));
  }

  public function test_upVoted_to_downVoted_each_other_features()
  {
    $user1 = User::create(['name' => 'samuelsihotang1']);
    $user2 = User::create(['name' => 'allen']);
    $post = Post::create(['title' => 'Hello world!']);

    $upModel = $user1->vote($post, VoteItems::UP);
    self::assertTrue($user1->hasUpVoted($post));
    self::assertFalse($user1->hasDownVoted($post));

    $downModel = $user1->vote($post, VoteItems::DOWN);
    self::assertFalse($user1->hasUpVoted($post));
    self::assertTrue($user1->hasDownVoted($post));
    self::assertTrue($user1->hasDownVoted($post));
    self::assertEquals($upModel->id, $downModel->id);

    $downModel = $user2->vote($post, VoteItems::DOWN);
    self::assertFalse($user2->hasUpVoted($post));
    self::assertTrue($user2->hasDownVoted($post));

    $upModel = $user2->vote($post, VoteItems::UP);
    self::assertTrue($user2->hasUpVoted($post));
    self::assertFalse($user2->hasDownVoted($post));
    self::assertEquals($upModel->id, $downModel->id);
  }

  public function test_aggregations()
  {
    $user = User::create(['name' => 'samuelsihotang1']);

    $post1 = Post::create(['title' => 'Hello world!']);
    $post2 = Post::create(['title' => 'Hello everyone!']);
    $post3 = Post::create(['title' => 'Hello players!']);
    $book1 = Book::create(['title' => 'Learn laravel.']);
    $book2 = Book::create(['title' => 'Learn symfony.']);
    $book3 = Book::create(['title' => 'Learn yii2.']);

    $user->vote($post1, VoteItems::UP);
    $user->vote($post2, VoteItems::UP);
    $user->vote($post3, VoteItems::DOWN);

    $user->vote($book1, VoteItems::UP);
    $user->vote($book2, VoteItems::UP);
    $user->vote($book3, VoteItems::DOWN);

    self::assertSame(6, $user->votes()->count());
    self::assertSame(4, $user->votes()->withVoteType(VoteItems::UP)->count());
    self::assertSame(2, $user->votes()->withVoteType(VoteItems::DOWN)->count());

    self::assertSame(3, $user->votes()->withVotableType(Book::class)->count());
    self::assertSame(1, $user->votes()->withVoteType(VoteItems::DOWN)->withVotableType(Book::class)->count());
  }

  public function test_vote_same_model()
  {
    $user1 = User::create(['name' => 'samuelsihotang1']);
    $user2 = User::create(['name' => 'allen']);
    $user3 = User::create(['name' => 'taylor']);

    $user1->vote($user2, VoteItems::UP);
    $user3->vote($user1, VoteItems::DOWN);

    self::assertTrue($user1->hasVoted($user2));
    self::assertTrue($user2->isVotedBy($user1));

    self::assertTrue($user1->hasUpVoted($user2));
    self::assertTrue($user2->isUpVotedBy($user1));

    self::assertTrue($user3->hasVoted($user1));
    self::assertTrue($user1->isVotedBy($user3));

    self::assertTrue($user3->hasDownVoted($user1));
    self::assertTrue($user1->isDownVotedBy($user3));
  }

  public function test_object_voters()
  {
    $user1 = User::create(['name' => 'samuelsihotang1']);
    $user2 = User::create(['name' => 'allen']);
    $user3 = User::create(['name' => 'taylor']);

    $post = Post::create(['title' => 'Hello world!']);

    $user1->vote($post, VoteItems::UP);
    $user2->vote($post, VoteItems::DOWN);

    self::assertCount(2, $post->voters);
    self::assertSame('samuelsihotang1', $post->voters[0]['name']);
    self::assertSame('allen', $post->voters[1]['name']);

    $sqls = $this->getQueryLog(function () use ($post, $user1, $user2, $user3) {
      self::assertTrue($post->isVotedBy($user1));
      self::assertTrue($post->isVotedBy($user2));
      self::assertFalse($post->isVotedBy($user3));
    });

    self::assertEmpty($sqls->all());
  }

  public function test_object_votes_with_custom_morph_class_name()
  {
    $user1 = User::create(['name' => 'samuelsihotang1']);
    $user2 = User::create(['name' => 'allen']);
    $user3 = User::create(['name' => 'taylor']);

    $post = Post::create(['title' => 'Hello world!']);

    Relation::morphMap([
      'posts' => Post::class,
    ]);

    $user1->vote($post, VoteItems::UP);
    $user2->vote($post, VoteItems::DOWN);

    self::assertCount(2, $post->voters);
    self::assertSame('samuelsihotang1', $post->voters[0]['name']);
    self::assertSame('allen', $post->voters[1]['name']);
  }

  public function test_eager_loading()
  {
    $user = User::create(['name' => 'samuelsihotang1']);

    $post1 = Post::create(['title' => 'Hello world!']);
    $post2 = Post::create(['title' => 'Hello everyone!']);
    $book1 = Book::create(['title' => 'Learn laravel.']);
    $book2 = Book::create(['title' => 'Learn symfony.']);

    $user->vote($post1, VoteItems::UP);
    $user->vote($post2, VoteItems::DOWN);
    $user->vote($book1, VoteItems::UP);
    $user->vote($book2, VoteItems::DOWN);

    // start recording
    $sqls = $this->getQueryLog(function () use ($user) {
      $user->load('votes.votable');
    });

    self::assertSame(3, $sqls->count());

    // from loaded relations
    $sqls = $this->getQueryLog(function () use ($user, $post1) {
      $user->hasVoted($post1);
    });

    self::assertEmpty($sqls->all());
  }

  /**
   * @param \Closure $callback
   *
   * @return \Illuminate\Support\Collection
   */
  protected function getQueryLog(\Closure $callback): \Illuminate\Support\Collection
  {
    $sqls = \collect([]);
    DB::listen(function ($query) use ($sqls) {
      $sqls->push(['sql' => $query->sql, 'bindings' => $query->bindings]);
    });

    $callback();

    return $sqls;
  }
}
